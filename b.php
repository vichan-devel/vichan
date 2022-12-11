<?php
$dir = "static/banners/";
$files = scandir($dir);
$images = array_diff($files, array('.', '..'));
$name = $images[array_rand($images)];
$image = $dir . $name;
header("Location: " . $image, true, 302);	

exit();

?>
