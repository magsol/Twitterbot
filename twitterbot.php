<?php

defined('TWITTERBOT') or die('Restricted.');

require_once('config.php');
require_once('storage.php');
require_once(ACTIONS . 'aggregator.php');

/**
 * This class handles the lion's share of creating the action processes,
 * forking them off into daemons, and monitoring them to ensure they 
 * execute correctly. This class is based heavily off the ServiceRunnerCheck
 * class in George Schlossnagle's "Advanced PHP Programming" book (2004), chpt 5.
 *
 * @author Shannon Quinn
 */
class Twitterbot {
  
  private $actions = array(); // actions we're interested in running
  private $current = array(); // any actions currently running (child processes)
  private $aggregator; // the phirehose aggregator
  private $exit = false; // a flag to indicate when we're exiting
  private $db; // this object's own database handle
  
  /**
   * Initializes the engine.
   */
  public function __construct() {
    global $actions; // pull in the actions array from the configuration
    $this->db = Storage::getDatabase();
    
    // first, initialize all the user-specified actions
    foreach ($actions as $action) {
      require_once(ACTIONS . $action['file']);
      $class = new ReflectionClass($action['class']);
      if ($class->isInstantiable()) {
        $item = $class->newInstance($action['name'], $action['active'], 
          (isset($action['args']) ? $action['args'] : array()));
        if ($item->isActive()) {
          $this->actions[] = $item;
        }
      } else {
        die('Twitterbot: ERROR: ' . $action['name'] . ' is not instantiable!');
      }
    }
    
    // now, set up the post aggregator they'll all use
    $this->aggregator = new DataAggregator(BOT_ACCOUNT, BOT_PASSWORD, Phirehose::METHOD_SAMPLE);
  }
  
  /**
   * Sorts the actions based on which is to execute next.
   * @param Action $a
   * @param Action $b
   * @return -1 if $a < $b, 1 if $a > $b, 0 if $a == $b
   */
  private function nextAttemptSort($a, $b) {
    if ($a->getNextAttempt() == $b->getNextAttempt()) {
      return 0;
    }
    return ($a->getNextAttempt() < $b->getNextAttempt() ? -1 : 1);
  }
  
  /**
   * Returns the next action to fire.
   * @return Action The next action to fire.
   */
  private function next() {
    usort($this->actions, array($this, 'nextAttemptSort'));
    return (count($this->actions) > 0 ? $this->actions[0] : null);
  }
  
  /**
   * This is the main function of this class. This registers any needed
   * signal handlers, starts an infinite loop, and fires any events
   * as they need to be fired.
   */
  public function loop() {
    declare(ticks = 1);
    
    // set up signal handlers
    pcntl_signal(SIGCHLD, array($this, "sig_child"));
    pcntl_signal(SIGTERM, array($this, "sig_kill"));
    pcntl_signal(SIGINT, array($this, "sig_kill"));
    
    // start the phirehose aggregator
    if ($pid = pcntl_fork()) {
      // parent process: record that this process is running
      $this->current[$pid] = $this->aggregator;
    } else {
      // child process: make this one an independent daemon as well
      posix_setsid();
      if (pcntl_fork()) { exit; }
      // we are now completely independent from the original twitterbot process
      exit($this->aggregator->consume());
    }
    
    // now start all the other actions
    while (1) {
      // do we exit?
      if ($this->exit) {
        break;
      }

      // determine the next action that should fire
      $now = time();
      $action = $this->next();
      if ($action == null) {
        // in this case, just the aggregator is running
        sleep(1000);
        continue; 
      }
      if ($now < $action->getNextAttempt()) {
        // sleep until the next action has to fire
        sleep($action->getNextAttempt() - $now);
        continue;
      }
      $action->setNextAttempt();
      if ($pid = pcntl_fork()) {
        $this->current[$pid] = $action;
      } else {
        pcntl_alarm($action->getTimeout());
        exit($action->run());
      }
    }
  }
  
  /**
   * Signal handler for child processes that have exited via SIGCHLD.
   * @param int $signal
   */
  private function sig_child($signal) {
    $status = Action::FAILURE;
    pcntl_signal(SIGCHLD, array($this, "sig_child"));
    while (($pid = pcntl_wait($status, WNOHANG)) > 0) {
      $action = $this->current[$pid];
      unset($this->current[$pid]);
      if (pcntl_wifexited($status) && pcntl_wexitstatus($status) == Action::SUCCESS) {
        $status = Action::SUCCESS;
      }
      
      // run the post-action commands
      if ($action != $this->aggregator) {
        $action->post_run($status);
      } else {
        if (!$this->exit) {
          // the aggregator broke!
          $this->db->log('Twitterbot.php', 'ERROR: The phirehose broke!');
          $this->exit = true;
          // NOTE: since this twitterbot is still experimental, I am opting
          // to kill the entire bot in this case until I develop a better
          // means of handling a broken phirehose

        } // otherwise we have a graceful shutdown of Phirehose
      }
    }
  }
  
  /**
   * Signal handler for SIGTERM and SIGINT, conducts a graceful shutdown if 
   * the user forcefully quits the parent process for the twitterbot.
   * @param int $signal
   */
  private function sig_kill($signal) {
    // send a kill signal to all the processes still running
    foreach ($this->current as $pid => $action) {
      // send the SIGTERM signal to the child
      posix_kill($pid, SIGTERM);
    }
    // set the flag to kill the parent process
    $this->exit = true;
  }
}

?>