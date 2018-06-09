<?php

use Aws\Sqs\SqsClient;
use Aws\Ses\Exception\SesException;

/**
 * Amazon queue.
 */
class FaAwsSqs extends FaAwsAbstract {

  private $queueName;
  private $queueUrl;


  public function __construct() {
    // Initiate global setting.
    $this->initiateConfig();

    // Initiate aws client.
    $this->setClient();
  }

  protected function setClient() {
    try {

      $data = $this->client = SqsClient::factory([
        'version'=> $this->getVersion(),
        'region' => $this->getRegion(),
        'credentials' => [
          'key' => $this->getKey(),
          'secret'  => $this->getSecret(),
        ]
      ]);
    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }
  }

  public function getSqsQueueUrl($name) {
    try {
      $queue = [
        'QueueName' => $name
      ];

      if (!empty($queue['QueueName']) && $queueUrl = $this->client->GetQueueUrl($queue)) {
        return $this->createQueue($name);
      } else {
        fa_gearman_log("Requesting queue is not exists, please create it on AWS SQS and get back.", 'error');
        throw new Exception("Requesting queue is not exists, please create it on AWS SQS and get back.", 1);
      }
    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }

    return FALSE;
  }

  public function createQueue($name) {
    try {

      $this->setQueueName($name);
      $result = $this->client->createQueue(array('QueueName' => $this->getQueueName()));
      $queueUrl = $result->get('QueueUrl');
      $this->setQueueUrl($queueUrl);
      return $queueUrl;

    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }

    return FALSE;
  }

  public function createItem($data, $type = 'product') {
    try {

      // Encapsulate our data
      $serialized_data = $this->serialize($data);

      // Check to see if someone is trying to save an item originally retrieved
      // from the queue. If so, this really should have been submitted as
      // $item->data, not $item. Reformat this so we don't save metadata or
      // confuse item_ids downstream.
      if (is_object($data) && property_exists($data, 'data') && property_exists($data, 'item_id')) {
        $text = t('Do not re-queue whole items retrieved from the SQS queue. This included metadata, like the item_id. Pass $item->data to createItem() as a parameter, rather than passing the entire $item. $item->data is being saved. The rest is being ignored.');
        $data = $data->data;
        fa_gearman_log($text);
      }

      // @todo Add a check here for message size? Log it?
      //$this->setQueueGroup($group);

      // Create a new message object
      $result = $this->client->sendMessage(array(
        'QueueUrl' => $this->getQueueUrl(),
        'MessageBody' => $serialized_data,
        'MessageAttributes' => [
          'cron_time' => [
            'DataType' => 'String',
            'StringValue' => $type
          ]
        ],
      ));

      return (bool) $result;
    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }

    return FALSE;
  }

  public function claimItem($lease_time = 0) {
    try {

      $response = $this->client->receiveMessage(array(
        'QueueUrl' => $this->getQueueUrl(),
        'MaxNumberOfMessages' => 10,
        'VisibilityTimeout' => MESSAGE_INFLIGHT_TIMEOUT,
        //'WaitTimeSeconds' => 20,
        'AttributeNames' => ['ApproximateNumberOfMessages'],
        'MessageAttributeNames' => ['*']
      ));

      // @todo Add error handling, in case service becomes unavailable.
      $messages = isset($response['Messages']) ? $response['Messages'] : [];

      $items = [];
      foreach ($messages as $message) {
        $item = [];
        $item['data'] = $this->unserialize($message['Body']);
        $item['item_id'] = $message['ReceiptHandle'];
        $item['item_attributes'] = $message['MessageAttributes'];
        $item['queue'] = $this->getQueueName();
        $items[$item['queue']][] = $item;
      }

      return $items;
    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }

    return FALSE;
  }

  public function deleteItem($item_id) {
    try {

      if (empty($item_id)) {
        fa_gearman_log("An item that needs to be deleted requires a handle ID", 'error');
      }

      if (empty($this->getQueueUrl())) {
        $msg = __CLASS__. '-' . __FUNCTION__ . '---' . "Queue url is not set, so queue is not initiated.";
        fa_gearman_log($msg, 'error');
        throw new Exception($msg);
      }

      $result = $this->client->deleteMessage(array(
        'QueueUrl' => $this->getQueueUrl(),
        'ReceiptHandle' => $item_id,
      ));

    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }
  }

  public function releaseItem($item_id) {
    try {

      $result = $this->client->changeMessageVisibility(array(
        'QueueUrl' => $this->getQueueUrl(),
        'ReceiptHandle' => $item_id,
        'VisibilityTimeout' => QUEUE_VISIBILITY_TIMEOUT,
      ));
    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }
  }

  public function deleteQueue() {
    try {

      if ($this->getQueueUrl()) {
        $result = $this->client->deleteQueue(array('QueueUrl' => $this->getQueueUrl()));
      } else {
        $msg = __CLASS__. '-' . __FUNCTION__ . '---' . "Queue url is not set, so queue is not initiated.";
        fa_gearman_log($msg, 'error');
        throw new Exception($msg);
      }

    } catch(SesException $error) {
      fa_gearman_log(__CLASS__. '-' .__FUNCTION__. '---' . $error->getMessage(), 'error');
    }
  }

  protected static function serialize($data) {
    return json_encode($data);
  }

  protected static function unserialize($data) {
    return json_decode($data, TRUE);
  }

  public function getQueueName() {
    return $this->queueName;
  }

  private function setQueueName($name) {
    $this->queueName = $name;
  }

  public function getQueueUrl() {
    return isset($this->queueUrl) ? $this->queueUrl : '';
  }

  private function setQueueUrl($queueUrl) {
    $this->queueUrl = $queueUrl;
  }
}
