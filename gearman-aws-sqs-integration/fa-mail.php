<?php

define('GEARMAN_ROOT', __DIR__);

require GEARMAN_ROOT . '/config.php';
require_once GEARMAN_ROOT . '/fa_autoload.php';

$service = isset($argv[1]) ? $argv[1] : '';
$restarted = isset($argv[2]) ? $argv[2] : 0;
$hostname = gethostname();
$date = date("Y-m-d H:i:s");
$subject = 'Gearman server: ';

if ($restarted) {
	 $subject .= $service . ' was down -but restarted- on ' . $hostname . ' at ' . $date;
	 $message = $service . ' was down, but I was able to restart it on ' . $hostname . ' at ' . $date;
} else {
	 $subject .= $service . ' down on ' . $hostname . ' at ' . $date;
	 $message = $service . ' is down on ' . $hostname . ' at ' . $date . '. I tried to restart it, but it did not work.';
}

$options = [
  'to' => ['biswajit.mondal@bakingo.com'],
  'subject' => $subject,
  'fromname' => 'FlowerAua Gearman',
  'from' => 'wecare@floweraura.in',
  'html' => $message
];

$data = [
  'data' => $options,
  'mail_account' => 'sendgrid'
];

$mail = new FaMail();
$mail->sendEmail($data);
