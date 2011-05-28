<?php

define('TWITTERBOT', 1);

// script for installing the twitterbot's SQL back-end
// NOTE: only run this AFTER setting up config.php!
include_once('config.php');
include_once('action.php');
include_once('storage.php');

// first, set up any tables needed for the core install
$db = Storage::getDatabase();
$sql = 'CREATE TABLE IF NOT EXISTS `' . DB_NAME . '`.`' . POST_TABLE . '` (' .
        '`postid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,' .
        '`text` VARCHAR( 140 ) NOT NULL ,' .
        '`user` VARCHAR( 50 ) NOT NULL ,' .
        '`date_saved` DATETIME NOT NULL ,' .
        '`modeled` SMALLINT NOT NULL DEFAULT \'0\')';
$db->setQuery($sql);
$db->query();
$sql = 'CREATE TABLE IF NOT EXISTS `' . DB_NAME . '`.`' . LOG_TABLE . '` (' .
        '`eventid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,' .
        '`eventtime` DATETIME NOT NULL ,' .
        '`message` TEXT NOT NULL)';
$db->setQuery($sql);
$db->query();
?>