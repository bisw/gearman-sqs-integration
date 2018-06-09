<?php

class FaGearman extends FaServices {

  protected $servers;
  protected $awsClient;


  public function getServers() {
    return $this->servers;
  }

  public function getAwsClient() {
    $this->awsClient;
  }

  protected function setServers($servers = NULL) {
    return $this->servers = $servers ? $servers : SERVERS;
  }

  protected function setAwsClient($clientName) {
    fa_gearman_log('Initiating AWS Client...');

    $this->awsClient = FaAws::createClient($clientName);
  }

  protected function initiateAwsQueue($queueName) {
    $this->setAwsClient('Sqs');

    fa_gearman_log("Initiating AWS Queue $queueName ...");
    $this->awsClient->getSqsQueueUrl($queueName);
  }

  public function showTaskCompleteMsg($code) {
    switch($code) {
      case GEARMAN_SUCCESS:
        $msg = 'Successful';
        break;

      case GEARMAN_IO_WAIT:
        $msg = 'Waiting';
        break;

      case GEARMAN_SHUTDOWN:
        $msg = 'Closed';
        break;

      case GEARMAN_NO_JOBS:
        $msg = 'No Job';
        break;
        
      default:
        $msg = 'Let me figure it out with code: ' . $code;
        break;
    }

    fa_gearman_log('Jod status: ' . $msg);
  }

}
