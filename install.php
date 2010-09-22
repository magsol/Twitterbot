<?php

// script for installing the twitterbot's SQL back-end
// NOTE: only run this AFTER setting up config.php!
include_once('config.php');
include_once('action.php');

// first, set up any tables needed for the core install

// next, go through all the custom actions (if they exist) performing
// the implemented installations of each
foreach ($actions as $action) {
  if (isset($action['class'])) {
    // first, include the class
    include_once(ACTIONS . strtolower($action['class']) . '.php');
    
    // perform the install method
    call_user_func(array($action['class'], 'install'), $action['identifier']);
  }
}



?>