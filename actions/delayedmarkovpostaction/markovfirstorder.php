<?php
 
/**
 * Implements a first-order Markov chain. Formally, this means:
 * 
 * P(some word | word1)
 * 
 * @author Shannon Quinn
 */
class MarkovFirstOrder {
 
  private $hash;
 
  public function __construct() {
    $this->hash = array();
  }
 
  /**
   * Adds a new word and the word following it to the model (first order)
   * @param word
   * @param next
   */
  public function add($word, $next) {
    // first, does the word already exist?
    if (isset($this->hash[$word])) {
      $this->hash[$word][$next] = (isset($this->hash[$word][$next]) ?
        $this->hash[$word][$next] + 1 : 1);
    } else {
      $this->hash[$word] = array();
      $this->hash[$word][$next] = 1;
    }
  }
 
  public function debug() {
    print_r($this->hash);
  }
 
  /**
   * Returns the next word from the distribution, given the current word.
   * @param word
   * @return
   */
  public function get($word) {
    // first, does the word even exist?
    if (!isset($this->hash[$word])) {
      return '';
    }
    $subarr = $this->hash[$word];
    // calculate the sum of the counts of the next possibilities
    $sum = array_sum($subarr);

    // generate a random number in this range
    $rand = mt_rand(1, $sum);
 
    // loop again, this time stopping once the counts have
    // reached the random number we generated, then return
    // that word
    $sum = 0;
    foreach ($subarr as $w => $count) {
      $sum += $count;
      if ($sum >= $rand) {
        return $w;
      }
    }
  }
}
 
?>
