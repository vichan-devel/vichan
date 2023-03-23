<?php
	$theme = array();
	
	// Theme name
	$theme['name'] = 'Overboard (Ukko)';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 'Board with threads and messages from all boards';
	$theme['version'] = 'v0.2';
	
	// Theme configuration	
	$theme['config'] = array();
	
	$theme['config'][] = array(
		'title' => 'Board name',
		'name' => 'title',
		'type' => 'text',
		'default' => 'Ukko'
	);
	$theme['config'][] = array(
		'title' => 'Board URI',
		'name' => 'uri',
		'type' => 'text',
		'default' => '*',
		'comment' => '(ukko for example)'
	);	
	$theme['config'][] = array(
		'title' => 'Subtitle',
		'name' => 'subtitle',
		'type' => 'text',
		'comment' => '(%s = thread limit. for example "%s freshly bumped threads")'
	);		
	$theme['config'][] = array(
		'title' => 'Excluded boards',
		'name' => 'exclude',
		'type' => 'text',
		'comment' => '(space seperated)'
	);
	$theme['config'][] = array(
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
	                file_write($settings['uri'] . '/ukko.js', Element('themes/ukko/ukko.js', array()));
		}
	}
	
