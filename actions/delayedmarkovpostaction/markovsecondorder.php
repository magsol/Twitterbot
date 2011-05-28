<?php

/**
 * Implements a second-order Markov chain, effectively a trigram model. 
 * Formally, this means:
 * 
 * P(some word | word1, word2)
 * 
 * @author Shannon Quinn
 */
class MarkovSecondOrder {
  
  private $hash;
  
  public function __construct() {
    $this->hash = array();
  }
  
  /**
   * Adds a new word to the model, given the two previous words
   * @param string $word1
   * @param string $word2
   * @param string $next
   */
  public function add($word1, $word2, $next) {
    if (isset($this->hash[$word1])) {
      // the first level exists, how about the second?
      if (isset($this->hash[$word1][$word2])) {
        $this->hash[$word1][$word2][$next] = (isset($this->hash[$word1][$word2][$next]) ? 
          $this->hash[$word1][$word2][$next] + 1 : 1);
      } else {
        $this->hash[$word1][$word2] = array();
        $this->hash[$word1][$word2][$next] = 1;
      }
    } else {
      $this->hash[$word1] = array();
      $this->hash[$word1][$word2] = array();
      $this->hash[$word1][$word2][$next] = 1;
    }
  }
  
  /**
   * Returns the next word in the distribution, given the current word.
   * @param string $firstword
   * @param string $secondword
   * @return string A word sampled from the distribution P(word | $firstword, $secondword)
   */
  public function get($firstword, $secondword) {
    // does this even exist?
    if (!isset($this->hash[$firstword]) || !isset($this->hash[$firstword][$secondword])) {
      return '';
    }
    
    // how many words are there in this sequence of initial tokens?
    $wordarr = $this->hash[$firstword][$secondword];
    $totalwords = array_sum($wordarr);
    
    // generate a random number in this range
    $sample = mt_rand(1, $totalwords);
    
    // loop over the model, stopping once we've exceeded our random number,
    // which corresponds to the token we want to sample
    $sum = 0;
    foreach ($wordarr as $word => $count) {
      $sum += $count;
      if ($sum >= $sample) {
        return $word;
      }
    }
    // should NEVER reach this point
  }
}