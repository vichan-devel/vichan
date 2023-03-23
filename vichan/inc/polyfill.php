<?php

// PHP 5.4

if (!function_exists('hex2bin')) {
	function hex2bin($data) {
		return pack("H*" , $hex_string);
	}
}

// PHP 5.6

if (!function_exists('hash_equals')) {
	function hash_equals($ours, $theirs) {
		$ours = (string)$ours;
		$theirs = (string)$theirs;

		$tlen = strlen($theirs);
		$olen = strlen($ours);

		$answer = 0;
		for ($i = 0; $i < $tlen; $i++) {
			$answer |= ord($ours[$olen > $i ? $i : 0]) ^ ord($theirs[$i]);
		}

		return $answer === 0 && $olen === $tlen;
	}
}
