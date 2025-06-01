<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */
require_once 'inc/bootstrap.php';
defined('TINYBOARD') or exit;

$twig = false;

function load_twig() {
	global $twig, $config;

	$cache_dir = "{$config['dir']['template']}/cache/";

	$loader = new Twig\Loader\FilesystemLoader($config['dir']['template']);
	$loader->setPaths($config['dir']['template']);
	$twig = new Twig\Environment($loader, array(
		'autoescape' => false,
		'cache' => is_writable('templates/') || (is_dir($cache_dir) && is_writable($cache_dir)) ?
			new Vichan\Twig\FilesystemCache\TinyboardFilesystem($cache_dir) : false,
		'debug' => $config['debug'],
		'auto_reload' => $config['twig_auto_reload']
	));
	if ($config['debug'])
		$twig->addExtension(new \Twig\Extension\DebugExtension());
	$twig->addExtension(new Vichan\Twig\Extensions\TinyboardExtension());
	$twig->addExtension(new Vichan\Twig\Extensions\I18nExtension());
}

function Element($templateFile, array $options) {
	global $config, $debug, $twig, $build_pages;

	if (!$twig)
		load_twig();

	if (isset($options['body']) && $config['debug']) {
		$_debug = $debug;

		if (isset($debug['start'])) {
			$_debug['time']['total'] = '~' . round((microtime(true) - $_debug['start']) * 1000, 2) . 'ms';
			$_debug['time']['init'] = '~' . round(($_debug['start_debug'] - $_debug['start']) * 1000, 2) . 'ms';
			unset($_debug['start']);
			unset($_debug['start_debug']);
		}
		if ($config['try_smarter'] && isset($build_pages) && !empty($build_pages))
			$_debug['build_pages'] = $build_pages;
		$_debug['included'] = get_included_files();
		$_debug['memory'] = round(memory_get_usage(true) / (1024 * 1024), 2) . ' MiB';
		$_debug['time']['db_queries'] = '~' . round($_debug['time']['db_queries'] * 1000, 2) . 'ms';
		$_debug['time']['exec'] = '~' . round($_debug['time']['exec'] * 1000, 2) . 'ms';
		$options['body'] .=
			'<h3>Debug</h3><pre style="white-space: pre-wrap;font-size: 10px;">' .
				str_replace("\n", '<br/>', utf8tohtml(print_r($_debug, true))) .
			'</pre>';
	}

	// Read the template file
	if (@file_get_contents("{$config['dir']['template']}/{$templateFile}")) {
		$body = $twig->render($templateFile, $options);

		if ($config['minify_html'] && preg_match('/\.html$/', $templateFile)) {
			$body = trim(preg_replace("/[\t\r\n]/", '', $body));
		}

		return $body;
	} else {
		throw new Exception("Template file '{$templateFile}' does not exist or is empty in '{$config['dir']['template']}'!");
	}
}

function twig_push_filter($array, $value) {
	array_push($array, $value);
	return $array;
}

function twig_date_filter($date, $format) {
    if (is_numeric($date)) {
        $date = new DateTime("@$date", new DateTimeZone('UTC'));
    } else {
        $date = new DateTime($date, new DateTimeZone('UTC'));
    }
    return $date->format($format);
}

function twig_hasPermission_filter($mod, $permission, $board = null) {
	return hasPermission($permission, $board, $mod);
}

function twig_extension_filter($value, $case_insensitive = true) {
	$ext = mb_substr($value, mb_strrpos($value, '.') + 1);
	if($case_insensitive)
		$ext = mb_strtolower($ext);
	return $ext;
}

function twig_sprintf_filter( $value, $var) {
	return sprintf($value, $var);
}

function twig_truncate_filter($value, $length = 30, $preserve = false, $separator = '…') {
	if (mb_strlen($value) > $length) {
		if ($preserve) {
			if (false !== ($breakpoint = mb_strpos($value, ' ', $length))) {
				$length = $breakpoint;
			}
		}
		return mb_substr($value, 0, $length) . $separator;
	}
	return $value;
}

function twig_filename_truncate_filter($value, $length = 30, $separator = '…') {
	if (mb_strlen($value) > $length) {
		$value = strrev($value);
		$array = array_reverse(explode(".", $value, 2));
		$array = array_map("strrev", $array);

		$filename = &$array[0];
		$extension = isset($array[1]) ? $array[1] : false;

		$filename = mb_substr($filename, 0, $length - ($extension ? mb_strlen($extension) + 1 : 0)) . $separator;

		return implode(".", $array);
	}
	return $value;
}

function twig_ratio_function($w, $h) {
	return fraction($w, $h, ':');
}
function twig_secure_link_confirm($text, $title, $confirm_message, $href) {
	global $config;

	return '<a onclick="if (event.which==2) return true;if (confirm(\'' . htmlentities(addslashes($confirm_message)) . '\')) document.location=\'?/' . htmlspecialchars(addslashes($href . '/' . make_secure_link_token($href))) . '\';return false;" title="' . htmlentities($title) . '" href="?/' . $href . '">' . $text . '</a>';
}
function twig_secure_link($href) {
	return $href . '/' . make_secure_link_token($href);
}
