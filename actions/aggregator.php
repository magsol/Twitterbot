<?php

defined('TWITTERBOT') or die('Restricted.');

require_once(BOTROOT . DIRECTORY_SEPARATOR . 'storage.php');
require_once(PHIREHOSE . 'Phirehose.php');

/**
 * This class handles reading from the Streaming API and saving any
 * posts it receives. It makes use of the Phirehose PHP implementation
 * for reading the streaming API and ensuring good behavior.
 * 
 * Phirehose: http://code.google.com/p/phirehose/
 * Streaming API: http://dev.twitter.com/pages/streaming_api
 * 
 * @author Shannon Quinn
 */
class DataAggregator extends Phirehose {
  
  /** a database handle for storing the data */
  private $db;
  
  /**
   * Overridden constructor for initializing the database connection.
   * @param string $username
   * @param string $password
   */
  public function __construct($username, $password) {
    return parent::__construct($username, $password, Phirehose::METHOD_SAMPLE);
  }
  
  /**
   * Helper method. It's kind of a hack job, since we want the Twitterbot
   * object to have access to the Aggregator object before the process
   * forking occurs, but the signal handling has to be specified *after*
   * the process is forked. Hence, a separate method from the constructor
   * for the latter. Just call this method once its process has been forked.
   */
  public function initSignalHandler() {
    pcntl_signal(SIGTERM, array($this, 'sig_term'));
  }
  
  /**
   * (non-PHPdoc)
   * @see util/Phirehose::enqueueStatus()
   */
  public function enqueueStatus($status) {
    if (!isset($this->db)) { $this->db = Storage::getDatabase(); }
    
    // save the status
    $data = json_decode($status, true);
    if (is_array($data) && isset($data['user']['screen_name'])) {
      $this->db->savePost($data['text'], $data['user']['screen_name']);
    }
  }
  
  /**
   * (non-PHPdoc)
   * @see util/Phirehose::log()
   */
  protected function log($message) {
    if (!isset($this->db)) { $this->db = Storage::getDatabase(); }
    $this->db->log('Phirehose', $message);
  }
  
  /**
   * Signal handler for this object.
   * @param int $signal
   */
  private function sig_term($signal) {
    // graceful shutdown
    $this->disconnect();
  }
}
//$data = new DataAggregator(BOT_ACCOUNT, BOT_PASSWORD, Phirehose::METHOD_SAMPLE);
//$data->consume();
?>