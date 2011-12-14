<?php

defined('TWITTERBOT') or die('Restricted.');

include_once(UTIL . 'TwitterAPI.php');
include_once(UTIL . 'translate.php');
include_once(ACTIONS . 'delayedmarkovpostaction' . DIRECTORY_SEPARATOR .
  'markovsecondorder.php');

/**
 * This class performs twitter post updates in a delayed fashioin; that is,
 * it implements a gaussian-sampled waiting time between posts. For posts,
 * it currently uses a second-order markov chain constructed from the output
 * of the Phirehose. It will translate the sampled post and send it to Twitter.
 *
 * OPTIONAL ARGS:
 * -delayMean: Mean of the gaussian distribution for # of minutes between posts.
 * -delayVar: Variance of the gaussian for # of minutes between posts.
 * -useUnmodeledPosts: Use only unmodeled posts in the database to build the
 * markov chain.
 * -googlekey: Google API key, for using Google Translate
 * -postLimit: Maximum number of posts to pull to build the markov chain.
 *
 * @author Shannon Quinn
 */
class DelayedMarkovPostAction extends Action {

  private $delayMean = 45;
  private $delayVar = 1;
  private $googleKey;
  private $postLimit = 0;
  private $useOnlyUnmodeledPosts = false;

  /**
   * Constructor
   * @param string $name Custom name for this action
   * @param bool $active Is this action listed as active?
   * @param array $args Array of custom arguments
   */
  public function __construct($name, $active, $args = array()) {
    // don't know why PHP doesn't have its child classes implicitly call
    // the parent constructors...but always a good idea to do so
    parent::__construct($name, $active, array());

    // optional arguments
    foreach ($args as $k => $v) {
      $this->$k = $v;
    }
    $this->setNextAttempt();
  }

  /**
   * @see Action::run()
   */
  public function run() {
    $this->db = Storage::getDatabase();

    /*** PART 1: Read from saved posts and construct a markov chain ***/
    $posts = $this->db->getPosts($this->useOnlyUnmodeledPosts,
      $this->postLimit);
    $markov = $this->buildMarkovChain($posts);
    if ($this->useOnlyUnmodeledPosts && count($posts) > 0) {
      $recent_date = $posts[0]['date_saved'];
      $old_date = $posts[count($posts) - 1]['date_saved'];
      $this->db->markPostsModeled($old_date, $recent_date);
    }

    /*** PART 2: now that we have our markov chain built, sample from it to
     * build the post ***/
    $thepost = $this->constructPostFromMarkovChain($markov);

    /*** PART 3: Translate the post and send it, if the API key was set ***/
    $API = TwitterAPI::getTwitterAPI();
    /*
    ** GOOGLE TRANSLATE API DEPRECATED **
    if (isset($this->googleKey)) {
      $thepost = Translate::translate($thepost, 'en', $this->googleKey);
    }
    */
    $API->update($thepost);

    // destroy the database connection
    unset($this->db);

    // all done
    return parent::SUCCESS;
  }

  /**
   * Overridden from parent class' declaration. Since this action relies
   * on a probability distribution to determine its next firing, it is not
   * a simple addition of terms. Thus, for this class, the "frequency" field
   * is ignored and replaced with "delayMean" and "delayVar".
   *
   * @see Action::setNextAttempt()
   */
  public function setNextAttempt() {
    $mean = floatval($this->delayMean);
    $var = floatval($this->delayVar);
    $rand1 = floatval(mt_rand()) / floatval(mt_getrandmax());
    $rand2 = floatval(mt_rand()) / floatval(mt_getrandmax());

    // sample from a normal (gaussian) distribution
    $delay = intval((sqrt(-2 * log($rand1)) * cos(2 * pi() * $rand2) * $var) +
      $mean);
    if ($delay <= 0) { $delay = 1; } // sanity check
    $this->nextAttempt = time() + intval($delay * 60);

    // log the next attempt
    $this->db = Storage::getDatabase();
    $this->db->log($this->getName(), 'Next action firing set for ' .
      date('Y-m-d H:i:s', $this->nextAttempt));
    unset($this->db);
  }

  /**
   * This helper method appends the raw posts from the Twitter public timeline
   * to the provided second-order markov chain. It extracts each individual
   * post, explodes it into its constituent words, performs any needed
   * preprocessing, builds the words in the markov chain, and returns
   * the updated markov chain.
   *
   * @param array $posts JSON-decoded twitter posts from the public timeline.
   * @return object A markov chain, updated with the new posts.
   */
  private function buildMarkovChain($posts) {
    $numposts = count($posts);
    $markov = new MarkovSecondOrder();
    for ($j = 0; $j < $numposts; $j++) {
      $words = explode(' ', trim($posts[$j]['text']));
      array_unshift($words, '_START1_', '_START2_');
      $words[] = '_STOP_';
      $numwords = count($words);
      for ($k = 2; $k < $numwords; $k++) {
        $markov->add($words[$k - 2], $words[$k - 1], $words[$k]);
      }
    }
    return $markov;
  }

  /**
   * This helper method iterates over the second-order markov chain, sampling
   * from it appropriately and constructing a post from it.
   *
   * @param object $markov The second-order markov chain.
   * @return string The assembled post.
   */
  private function constructPostFromMarkovChain($markov) {
    $word1 = '_START1_';
    $word2 = '_START2_';
    $next = '';
    $thepost = '';
    while (($next = $markov->get($word1, $word2)) != '_STOP_') {
      $temp = $thepost . $next;
      if (strlen($temp) > 140) {
        $temp = trim($temp);
        break;
      }
      $thepost = $temp . ' ';
      $word1 = $word2;
      $word2 = $next;
    }
    return $thepost;
  }
}

?>
