<?php
$dir = "static/banners/";
$files = scandir($dir, SCANDIR_SORT_NONE);
$images = array_diff($files, array('.', '..'));
$name = $images[array_rand($images)];
// open the file in a binary mode
$fp = fopen($dir . $name, 'rb');

// send the right headers
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies
$fstat = fstat($fp);
header('Content-Type: ' . mime_content_type($dir . $name));
header('Content-Length: ' . $fstat['size']);

// dump the picture and stop the script
fpassthru($fp);
exit;
?>
