<?php

class FaMail extends FaServices {
  private $url;
  private $api_user;
  private $api_key;

  public function getUrl() {
    return $this->url;
  }

  private function setUrl() {
    return $this->url = 'https://sendgrid.com/api/mail.send.json';
  }

  private function setAccount($name = 'default') {
    $accounts = MAIL_ACOUNTS;
    $accounts = json_decode($accounts, true);
    $account = isset($accounts[$name]) ? $accounts[$name] : $accounts['default'];

    if (!empty($account['user'])) {
      $this->setApiUser($account['user']);
    } else {
      throw new Exception('Sendgrid account: ' . $name . ' user is not set.', 1);
    }

    if (!empty($account['key'])) {
      $this->setApiKey($account['key']);
    } else {
      throw new Exception('Sendgrid account: ' . $name . ' key is not set.', 1);
    }
  }

  public function getApiUser() {
    return $this->api_user;
  }

  private function setApiUser($user) {
    return $this->api_user = $user;
  }

  public function getApiKey() {
    return $this->api_key;
  }

  private function setApiKey($key) {
    return $this->api_key = $key;
  }

  public function __construct() {
    fa_gearman_log('Initiating email sending process...');

    $this->setUrl();
  }

  public function processEmailData($message) {
    $attr = isset($data['item_attributes']) ? $data['item_attributes'] : [];
    $accountName = isset($attr['mail_account']['StringValue']) ? $data['mail_account']['StringValue'] : 'default';
    $data = $message['data'];

    $this->setAccount($accountName);
    $params = [
      'api_user' => $this->getApiUser(),
      'api_key' => $this->getApiKey(),
    ];

    $params += $data;

    $options = [
      'method' => 'POST',
      'data' => http_build_query($params),
      'timeout' => 10,
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
    ];

    $result = $this->faHttpRequest($this->getUrl(), $options);

    return $result;
  }

  public function sendEmail($message) {
    $accountName = isset($message['mail_account']) ? $message['mail_account'] : 'default';
    $data = $message['data'];

    $this->setAccount($accountName);
    $params = [
      'api_user' => $this->getApiUser(),
      'api_key' => $this->getApiKey(),
    ];

    $params += $data;

    $options = [
      'method' => 'POST',
      'data' => http_build_query($params),
      'timeout' => 10,
      'headers' => [
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
    ];

    $result = $this->faHttpRequest($this->getUrl(), $options);

    return $result;
  }

}
