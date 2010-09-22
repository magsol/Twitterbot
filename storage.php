<?php

// Handles the persistence with the database
include_once('config.php');

class Storage {
  
  /** the database handle */
  private $db;
  
  /** the current query */
  private $query;
  
  /**
   * Static factory method. Ensures only one instance of the database
   * connection handle is available at any given time.
   */
  public static function getDatabase() {
    static $instance;
    if (!is_object($instance)) {
      $instance = new Storage(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }
    return $instance;
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
      // update, insert, delete, etc statement, return the number of affected rows
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
}

?>