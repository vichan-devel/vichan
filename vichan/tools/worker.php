#!/usr/bin/php
<?php
/* worker.php - part of advanced build vichan feature */

require dirname(__FILE__) . '/inc/cli.php';
require_once 'inc/controller.php';

$config['smart_build'] = false; // Let's disable it, so we can build the page for real
$config['generation_strategies'] = array('strategy_immediate');

function after_open_board() { global $config;
  $config['smart_build'] = false;
  $config['generation_strategies'] = array('strategy_immediate');
};

echo "Hello world!\n";

$queue = get_queue('generate');

while (true) {
  $q = $queue->pop(2);
  foreach ($q as $v) {
    list($__, $func, $ary, $action) = unserialize($v);
    echo "Starting to generate $func ".implode(" ", $ary)."... ";

    call_user_func_array($func, $ary);

    echo "done!\n";
  }
  if (!$q) usleep(20000); // 0.02s
}
