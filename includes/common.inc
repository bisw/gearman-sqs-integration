<?php

function fa_gearman_timer_start($name) {
  global $timers;

  $timers[$name]['start'] = microtime(TRUE);
  $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
}

/**
 * Reads the current timer value without stopping the timer.
 *
 * @param $name
 *   The name of the timer.
 *
 * @return
 *   The current timer value in ms.
 */
function fa_gearman_timer_read($name) {
  global $timers;

  if (isset($timers[$name]['start'])) {
    $stop = microtime(TRUE);
    $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

    if (isset($timers[$name]['time'])) {
      $diff += $timers[$name]['time'];
    }
    return $diff;
  }
  return $timers[$name]['time'];
}

function _fa_gearman_parse_response_status($response) {
  $response_array = explode(' ', trim($response), 3);
  // Set up empty values.
  $result = array(
    'reason_phrase' => '',
  );
  $result['http_version'] = $response_array[0];
  $result['response_code'] = $response_array[1];
  if (isset($response_array[2])) {
    $result['reason_phrase'] = $response_array[2];
  }
  return $result;
}

function fa_gearman_log($content, $type = 'success') {
  $filename = '/var/log/fa-gearman/fa-gearman-log_' . $type . '_' . date("Y-m-d");
  $log_data = date("Y-m-d H:i:s") . ' :-->   ' . $content . "\n";

  file_put_contents($filename, $log_data, FILE_APPEND);
}

function fa_gearman_show_message($data) {
  echo 'Log: ' . $data . "\n";
}

function fa_gearman_get_queue_type($queue) {
  $queues = QUEUES;
  $queues = json_decode($queues, true);
  foreach ($queues as $queueType => $queueName) {
    if ($queue === $queueName) {
      return $queueType;
    }
  }

  return FALSE;
}
