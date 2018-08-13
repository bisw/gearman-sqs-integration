<?php

class FaGearmanWorkerBase extends FaGearman {

  protected $worker;


  public function __construct() {
    if (empty($this->getServers())) {
       $this->setServers();
    }

    fa_gearman_log('Initiating Gearman Workers...');

    $this->worker = new GearmanWorker();
    $this->worker->addServers($this->getServers());

    fa_gearman_log('Initiation completed');
  }

  public function processWorker() {
    $this->worker->addFunction('pushAwsSqsDataIntoGearmanQueue', [$this, 'processGearmanQueueData']);

    fa_gearman_log('Waiting for job...');
    while($task = $this->worker->work()) {
      $this->showTaskCompleteMsg($this->worker->returnCode());
    }
  }

}
