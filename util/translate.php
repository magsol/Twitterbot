<?php

defined('TWITTERBOT') or die('Restricted.');

/**
 * This class handles sending some twitter post off to Google
 * Translate to be, well, translated. This is a utility class
 * and not an action unto itself, in case other actions wish
 * to use this class.
 *
 * NOTE: THE GOOGLE TRANSLATE API HAS BEEN DEPRECATED. THIS
 * FUNCTIONALITY WILL NO LONGER WORK UNLESS YOU HAVE A PAID
 * SUBSCRIPTION TO THE SERVICE.
 *
 * @author Shannon Quinn
 */
class Translate {

  public function __construct() {}

  /**
   * Passes the given text off to Google Translate, and returns
   * the text as translated into the specified language.
   * @static
   * @param string $text Text to be translated
   * @param string $targetlanguage Target translation language
   * @param string $key The Google API key to use
   * @return string The translated text.
   */
  public static function translate($text, $targetlanguage, $key) {
    $ch = curl_init();
    $options = array(CURLOPT_URL =>
      'https://www.googleapis.com/language/translate/v2?key=' .
      $key . '&target=' . $targetlanguage . '&q=' . urlencode($text),
      CURLOPT_RETURNTRANSFER => true);
    curl_setopt_array($ch, $options);
    $retval = json_decode(curl_exec($ch));
    curl_close($ch);
    if (isset($retval->error) ||
      $retval->data->translations[0]->detectedSourceLanguage == 'en') {
      return $text;
    } else {
      // do a little clean-up on the @ tags
      $matches = preg_match_all('/@([A-Za-z0-9_]+)/', $text, $usernames);
      $translated = $retval->data->translations[0]->translatedText;
      if ($matches == 0 || $matches === false) {
        return $translated;
      }
      foreach ($usernames[1] as $index => $username) {
        $pos = strpos($translated, $username);
        $translated = substr_replace($translated, '@', $pos, 0);
      }
      return str_replace('@ ', '', $translated);
    }
  }
}

?>
