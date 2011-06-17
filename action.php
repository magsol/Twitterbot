<?php

defined('TWITTERBOT') or die('Restricted.');

require_once('config.php');
require_once('storage.php');

/**
 * This is the abstract superclass that all other Twitterbot Actions must
 * extend. Namely, they require a "run" method that can be invoked from
 * the main method of the application. All other implemented functions
 * are at your discretion.
 *
 * @author Shannon Quinn
 */
abstract class Action {

  const FAILURE = 0;
  const SUCCESS = 1;

  protected $name; // name of the class
  protected $isActive; // is this action active?
  protected $timeout = 2; // minutes until this action times out
  protected $nextAttempt; // specific time this event will fire next
  protected $frequency; // minutes to increment the next firing time
  protected $db; // the database

  protected $currentStatus = Action::SUCCESS;
  protected $previousStatus = Action::SUCCESS;

  /**
   * Default constructor.
   * @param string $name The name of this action.
   * @param boolean $active Whether or not this action is active.
   * @param array $params Key/value pairs of parameters.
   */
  public function __construct($name, $active, $params) {
    // assign all the variables
    foreach ($params as $k => $v) {
      $this->$k = $v;
    }
    $this->name = $name;
    $this->isActive = $active;
  }

  /**
   * This method also needs to be implemented by the subclasses. Dictates
   * how the action runs.
   * NOTE: If you need access to a database connection, do it in here!
   *
   * @return Action::SUCCESS if the method runs successfully,
   * Action::FAILURE otherwise.
   */
  public abstract function run();

  /**
   * Calculates the time for the next firing of this action.
   */
  public function setNextAttempt() {
    $this->nextAttempt = time() + ($this->frequency * 60);
  }

  /**
   * Accessor for the nextAttempt field.
   * @return The unix timestamp of when this action should fire.
   */
  public function getNextAttempt() {
    return $this->nextAttempt;
  }

  /**
   * Accessor for the timeout count.
   * @return The number of seconds until this action should be timed out if
   * it has not completed.
   */
  public function getTimeout() {
    return $this->timeout * 60;
  }

  /**
   * This method can be called after the run() method to perform
   * post-processing.
   *
   * In this case, it logs any failures (if the run() return value is
   * Action::FAILURE) and saves the necessary values to the database.
   * @param int $status The return code from the child process exiting.
   */
  public function post_run($status) {
    $this->db = Storage::getDatabase();

    // log the status of this action
    if ($status !== $this->currentStatus) {
      $this->previousStatus = $this->currentStatus;
    }
    if ($status === self::FAILURE) {
      if ($this->currentStatus === self::FAILURE) {
        // failed consecutive times
        $this->db->log($this->name, "Still have not recovered from previous" .
          "error!");
      } else {
        // this is the first time the action has failed
        $this->db->log($this->name, "Error has occurred!");
      }
    } else {
      // Action::SUCCESS. Log this only if the previous status
      // was Action::FAILURE, so we know we've recovered from something.
      if ($this->previousStatus === Action::FAILURE) {
        $this->db->log($this->name, "Recovered from previous failure.");
      }
    }
    // set the current status
    $this->currentStatus = $status;

    // destroy the database connection
    unset($this->db);
  }

  /**
   * Simple accessor for the state of this action.
   * @return True if this Action is active, false otherwise.
   */
  public function isActive() {
    return $this->isActive;
  }

  /**
   * Change the active state of this action. This is changed through
   * signaling.
   * @param boolean $state The new active state of this action (true or false).
   */
  public function setActive($state) {
    $this->isActive = $state;
  }

  /**
   * Accessor for this action's name.
   * @return The name (unique identifier) of this action.
   */
  public function getName() {
    return $this->name;
  }
}
