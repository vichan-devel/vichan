<?php
require_once("inc/bootstrap.php");
require_once("inc/route.php");
require_once("inc/controller.php");

if (!$config["smart_build_helper"]) {
  die('You need to enable $config["smart_build_helper"]');
}

$config['smart_build'] = false; // Let's disable it, so we can build the page for real
$config['generation_strategies'] = array('strategy_immediate');

function after_open_board() { global $config;
  $config['smart_build'] = false;
  $config['generation_strategies'] = array('strategy_immediate');
};

$request = $_SERVER['REQUEST_URI'];

$route = route($request);

if (!$route) {
  $reached = false;
}
else {
  list ($fun, $args) = $route;
  $reached = call_user_func_array($fun, $args);
}

function die_404() { global $config;
  if (!$config['page_404']) {
    header("HTTP/1.1 404 Not Found");
    header("Status: 404 Not Found");
    echo "<h1>404 Not Found</h1><p>Page doesn't exist<hr><address>vichan</address>";
  }
  else {
    header("Location: ".$config['page_404']);
  }
  header("X-Accel-Expires: 120");
  die();
}

if ($reached) {
  if ($request[strlen($request)-1] == '/') {
    $request .= 'index.html';
  }
  $request = '.'.$request;

  if (!file_exists($request)) {
    die_404();
  }

  header("HTTP/1.1 200 OK");
  header("Status: 200 OK");
  if (preg_match('/\.json$/', $request)) {
    header("Content-Type", "application/json");
  }
  elseif (preg_match('/\.js$/', $request)) {
    header("Content-Type", "text/javascript; charset=utf-8");
  }
  elseif (preg_match('/\.xml$/', $request)) {
    header("Content-Type", "application/xml");
  }
  elseif (preg_match('/\.rss$/', $request)) {
    header("Content-Type", "application/rss+xml");
  }
  else {
    header("Content-Type", "text/html; charset=utf-8");
  }
  header("Cache-Control: public, nocache, no-cache, max-age=0, must-revalidate");
  header("Expires: Fri, 22 Feb 1991 06:00:00 GMT");
  header("Last-Modified: ".date('r', filemtime($request)));

  //if (isset ($_SERVER['HTTP_ACCEPT_ENCODING']) && preg_match('/gzip/', $_SERVER['HTTP_ACCEPT_ENCODING']) && file_exists($request.".gz")) {
  //  header("Content-Encoding: gzip");
  //  $file = fopen($request.".gz", 'r');
  //}
  //else {
  $file = fopen($request, 'r');
  //}
  fpassthru($file);
  fclose($file);
}
else {
  die_404();
}
