PHP Twitterbot
==============

This is designed to be a relatively lightweight, flexible framework for
deploying your own customized and automated twittering bot that more or
less does whatever you program it to do.

Author: Shannon Quinn
Project Wiki: http://www.magsol.me/wiki/index.php5?title=SpamBot
Git repository: https://github.com/magsol/Twitterbot
Version: 2.0 (alpha)

Overview
--------

The general idea here is to provide a framework by which you can implement
as simple or complex a twitterbot as you like without having to worry about
anything but the specific behavior you want to implement. If you want a bot
that does nothing but sample the public timeline, you can practically use
this framework out of the box. If you want a box that reads posts, makes
posts, adds friends, changes its profile, and develops a cure for cancer you
can absolutely do that too (though it may take a bit of work). The point
is you'll only have to focus on implementing that specific behavior; everything
else (in theory :P ) has been taken care of.

The core of this framework is the concept of an "Action": ideally, it
encapsulates a single concrete activity the twitterbot performs (posting,
or direct messaging, or changing background images, etc). In order to create
a new action, you begin by subclassing the Action class.

Say we want our bot to post the current time and a "Hello!" message every hour.
We begin by defining this class as follows:

    require_once(BOTROOT . 'action.php');
    class ClockAction extends Action {

We are then required to implement at least two other methods: a
constructor and a run() method:

    public function __construct($name, $active, $params) {
      $this->name = $name;
      $this->isActive = $active;
      foreach ($params as $k => $v) {
        $this->$k = $v;
      }
      parent::__construct($name, $active, array()); // recommended!
    }

    public function run() {
      // do stuff here that will post hourly update
      // hint: make use of util/TwitterAPI.php

      // if the action completes without any errors,
      // return parent::SUCCESS. otherwise, return parent::FAILURE
      return parent::SUCCESS;
    }

With this basic framework, you can extend it to do just about anything
you'd like. Set $this->frequency in the __construct() method you wrote
to provide a different frequency of your Action firing; or, for even
greater control, override the setNextAttempt() method to completely
redefine the frequency with which your Action fires. You can override
the post_run() method to perform any custom post-Action clean-up or
logging.
 
Put your ClockAction.php file into the actions/ folder (along with
any additional dependencies it may require), and point this Twitterbot
to it by setting up config.php to point to it (details below in the
SETUP section, step 1). Once this is complete, fire up the bot's daemon,
sit back, and let the Twitter trolling begin :)

Notes
-----

*THIS IS STILL VERY MUCH EXPERIMENTAL AND NOT PARTICULARLY ROBUST.* I implemented
the process control without much previous experience in it (most of my experience
is in multithreading with C...not very applicable to this), so it is still very
rough around the edges, particularly with the database connection management.

If you encounter any bugs, please don't hesitate to report them, either to the
github page, or you can email me: magsol at gmail.

Requirements
------------

In order to run this bot, you need:

  - PHP 5.x (with pcntl)
  - PEAR and its basic libraries
  - MySQL 5.x (though will probably work with 4.x)
  - A brave soul

Installation
-----------

1. Fill out the necessary fields in config.php (there are several).
  - BOT_ACCOUNT: The display name for your bot.
  - BOT_PASSWORD: The password to log into your bot (will be removed once Twitter's
    Streaming API is integrated into OAuth...for now, a necessary evil).
  - CONSUMER_KEY: OAuth Consumer Key, obtained by creating an app for this bot.
  - CONSUMER_SECRET: Same as above.
  - OAUTH_TOKEN: Same as above.
  - OAUTH_TOKEN_SECRET: Same as above.
  - DB_NAME: Name of the database this application's data can be stored in.
  - DB_HOST: Host on which the database resides (usually "localhost").
  - DB_USER: Username of the account that has admin access to the DB_NAME database.
  - DB_PASS: Password for the above user.

  Within the $actions array you have optional definitions (required if you want
the bot to do anything interesting) to customize how your bot behaves. If you
leave everything blank, it will simply aggregate posts from Twitter's public
timeline (you'll see them grow quite fast within your database).

  If you choose to add some fields, you'll need to implement your own Action
subclass that performs whatever action of interest you want. Obeying the usual
object-oriented programming guidelines generally makes for better-behaved bots,
but in general you can have your action do whatever you want. Just be sure
to fill in the required fields for any defined Action:
  - name (can be anything, used mainly for debugging)
  - class (case-sensitive name of the class you created)
  - file (case-sensitive name of the PHP file in which your class resides)
  - active (boolean indicating whether or not this action should be fired)
  - args (optional array of arguments, specific to your action)

2. Run the bot's install script.

    php install.php

  This will use the database values defined in your config.php to set up your
database's schema. For optimal behavior, please only fire this script once.
It shouldn't cause any unintended behavior if you execute it multiple times
(it will simply quit if it detects the tables exist already), but why would
you need to run the install script multiple times anyway?

3. Test the bot's settings.

    php run.php --tests-only

  This will have the bot run a battery of tests against the settings you've
indicated. If there are any failures, it will halt immediately and let you
know (wrong database username/password, a missing required field for a custom
Action, etc).

4. Run the bot!

    php run.php

  You can include a -h flag to display all the available options. By default
(run with no arguments), the script will execute the battery of tests like in
step 3 but will, pending all tests passing, start up the bot itself. The bot
will behave as a daemon, detaching itself from the terminal and spawning a child
process for each custom Action defined in config.php. In order to kill the
daemon and its child processes, run the command:

    php run.php --stop

Acknowledgements
----------------

There are several people who made this project possible.

  - [Rob Hall](http://cs.cmu.edu/~rjhall) : the original inspiration, with his
hilarious [postmaster9001 bot](http://twitter.com/postmaster9001), whose awful
Perl hack-job implementation inspired me to make something more flexible
and robust.

  - [Abraham of twitteroauth](https://github.com/abraham/twitteroauth) : His library
makes the implementation of OAuth authentication in this project possible.
That's one huge black box that is reduced to one or two lines of code on my
part to worry about. Awesome, awesome work.

  - [Fennb of Phirehose](https://github.com/fennb/phirehose) : Again, a huge, rich,
robust API that makes my life really freaking easy. This makes data aggregation
possible in this project, enabling me to spend my time doing interesting things
with the data rather than finding ways of crawling Twitter. Keep up the good
work.

  - [Sebastian Bergmann of PHPUnit](https://github.com/sebastianbergmann/phpunit/) :
Author of a phenomenal unit-testing framework for PHP, it's still on my to-do
list to incporporate it into this bot's testing. It will definitely happen,
and this guy has made it a lot easier.

  - George Schlossnagle of "Advanced PHP Programming", from which I learned just
about every trick in this project for process management, particularly in
setting up the structure for the Twitterbot and Action classes. It was a
phenomenal resource and made the daemon aspect of this project feasible.
