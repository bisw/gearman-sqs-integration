<?php

class FaGearmanClient extends FaGearmanClientBase {

  protected function processDataWithPriority($datus) {
    $res = [];
    foreach($datus as $queue => $data) {
      switch ($queue) {
        case 'cron':
          if (!empty($data)) {
            $res = $this->addBackgroundTask('addTaskBackground', 'pushAwsSqsDataIntoGearmanQueue', $data);
          }

          break;

        case 'transactional':
          if (!empty($data)) {
            $res = $this->addBackgroundTask('addTaskHighBackground', 'pushAwsSqsDataIntoGearmanQueue', $data);
          }

          break;

        default:
          if (!empty($data)) {
            $res = $this->addBackgroundTask('addTaskBackground', 'pushAwsSqsDataIntoGearmanQueue', $data);
          }

          break;
      }
    }

    return $res;
  }

  public function addTask($function, $datus) {
    do {

      // Register different callbacks.
      $this->client->setCreatedCallback([$this, 'callbackForTaskCreation']);
      $this->client->setDataCallback([$this, 'callbackForTaskDataProcess']);
      $this->client->setStatusCallback([$this, 'callbackForTaskStatus']);
      $this->client->setCompleteCallback([$this, 'callbackForTaskComplete']);
      $this->client->setFailCallback([$this, 'callbackForTaskFailure']);

      // Add multiple task to process in parallel.
      foreach ($datus as $data) {
        $data = json_encode($data);
        $task = $this->client->addTask($function, $data);
      }

      // Run the tasks in parallel (assuming multiple workers)
      if (!$this->client->runTasks()) {
        fa_gearman_log('Errors: ' . $this->client->error(), 'error');
      }

      fa_gearman_log('Tasks are completed.');

    } while(!$this->client->runTasks());

  }

  public function addBackgroundTask($callback, $registerFunc, $datus) {

    do {

      // Add multiple task to process in parallel.
      foreach ($datus as $data) {
        $data = json_encode($data);
        $task = $this->client->{$callback}($registerFunc, $data);
      }

      // Run the tasks in parallel (assuming multiple workers)
      if (!$this->client->runTasks()) {
        fa_gearman_log('Errors: ' . $this->client->error(), 'error');
      }

      // Check for various return packets and errors.
      $this->showTaskCompleteMsg($this->client->returnCode());
      fa_gearman_log('Tasks are completed.');

    } while($this->client->returnCode() != GEARMAN_SUCCESS);

  }

  function callbackForTaskCreation($task) {
    fa_gearman_log('Task: ' . $task->jobHandle() . ' is created');
  }

  function callbackForTaskDataProcess($task) {
    fa_gearman_log('Task data: ' . $task->data());
  }

  function callbackForTaskStatus($task) {
    $data = 'Task Status: ' . $task->jobHandle() . ' - ' . $task->taskNumerator() . '/' . $task->taskDenominator() . '.';

    fa_gearman_log($data);
  }

  function callbackForTaskComplete($task) {
    fa_gearman_log('Task is completed: ' . $task->jobHandle());
    $itemId = $task->data();

    $this->awsClient->deleteItem($itemId);
  }

  function callbackForTaskFailure($task) {
    fa_gearman_log('Task is failed: ' . $task->jobHandle() . ', ' . $task->data());
  }

}
