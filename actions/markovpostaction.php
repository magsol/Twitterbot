<?php

include_once('markov.php');
include_once('publictimelineaction.php');

/**
 * This class is responsible for compiling all stored posts, generating
 * a Markov chain from them, and creating a post.
 * @author Shannon Quinn
 *
 */
class MarkovPostAction extends Action {
  
  private $markov;
  
  /**
   * Constructor
   * @param string $action
   * @param Storage $storage
   */
  public function __construct($action) {
    parent::__construct($action);
    $this->markov = new MarkovFirstOrder();
  }
  
  /**
   * (non-PHPdoc)
   * @see Action::install()
   */
  public static function install($name) {
    $sql = 'CREATE TABLE IF NOT EXISTS `' . DB_NAME . '`.`' . $name . '` (' .
            '`postid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,' . 
            '`post` VARCHAR( 140 ) NOT NULL ,' .
            '`time_stamp` DATETIME NOT NULL ,' .
            '`notes` TEXT NOT NULL)';
    $db = Storage::getDatabase();
    $db->setQuery($sql);
    $db->query();
    
    // insert one post so as to create a base timeline
    $query = 'INSERT INTO ' . $name . ' (post, time_stamp, notes) VALUES ' .
              '("Action installed!", "' . date('Y-m-d H:i:s') . '", "' .
              'Baseline entry for future posts.")';
    $db->setQuery($query);
    $db->query();
  }
  
  /**
   * Reads from the public timeline file, preprocesses all the tweets,
   * and then creates a Markov chain from which to sample a new post.
   * @see Action::doAction()
   */
  public function doAction() {
    // first, has enough time passed between updates?
    if ($this->notEnoughTime()) {
      return;
    }
    
    // read all the saved tweets
    $posts = $this->getSavedPosts();
    if (count($posts) == 0) { return; }
    
    // then, build a markov chain
    foreach ($posts as $post) {
      // split each line
      $words = explode(' ', trim($post));
      
      // feed each word into the markov chain
      $numwords = count($words);
      for ($i = 1; $i < $numwords; $i++) {
        $this->markov->add($words[$i - 1], $words[$i]);
      }
    }
    
    // now that the markov chain has been built, sample from it
    // to generate a post
    $curWord = '_START_';
    $nextWord = '';
    $thePost = '';
    $notes = '';
    while (($nextWord = $this->markov->get($curWord)) != '_STOP_') {
      $temp = $thePost . $nextWord;
      if (strlen($temp) > 140) {
        $notes = 'Post was too long and is truncated at 140 characters.';
        break;
      }
      // reaching this point means our conglomerate post is under 140 characters,
      // so move the temp variable over to the full variable and continue
      $thePost = $temp . ' '; // add a space at the end
      $curWord = $nextWord;   // update the word we're on for the markov chain
    }
    
    // perform the action
    $this->args['status'] = $thePost;
    parent::doAction();
    
    // update the posts so they aren't used again
    $this->markModeledPosts();
    
    // mark when the last post was made
    $query = 'INSERT INTO ' . $this->name . ' (post, time_stamp, notes) VALUES ' .
            '("' . mysql_real_escape_string($thePost) . '", "' . 
            date('Y-m-d H:i:s') . '", "' . mysql_real_escape_string($notes) . '")';
    $db = Storage::getDatabase();
    $db->setQuery($query);
    $db->query();
  }
  
  /**
   * Utility method for pulling out all the posts stored by the PublicTimeline
   * action, and marking them as having been used.
   * 
   * @return array The array of posts.
   */
  private function getSavedPosts() {
    $retval = array();
    $db = Storage::getDatabase();
    $query = 'SELECT post FROM ' . $this->deps[0] . ' WHERE modeled = 0';
    $db->setQuery($query);
    $result = $db->query();
    foreach ($result as $element) {
      $retval[] = $element['post'];
    }
    return $retval;
  }
  
  /**
   * Once a post has been made, this method is used to update all the
   * posts that were used in the model to be marked as such, so they
   * are not used again (or used for different purposes).
   */
  private function markModeledPosts() {
    $db = Storage::getDatabase();
    $query = 'UPDATE ' . $this->deps[0] . ' SET modeled = 1 WHERE modeled = 0';
    $db->setQuery($query);
    $db->query();
  }
  
  /**
   * Test that $this->delay is greater than the difference between the current
   * time and the timestamp associated with the last update
   * 
   * @return boolean
   */
  private function notEnoughTime() {
    $db = Storage::getDatabase();
    $query = 'SELECT time_stamp FROM ' . $this->name . ' ORDER BY time_stamp ' .
              'DESC LIMIT 1';
    $db->setQuery($query);
    $result = $db->query();
    if (count($result) == 0) { // there are no updates, so definitely post one 
      return false;
    }
    //$diff = $this->delay * 60; // time delay in seconds
    $diff = 0;
    return ((time() - strtotime($result[0]['time_stamp'])) <= $diff);
  }
}

?>