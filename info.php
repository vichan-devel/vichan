<?php
	$theme = Array();
	
	// Theme name
	$theme['name'] = 'Ukko';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 'Board with threads and messages from all boards';
	$theme['version'] = 'v0.1';
	
	// Theme configuration	
	$theme['config'] = Array();
	
	$theme['config'][] = Array(
		'title' => 'Board name',
		'name' => 'title',
		'type' => 'text',
		'default' => 'Ukko'
	);
	$theme['config'][] = Array(
		'title' => 'Board URI',
		'name' => 'uri',
		'type' => 'text',
		'comment' => '(ukko for example)'
	);	
	$theme['config'][] = Array(
		'title' => 'Subtitle',
		'name' => 'subtitle',
		'type' => 'text',
		'comment' => '(%s = thread limit. for example "%s freshly bumped threads")'
	);		
	$theme['config'][] = Array(
		'title' => 'Excluded boards',
		'name' => 'exclude',
		'type' => 'text',
		'comment' => '(space seperated)'
	);
	$theme['config'][] = Array(
		'title' => 'Number of threads',
		'name' => 'thread_limit',
		'type' => 'text',
		'default' => '15',
	);	
	// Unique function name for building everything
	$theme['build_function'] = 'ukko_build';
	$theme['install_callback'] = 'ukko_install';

	if(!function_exists('ukko_install')) {
		function ukko_install($settings) {
			if (!file_exists($settings['uri']))
				@mkdir($settings['uri'], 0777) or error("Couldn't create " . $settings['uri'] . ". Check permissions.", true);
		}
	}
	
