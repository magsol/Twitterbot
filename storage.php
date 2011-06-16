<?php

defined('TWITTERBOT') or die('Restricted.');

/**
 * Defines a means by which we interact with the database.
 *
 * @author Shannon Quinn
 */
class Storage {

  /** the database handle */
  private $db;

  /** the current query */
  private $query;

  /**
   * Static factory method. Returns a new instance of a database
   * connection each time it's called.
   *
   * NOTE: It is the caller's responsibility NOT to call this
   * method several times within a single round of execution!
   */
  public static function getDatabase() {
    return new Storage(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  }

  /**
   * Constructor. Sets up the database connection
   * @param string $host Database hostname
   * @param string $user Username
   * @param string $pass Password
   * @param string $name Database name
   */
  private function __construct($host, $user, $pass, $name) {
    $this->db = @mysql_pconnect($host, $user, $pass);
    if (!$this->db) {
      die('ERROR: Unable to access the database. ' . mysql_error() . "\n");
    }
    mysql_select_db($name);
    $this->query = 'SHOW TABLES';
  }

  /**
   * Destructor
   */
  public function __destruct() {
    mysql_close($this->db);
  }

  /**
   * Sets the current query string.
   * @param string $query
   */
  public function setQuery($query) {
    $this->query = $query;
  }

  /**
   * Executes the set query string and returns any pertinant results.
   * @return An array of the results, or number of updated tables.
   */
  public function query() {
    // first, perform the query
    $result = mysql_query($this->query);

    // what was the return value?
    if ($result === false) {
      // error!
      die('ERROR: MySQL query failed. ' . mysql_error() . "\n");
    } else if ($result === true) {
      // update, insert, delete, etc statement, return the number of
      // affected rows
      return mysql_affected_rows();
    } else {
      // select statement, return the results
      $retval = array();
      while ($row = mysql_fetch_array($result)) {
        $retval[] = $row;
      }
      return $retval;
    }
  }

  /**
   * Retrieves a specific number of twitter posts stored in the table. A nice
   * utility function, given this will probably make up most of the database
   * accesses.
   *
   * @param boolean $unmodeled If true, this returns the $number most recent
   *        posts marked as unmodeled. If false, this returns the $number most
   *        recent posts (regardless of their modeled/unmodeled state).
   * @param int $number The number of posts to retrieve. If this is not
   *        specified (or set to be <= 0), all posts are retrieved (with respect
   *        to the $unmodeled parameter).
   * @return array List of twitter posts.
   */
  public function getPosts($unmodeled, $number = 0) {
    $query = 'SELECT `text`, `date_saved` FROM `' . DB_NAME . '`.`' . POST_TABLE . '`' .
      (isset($unmodeled) && $unmodeled ? ' WHERE `modeled` = 0' : '') .
      ' ORDER BY `date_saved` DESC' . ($number > 0 ? ' LIMIT ' . $number : '');
    $this->setQuery($query);
    return $this->query();
  }

  /**
   * Another utility method. This saves a post to the database.
   * @param string $status
   * @param string $user
   */
  public function savePost($status, $user) {
    $sql = 'INSERT INTO `' . DB_NAME . '`.`' . POST_TABLE .
      '` (text, user, date_saved, modeled) ' . 'VALUES ("' .
      mysql_real_escape_string(urldecode($status)) . '", "' .
      mysql_real_escape_string($user) . '", "' . date('Y-m-d H:i:s') . '", 0)';
    $this->setQuery($sql);
    $this->query();
  }

  /**
   * Utility method for marking a large range of saved posts as "modeled".
   * This works inclusively: the range of posts marked as "modeled" includes
   * those with the timestamps.
   *
   * @param string $old_date The oldest date post to mark as modeled.
   * @param string $recent_date The most recent date post.
   */
  public function markPostsModeled($old_date, $recent_date) {
    $sql = 'UPDATE `' . DB_NAME . '`.`' . POST_TABLE .
      '` SET modeled = 1 WHERE date_saved BETWEEN "' . $old_date .
      '" AND "' . $recent_date . '"';
    $this->setQuery($sql);
    $this->query();
  }

  /**
   * Utility method for saving a log entry to the database.
   * @param string $sender Author of the message.
   * @param string $message Message itself.
   */
  public function log($sender, $message) {
    $sql = 'INSERT INTO `' . DB_NAME . '`.`' . LOG_TABLE .
      '` (eventtime, message) ' . 'VALUES ("' . date('Y-m-d H:i:s') .
      '", "' . mysql_real_escape_string($sender . ': ' . $message) . '")';
    $this->setQuery($sql);
    $this->query();
  }
}

?>
