<?php

/**
 * @file
 * This is the front controller class which will be used to get any aws client.
 */

include_once GEARMAN_ROOT . '/vendor/autoload.php';

class FaAws {

  public static function createClient($type) {
    $clientType = 'FaAws' . $type;

    if (self::validClient($type) && class_exists($clientType)) {
      return new $clientType();
    }
  }

  public static function validClient($type) {
    $clients = ['Sqs', 'CloudFront', 'Ses'];
    return in_array($type, $clients) ? $type : NULL;
  }
}
