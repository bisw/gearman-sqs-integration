<?php

define('SERVERS', '127.0.0.1:4730');

// Aws settings.
define('AWS_KEY', 'aws_key');
define('AWS_SECRET_ID', 'aws_secret');
define('PARALLEL_AWS_CLIENT', 5);
define('QUEUE_VISIBILITY_TIMEOUT', 7200);
define('MESSAGE_INFLIGHT_TIMEOUT', 1800);

$queues = [
  'transactional' => 'aws_sqs_queue',
  'cron' => 'aws_sqs_queue'
];
$queues = json_encode($queues);
define('QUEUES', $queues);
