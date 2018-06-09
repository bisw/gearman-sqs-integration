<?php

class FaGearmanWorker extends FaGearmanWorkerBase {

  public function processGearmanQueueData($job) {
    fa_gearman_log('Received job: ' . $job->handle());

    $workload = $job->workload();
    $data = json_decode($workload, true);
    $queueName = fa_gearman_get_queue_type($data['queue']);

    switch($queueName) {
      case 'cron':
        $response = $this->processCronData($data);
        break;

      case 'transactional':
        $response = $this->processTransactionalData($data);
        break;

      default;
        $response = $this->processCronData($data);
        break;
    }

    if ($response && !empty($data['queue']) && !empty($data['item_id'])) {
      $this->initiateAwsQueue($data['queue']);

      fa_gearman_log('Deleting item from Queue');
      $this->awsClient->deleteItem($data['item_id']);
    } elseif (!empty($data['queue']) && !empty($data['item_id'])) {
      $this->initiateAwsQueue($data['queue']);

      fa_gearman_log('Data is not processed, so make the item invisible for ' . QUEUE_VISIBILITY_TIMEOUT . ' seconds.');
      $this->awsClient->releaseItem($data['item_id']);
    } else {
      fa_gearman_log('Processing empty item, please check.');
    }

    fa_gearman_log('Job is finished');
    return $response;
  }

  protected function processApiData($data) {
    $res = FALSE;
    if (!empty($data['api_url']) && !empty($data['options'])) {
      $url = $data['api_url'];
      $options = $data['options'];
      $response = $this->faHttpRequest($url, $options);

      if (isset($response->code) && $response->code == 200) {
        return TRUE;
      }

      if (isset($response->data)) {
        fa_gearman_log($response->data, 'error');
      }
    }

    return FALSE;
  }

  public function processCronData($itemData) {
    $data = $itemData['data'];
    $attrs = $itemData['item_attributes'];
    $log = 'Queue Name: ' . $itemData['queue'];
    foreach ($attrs as $attr_name => $attr) {
      $log .= ', ' . $attr_name . ': ' . $attr['StringValue'];
    }

    if ($this->processApiData($data)) {
      fa_gearman_log($log);
      return TRUE;
    } else {
      fa_gearman_log($log . "\n", 'error');
      return FALSE;
    }
  }

  public function processTransactionalData($itemData) {
    $data = $itemData['data'];
    $attrs = $itemData['item_attributes'];
    $log = 'Queue Name: ' . $itemData['queue'];
    foreach ($attrs as $attr_name => $attr) {
      $log .= ', ' . $attr_name . ': ' . $attr['StringValue'];
    }

    if ($this->processApiData($data)) {
      fa_gearman_log($log);
      return TRUE;
    } else {
      fa_gearman_log($log . "\n", 'error');
      return FALSE;
    };
  }



  public function processTransactionalDataTest($data) {
    $res = FALSE;
    $cat = isset($data['item_attributes']['queue_category']['StringValue']) ? $data['item_attributes']['queue_category']['StringValue'] : 'default';

    switch ($cat) {
      case 'email':
        $res = $this->initiateEmailProcessing($data);
        break;

      case 'bitout':
        $res = $this->initiateBitoutProcessing($data);
        break;

      case 'sms':
        $res = $this->initiateSmsProcessing($data);
        break;

      default;
        $res = $this->initiateEmailProcessing($data);
        break;
    }

    return $res;
  }

  protected function initiateEmailProcessing($data) {
    $email = new FaMail();
    $response = $email->processEmailData($data);
    if (isset($response->code) && $response->code == 200) {
      return $data['item_id'];
    }

    return FALSE;
  }

}
