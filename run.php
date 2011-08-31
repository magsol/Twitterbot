<?php

define('TWITTERBOT', 1);

require_once('config.php');
require_once('twitterbot.php');
require_once('action.php');
require_once(OAUTH . 'twitteroauth.php');

// which args are present?
if (count($argv) > 2 || in_array('-h', $argv) || in_array('--help', $argv)) {
	die(printHelp());
}

// One final sanity check: make sure the user wanted this thing to start.
if (count($argv) == 2 && !in_array('--start', $argv)) {
	die(printHelp());
}

if (in_array('--stop', $argv)) {
  // stop has been set! try to kill it
  echo "\n" . '==SHUTTING DOWN TWITTERBOT==' . "\n";
  halt();
  exit;
} else if (!in_array('--tests-skip', $argv)) {
  // run tests
  test();
  if (in_array('--tests-only', $argv)) {
    exit;
  }
}

echo "\n" . '==POWERING UP TWITTERBOT==' . "\n";

// gain the lockfile
$fp = @fopen(TWITTERBOT_LOCKFILE, "a");
if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
  die("Failed to acquire lock. Twitterbot may already be running.\n");
}

// this bit guarantees that the process is 1) detached, and 2) independent
echo 'Forking process into a daemon. Goodbye :)' . "\n";
if (pcntl_fork()) {
  exit;
}
posix_setsid();
if (pcntl_fork()) {
  exit;
}

// write the PIDs
fwrite($fp, getmypid() . "\n");
fflush($fp);

// start the loop
$engine = new Twitterbot();
$engine->loop();

// reaching this point means an exit command has been issued
flock($fp, LOCK_UN);
fclose($fp);
@unlink(TWITTERBOT_LOCKFILE);

/**
 * Runs tests against the configuration of this bot, to make sure everything
 * is in working order.
 */
function test() {
  // test the database connection parameters
  echo "\n" . '==TESTING TWITTERBOT==' . "\n";
  echo 'Testing MySQL connection...';
  $db = @mysql_connect(DB_HOST, DB_USER, DB_PASS);
  if (!$db) {
    die('ERROR: Invalid MySQL connection parameters: ' . mysql_error() . "\n");
  }
  echo 'passed!' . "\n";
  mysql_close($db);

  // test Twitter OAuth settings
  echo 'Testing OAuth credentials...';
  $obj = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN,
    OAUTH_TOKEN_SECRET);
  $retval = $obj->get('account/verify_credentials');
  if (!is_object($retval) || !isset($retval->name)) {
    die('ERROR: Unable to successfully establish an OAuth connection with' .
     ' Twitter.' . "\n");
  }
  if ($retval->screen_name != BOT_ACCOUNT) {
    die('ERROR: The BOT_ACCOUNT indicated in configuration differs from' .
     ' what the Twitter API said.' . "\n");
  } else {
    echo 'passed!' . "\n";
  }

  // test the actions listed in the configuration file
  global $actions;
  foreach ($actions as $action) {
    echo 'Found action ' . $action['name'] . ', checking that all required ' .
      'fields are set...';
    if (isset($action['name']) && isset($action['file']) &&
        isset($action['class']) && isset($action['active'])) {
      echo "passed!\n";
    } else {
      die('ERROR: One or more required fields are missing in your ' .
        'config.php for an action.' . "\n");
    }
    echo 'Checking instantiability of ' . $action['class'] . '...';
    if (!file_exists(ACTIONS . $action['file'])) {
      die('ERROR: Unable to find class file for the custom Action.' . "\n");
    }
    include_once(ACTIONS . $action['file']);
    $class = new ReflectionClass($action['class']);
    if (!$class->isInstantiable()) {
      die('ERROR: Unable to instantiate class ' . $action['class'] . ".\n");
    }
    echo 'passed!' . "\n";
  }

  // finally, test a few PHP dependencies
  echo 'Looking for pcntl_fork()...';
  if (!function_exists('pcntl_fork')) {
    die('ERROR: pcntl_fork() is undefined. Please check your PHP ' .
      'configuration.' . "\n");
  }
  echo 'passed!' . "\n";
  echo 'Looking for Console_Getopt...';
  if (!class_exists('Console_Getopt')) {
    die('ERROR: Console_Getopt not found. Please check your PEAR ' .
      'configuration.' . "\n");
  }
  echo 'passed!' . "\n";
}

/**
 * Constructs a string with the help information for running this program.
 * @return The string
 */
function printHelp() {
  $retval = "Twitterbot, v2.0\n\n" .
    "php run.php [--start | --stop | --tests-only | --tests-skip " .
    "| --help ]\n\n" .
    "--start\t\t\tStart the twitterbot daemon\n" .
    "--stop\t\t\tStop the twitterbot daemon\n" .
    "--tests-only\t\tExecute the pre-process tests and exit\n" .
    "--tests-skip\t\tDon't run any tests before launching the daemon\n" .
    "--help or -h\t\tPrints this help\n";
  return $retval;
}

/**
 * This function attempts to gracefully shut down the program.
 */
function halt() {
  // first, read the PID from the lockfile
  echo 'Reading the lockfile...' . "\n";
  $contents = @file(TWITTERBOT_LOCKFILE);
  if (!$contents) {
    die('ERROR: Failed to acquire lock. Twitterbot may not be running.' . "\n");
  }
  $pid = intval($contents[0]);
  echo 'Found process with id ' . $pid . ', killing...' . "\n";
  // next, send a kill process; the handlers will take care of it from there
  posix_kill($pid, SIGTERM);
  echo 'Shutdown complete!' . "\n";
}

?>
