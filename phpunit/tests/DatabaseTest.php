<?php

require_once('PHPUnit' . DIRECTORY_SEPARATOR . 'Extensions' . DIRECTORY_SEPARATOR . 
              'Database' . DIRECTORY_SEPARATOR . 'TestCase.php');

class DatabaseTest extends PHPUnit_Extensions_Database_TestCase {
  
  protected function getConnection() {
    // see http://www.phpunit.de/manual/current/en/database.html
    return null;
  }
  
  protected function getDataSet() {
    return $this->createXMLDataSet('..' . DIRECTORY_SEPARATOR . 'data' . 
      DIRECTORY_SEPARATOR . 'testDelayedMarkovPostAction.xml');
  }
  
}

?>