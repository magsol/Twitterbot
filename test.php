<?php

include_once('config.php');
include_once('twitteroauth/twitteroauth.php');

// Tests the system setup to make sure that, at least from this end,
// everything seems to be running correctly.

// test the database connection parameters
echo 'Testing MySQL connection...';
$db = @mysql_pconnect(DB_HOST, DB_USER, DB_PASS);
if (!$db) {
  die('ERROR: Invalid MySQL connection parameters: ' . mysql_error() . "\n");
}
echo 'passed!' . "\n";
mysql_close($db);

// Now, let's test the OAuth
echo 'Testing OAuth credentials...';
$obj = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_TOKEN_SECRET);
$retval = $obj->get('account/verify_credentials');
if (!is_object($retval) || !isset($retval->name)) {
  die('ERROR: Unable to successfully establish an OAuth connection with Twitter.' . "\n");
}
if ($retval->screen_name != BOT_ACCOUNT) {
  die('ERROR: The BOT_ACCOUNT indicated in configuration differs from what the Twitter API said.' . "\n");  
} else {
  echo 'passed!' . "\n";
}

// Next, test the actions
foreach ($actions as $action) {
  echo 'Checking delay on action ' . $action['identifier'] . '...';
  if (intval($action['delay']) < 0) {
    die('ERROR: Delay given is a negative number, not really possible.' . "\n");
  }
  echo 'passed!' . "\n";
  if (isset($action['class'])) {
    echo 'Custom Action found! Testing proper classfile naming...';
    if (!file_exists('actions' . DIRECTORY_SEPARATOR . strtolower($action['class']) . '.php')) {
      die('ERROR: Unable to find class file for the custom Action.' . "\n");
    }
    echo 'passed!' . "\n";
  }
}

echo 'ALL TESTS PASSED. You should be good to go! Happy Twitterbotting!' . "\n";
?>