<?php

/**
 * Since the default action setup in the config doesn't write anything
 * to a file, this class is necessary (particularly since we're going
 * to write posts to *two* files)
 * @author squinn
 *
 */
class PublicTimelineAction extends Action {
  
  /**
   * @see Action::doAction()
   */
  public function doAction() {
    // first, perform the action of hitting the public timeline
    $this->result = Action::$oauth->get($this->call, $this->args);

    // save all the posts
    $this->processRawTimeline();
  }
  
  /**
   * Installs the table for this action.
   * @see Action::install()
   */
  public static function install($name) {
    $sql = 'CREATE TABLE IF NOT EXISTS `' . DB_NAME . '`.`' . $name . '` (' .
            '`postid` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,' . 
            '`post` VARCHAR( 140 ) NOT NULL ,' . 
            '`user` VARCHAR( 50 ) NOT NULL ,' . 
            '`modeled` SMALLINT NOT NULL)';
    $db = Storage::getDatabase();
    $db->setQuery($sql);
    $db->query();
  }
  
  /**
   * Helper method. Processes the raw timeline results and returns an
   * array of sentences.
   * @param object $t
   * @return array
   */
  private function processRawTimeline() {
    $db = Storage::getDatabase();
    $numposts = count($this->result);
    for ($i = 0; $i < $numposts; $i++) {
      // get the text and the user who posted it
      $text = str_replace("\n", '', $this->result[$i]->text);
      $user = $this->result[$i]->user->screen_name;
      
      // save them
      $query = 'INSERT INTO ' . $this->name . ' (post, user, modeled) VALUES ' .
        '("' . mysql_real_escape_string($text) . '", "' . 
        mysql_real_escape_string($user) . '", 0)';
      $db->setQuery($query);
      $db->query(); 
    }
  }
}

?>