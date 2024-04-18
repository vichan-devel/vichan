<?php

$files = scandir('static/banners/', SCANDIR_SORT_NONE);
$files = array_diff($files, ['.', '..']);

$name = $files[array_rand($files)];
header("Location: /static/banners/$name", true, 307);
header('Cache-Control: no-cache');
