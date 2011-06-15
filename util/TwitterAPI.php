<?php

defined('TWITTERBOT') or die('Restricted.');

/**
 * This class encapsulates all the major calls that can be made to the
 * Twitter API.
 *
 * NOTE: This does *NOT* include the streaming API! That is done
 * via the Phirehose package.
 *
 * @author Shannon Quinn
 */
class TwitterAPI {

  private $oauth;
  private $user;

  /**
   * Static factory method.
   */
  public static function getTwitterAPI() {
    static $instance;
    if (!is_object($instance)) {
      $instance = new TwitterAPI();
    }
    return $instance;
  }

  /**
   * Defines the user (as defined in the configuration file) and
   * initializes the OAuth object for making API calls.
   */
  private function __construct() {
    $this->user = BOT_ACCOUNT;
    $this->oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN,
      OAUTH_TOKEN_SECRET);
  }

  /**
   * Private helper method for performing the given action with OAuth.
   * @param string $method The HTTP method.
   * @param string $call The Twitter API method call.
   * @param array $args Array of arguments for the API call.
   * @return Raw JSON-encoded response from the Twitter servers.
   */
  private function doAction($method, $call, $args) {
    switch (strtoupper($method)) {
      case 'GET':
        return $this->oauth->get($call, $args);
      case 'POST':
        return $this->oauth->post($call, $args);
      case 'DELETE':
        return $this->oauth->delete($call, $args);
    }
  }

  /**
   * Do the public_timeline action.
   * See http://dev.twitter.com/doc/get/statuses/public_timeline
   * @param array $args
   * @return JSON-decoded array.
   */
  public function public_timeline($args = array()) {
    return $this->doAction('get', 'statuses/public_timeline', $args);
  }

  /**
   * Do the update action. Posts a new tweet.
   * See http://dev.twitter.com/doc/post/statuses/update
   * @param string $post
   * @param array $args
   * @return JSON-decoded array
   */
  public function update($post, $args = array()) {
    return $this->doAction('post', 'statuses/update',
      array_merge(array('status' => $post),$args));
  }

  /**
   * Performs a search through Twitter's public archives for posts with
   * the given search terms.
   * See http://dev.twitter.com/doc/get/search
   *
   * NOTE: Due to current separations in Twitter's API, this method is
   * implemented separately from the rest of the methods, specifically
   * it does not use OAuth in order to perform its function.
   *
   * @param string $searchstring
   * @param array $args
   */
  public function search($searchstring, $args = array()) {
    $url = 'http://search.twitter.com/search.json?q=' .
      urlencode($searchstring);
    foreach ($args as $key => $value) {
      $url .= '&' . $key . '=' . urlencode($value);
    }
    // set up the curl session
    $ch = curl_init();
    $options = array(CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true);
    curl_setopt_array($ch, $options);
    $retval = json_decode(curl_exec($ch));
    curl_close($ch);
    return $retval;
  }
}

?>
