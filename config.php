<?php

defined('TWITTERBOT') or die('Restricted.');

// OAuth Authentication variables - DO NOT SHARE THESE
define('TWITTER_CONSUMER_KEY', 'your consumer key here');
define('TWITTER_CONSUMER_SECRET', 'your consumer secret here');
define('OAUTH_TOKEN', 'your oauth_token here');
define('OAUTH_TOKEN_SECRET', 'your oauth_token_secret here');

// actions to take whenever the cron is run
$actions = array(

  // for each action, define an array with the following elements:
  /*
  array('name' => 'Name of your custom action',     // Can be anything you want!

        'class' => 'CaseSensitiveNameOfClass',      // The case-sensitive name
                                                    // of the action class you
                                                    // wrote.

        'file' => 'name_of_php_file.php',           // The name of the PHP file
                                                    // (with the .php extension)
                                                    // of the file containing
                                                    // your custom action class.

        'active' => true | false,                   // Determines whether this
                                                    // action will run or not.
                                                    // If true, this action will
                                                    // execute. If false, it
                                                    // will be ignored.

        'args' => array('Any additional arguments'),// An array of any
                                                    // additional arguments
                                                    // specific to your custom
                                                    // action.
  ),
  */
);

// change the following values to connect to your database
define('DB_NAME', 'your db name here');         // name of the database itself
define('DB_HOST', 'your db host here');         // usually 'localhost'
define('DB_USER', 'your db user here');         // username to connect
define('DB_PASS', 'your db pass here');         // password for the username

// ----------------------------------------------------------- //
// -- That's it! Don't change anything else below this line -- //
// ----------------------------------------------------------- //

define('TWITTERBOT_LOCKFILE', '/tmp/.bot_lockfile');
define('BOTROOT', __DIR__);
define('ACTIONS', BOTROOT . DIRECTORY_SEPARATOR . 'actions' .
  DIRECTORY_SEPARATOR);
define('UTIL', BOTROOT . DIRECTORY_SEPARATOR . 'util' . DIRECTORY_SEPARATOR);
define('OAUTH', BOTROOT . DIRECTORY_SEPARATOR . 'twitteroauth' .
  DIRECTORY_SEPARATOR);
define('PHIREHOSE', BOTROOT . DIRECTORY_SEPARATOR . 'phirehose' .
  DIRECTORY_SEPARATOR);
define('POST_TABLE', 'posts');
define('LOG_TABLE', 'logs');

?>
