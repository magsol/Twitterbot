<?php

include_once('config.php');
include_once('storage.php');
include_once('action.php');

function main() {
  global $actions;
  
  // loop through all the actions as specified in the config file
  $objects = array();
  foreach ($actions as $action) {
    
    // step 1: default or custom action?
    $obj = null;
    if (isset($action['class'])) {
      include_once(ACTIONS . strtolower($action['class'] . '.php'));
      $obj = new $action['class']($action);
    } else {
      $obj = new Action($action);
    }
    // step 2: run the action
    $obj->doAction();
  }
}

// invoke the main method
main();

?>