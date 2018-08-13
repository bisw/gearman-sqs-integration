<?php

spl_autoload_register(function ($class_name) {
  require_once GEARMAN_ROOT . '/classes/' . $class_name . '.php';
});

$include_files = [
  'common.inc'
];

foreach ($include_files as $file) {
	 require_once GEARMAN_ROOT . '/includes/' . $file;
}
