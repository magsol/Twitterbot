<?php

defined('TWITTERBOT') or die('Restricted.');

/**
 * This does a bit of cleanup work. The aggregator is quite efficient at what
 * it does...almost too efficient. Left unchecked, it can aggregate tweets into
 * the many millions within only a few days, significantly slowing down queries
 * by other Actions and bringing the whole framework to a halt. This
 * periodically deletes posts, either by raw amount or percentage.
 *
 * OPTIONAL ARGS:
 * -percentDelete: If > 0.0, this is the percentage of the number of posts to
 * delete (from least recent to most recent).
 * -numberDelete: If > 0, this is the raw number of posts to delete (from least
 * recent to most recent).
 * -maxAllowed: if > 0, this is the maximum number of tweets allowed in the
 * database, all others will be deleted (from least recent to most recent).
 * -delay: Minutes in between firings.
 *
 * NOTE: If more than one of these values are set, the order of precedence is:
 * 1) maxAllowed
 * 2) percent
 * 3) number
 */
class PostCleanupAction extends Action {

  private $percentDelete = -1.0;
  private $numberDelete = -1;
  private $maxAllowed = 1000000;
  private $delay = 25;

  /**
   * Constructor.
   * @param string $name
   * @param bool $active
   * @param array $args
   */
  function __construct($name, $active, $args = array()) {
    parent::__construct($name, $active, array());
    foreach ($args as $k => $v) {
      $this->$k = $v;
    }

    if ((isset($args['maxAllowed']) && isset($args['numberDelete'])) ||
      (isset($args['maxAllowed']) && isset($args['percentDelete']))) {
      $this->numberDelete = -1;
      $this->percentDelete = -0.1;
    } else if (isset($args['percentDelete']) && isset($args['numberDelete'])) {
      $this->numberDelete = -0.1;
    }
    $this->frequency = $this->delay;
    $this->setNextAttempt();
  }

  /**
   * @see Action::run()
   */
  public function run() {
    $this->db = Storage::getDatabase();

    // First, figure out how many tweets there are in the whole database.
    $query = 'SELECT COUNT(*) FROM `' . DB_NAME . '`.`' . POST_TABLE . '`';
    $this->db->setQuery($query);
    $result = $this->db->query();
    $numPosts = $result[0]['COUNT(*)'];
    $toDelete = 0;
    if ($this->maxAllowed > 0) {
      if ($this->maxAllowed > $numPosts) {
        return parent::SUCCESS;
      }
      $toDelete = $numPosts - $this->maxAllowed;
    } else if ($this->number > 0) {
      if ($this->number > $numPosts) {
        return parent::SUCCESS;
      }
      // Delete this number of posts.
      $toDelete = $this->number;
    } else {
      $toDelete = intval($this->percent * (double)$numPosts);
    }
    $query = 'DELETE FROM `' . DB_NAME . '`.`' . POST_TABLE . '` ORDER BY ' .
      '`date_saved` ASC LIMIT ' . $toDelete;
    $this->db->setQuery($query);
    $this->db->query();
    $this->db->log($this->getName(), $toDelete . ' tweet' .
      ($toDelete != 1 ? 's' : '') . ' deleted!');
    return parent::SUCCESS;
  }
}
