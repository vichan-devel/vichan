<?php

class Queue {
  function __construct($key) { global $config;
    if ($config['queue']['enabled'] == 'fs') {
      $this->lock = new Lock($key);
      $key = str_replace('/', '::', $key);
      $key = str_replace("\0", '', $key);
      $this->key = "tmp/queue/$key/";
    }
  }

  function push($str) { global $config;
    if ($config['queue']['enabled'] == 'fs') {
      $this->lock->get_ex();
      file_put_contents($this->key.microtime(true), $str);
      $this->lock->free();
    }
    return $this;
  }

  function pop($n = 1) { global $config;
    if ($config['queue']['enabled'] == 'fs') {
      $this->lock->get_ex();
      $dir = opendir($this->key);
      $paths = array();
      while ($n > 0) {
        $path = readdir($dir);
        if ($path === FALSE) break;
        elseif ($path == '.' || $path == '..') continue;
        else { $paths[] = $path; $n--; }
      }
      $out = array();
      foreach ($paths as $v) {
        $out []= file_get_contents($this->key.$v);
        unlink($this->key.$v);
      }
      $this->lock->free();
      return $out;
    }
  }
}

// Don't use the constructor. Use the get_queue function.
$queues = array();

function get_queue($name) { global $queues;
  return $queues[$name] = isset ($queues[$name]) ? $queues[$name] : new Queue($name);
}
