<?php

class FaGearmanClientBase extends FaGearman {

  protected $client;


  public function __construct() {
    if (empty($this->getServers())) {
       $this->setServers();
    }

    fa_gearman_log('Initiating Gearman Client...');

    $this->client = new GearmanClient();
    $this->client->addServers($this->getServers());
  }

  public function processClient() {
    $queues = QUEUES;
    $queues = json_decode($queues, true);
    foreach($queues as $queueName) {
      $this->processAwsQueue($queueName);
    }

    sleep(1);
    $this->processClient();
  }

  public function processAwsQueue($queueName) {
    $this->processParallelAwsQueueClient($queueName);
  }

  protected function processParallelAwsQueueClient($queueName) {
    for ($i = 0; $i < PARALLEL_AWS_CLIENT; $i++) {
      $this->processAwsQueueClient($queueName);
    }
  }

  protected function processAwsQueueClient($queueName) {
    $this->initiateAwsQueue($queueName);

    fa_gearman_log("Waiting for task from Aws Queue $queueName ...");
    $datus = $this->awsClient->claimItem();

    if (!empty($datus)) {
      $this->processDataWithPriority($datus);
    }
  }

}
