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
   * Public method for shutting down the Phirehose.
   * NOTE: Calling this method won't invoke an immediate shutdown.
   * It will begin the process, but it is largely a matter of the Phisehose
   * doing its thing to close all active connections and finish any
   * in-process transactinos.
   */
  public function shutdown() {
    $this->log("Shutdown command received.");
    $this->disconnect();
  }
}

?>
