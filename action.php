<?php

include_once('config.php');
include_once('storage.php');

/**
 * This is the default Action class for the Twitterbot. If you would like to 
 * create more specific Actions, extend this class and override the
 * public methods. If you want to change the default behavior, modify
 * this class.
 * 
 * @author Shannon Quinn
 */
class Action {
  
  // the OAuth object
  protected static $oauth;
  
  protected $method;    // the HTTP method to use in this action
  protected $call;      // the specific Twitter API call to make
  protected $args;      // any arguments necessary for the Twitter API call
  protected $delay;     // delay in between successive executions of this action
  protected $name;      // some unique identifier for this action
  protected $deps;      // identifiers for action dependencies
  protected $result;    // stores the result of the action (for child classes)
  
  /**
   * Sets up an Action object. If this class is extended, this constructor
   * must still be called. The parameter consists of an element of the array 
   * from the configuration file.
   * @param array $action
   */
  public function __construct($action) {
    $this->method = $action['httpmethod'];
    $this->call = $action['twittermethod'];
    $this->args = $action['twitterargs'];
    $this->delay = $action['delay'];
    $this->name = $action['identifier'];
    $this->deps = (isset($action['dependencies']) ? $action['dependencies'] : null);
    if (!isset(Action::$oauth)) {
      Action::$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_TOKEN_SECRET);
    }
  }
  
  /**
   * Performs this action. No data persistence is performed.
   */
  public function doAction() {
    switch (strtoupper($this->method)) {
      case 'GET':
        $this->result = Action::$oauth->get($this->call, $this->args);
        break;
      case 'POST':
        $this->result = Action::$oauth->post($this->call, $this->args);
        break;
      case 'DELETE':
        $this->result = Action::$oauth->delete($this->call, $this->args);
        break;
      default:
        die('ERROR: Unrecognized HTTP method "' . $this->method . '".');
    }
  }
  
  /**
   * If subclassed, this method *must* be overridden to provide installation
   * requirements for the action in the database. The table the action uses
   * is typically named by the $this->name field, but you can use whichever
   * naming convention works for you (so long as the name does not already
   * exist!). If your action does not require a database, then you can
   * leave this method's body empty.
   * @param string $name The name of the table to install in the database.
   */
  public static function install($name) {}
}