<?php
 require_once 'inc/functions.php';
 checkBan();
 if(@strlen($_POST["text"])>0) {
	file_put_contents("attentionbar.txt",$_POST["text"]);
	 if(strlen($_SERVER['HTTP_REFERER'])>0) { header('Location: ' . $_SERVER['HTTP_REFERER']); }
 	else { header('Location: /'); }
 } else print(file_get_contents("attentionbar.txt"));
 return;
?>
