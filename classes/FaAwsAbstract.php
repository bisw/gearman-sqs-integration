<?php

/**
 * @file
 * Base abstract class which config global attributes and functions.
 */

abstract class FaAwsAbstract {

  protected $secret;
  protected $key;
  protected $version;
  protected $region;

  /**
   * This should be public so other any external class use our aws client.
   */
  public $client;


  /**
   * Make sure to define setClient() function whereever this class will be extended.
   */
  abstract protected function setClient();

  /**
   * Initiate aws configuration and make sure all good to go.
   */
  protected function initiateConfig() {
    $this->setKey();
    $this->setSecret();
    $this->setRegion();
    $this->setVersion();

    // Check if keys are available.
    if (!$this->getKey() || !$this->getSecret()) {
      fa_gearman_log('AWS Credentials not found', 'error');
      throw new Exception('AWS Credentials not found');
    }

    // Check if region is available.
    if (!$this->getRegion()) {
      fa_gearman_log('AWS region is not set.', 'error');
      throw new Exception('AWS region is not set.');
    }

    // Check if version is available.
    if (!$this->getVersion()) {
      fa_gearman_log('AWS version is not set.', 'error');
      throw new Exception('AWS version is not set.');
    }
  }

  public function getClient() {
    return $this->client;
  }

  protected function setKey($key = '') {
    $this->key = !empty($key) ? $key : AWS_KEY;
  }

  public function getKey() {
    return $this->key;
  }

  protected function setSecret($secret = '') {
    $this->secret = !empty($secret) ? $secret : AWS_SECRET_ID;
  }

  public function getSecret() {
    return $this->secret;
  }

  protected function setRegion($region = 'ap-south-1') {
    $this->region = $region;
  }

  public function getRegion() {
    return $this->region;
  }

  protected function setVersion($version = 'latest') {
    $this->version = $version;
  }

  public function getVersion() {
    return $this->version;
  }
}
