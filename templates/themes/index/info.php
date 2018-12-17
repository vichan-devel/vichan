<?php
	$theme = Array();
	
	// Theme name
	$theme['name'] = 'Index';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 'Show a homepage';
	$theme['version'] = 'v1.0';
	
	// Theme configuration	
	$theme['config'] = Array();
	
	$theme['config'][] = Array(
		'title' => 'Icon',
		'name' => 'icon',
		'type' => 'text',
		'default' => '../templates/themes/index/hikichanIcon.png',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Title',
		'name' => 'title',
		'type' => 'text',
		'default' => 'Welcome to my Image Board',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Subtitle',
		'name' => 'subtitle',
		'type' => 'text',
		'default' => 'What is chaos for the fly is normal for the spider.',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => 'Description',
		'name' => 'description',
		'type' => 'textarea',
		'default' => 'Short description for your website.'
	);
	
		$theme['config'][] = Array(
		'title' => 'Image of the now.',
		'name' => 'imageofnow',
		'type' => 'text',
		'default' => '../templates/themes/index/hotweels.jpg',
		'size' => 50
	);
	
		$theme['config'][] = Array(
		'title' => 'Quote of the now.',
		'name' => 'quoteofnow',
		'type' => 'textarea',
		'default' => '"Great minds discuss ideas; average minds discuss events; small minds discuss people." - QUOTE'
	);
	
		$theme['config'][] = Array(
		'title' => 'Video of the Now',
		'name' => 'videoofnow',
		'type' => 'text',
		'default' => 'https://www.youtube.com/embed/zndkMAHKjNM',
		'size' => 50
	);
	
	$theme['config'][] = Array(
		'title' => '# of recent entries',
		'name' => 'no_recent',
		'type' => 'text',
		'default' => 5,
		'size' => 3,
		'comment' => '(number of recent news entries to display; "0" is infinite)'
	);
	
	$theme['config'][] = Array(
		'title' => 'Excluded boards',
		'name' => 'exclude',
		'type' => 'text',
		'comment' => '(space seperated)'
	);
	
	$theme['config'][] = Array(
		'title' => '# of recent images',
		'name' => 'limit_images',
		'type' => 'text',
		'default' => '15',
		'comment' => '(maximum images to display)'
	);
	
	$theme['config'][] = Array(
		'title' => '# of recent posts',
		'name' => 'limit_posts',
		'type' => 'text',
		'default' => '30',
		'comment' => '(maximum posts to display)'
	);
	
	$theme['config'][] = Array(
		'title' => 'HTML file',
		'name' => 'html',
		'type' => 'text',
		'default' => 'index.html',
		'comment' => '(eg. "index.html")'
	);
	
	$theme['config'][] = Array(
		'title' => 'CSS file',
		'name' => 'css',
		'type' => 'text',
		'default' => 'index.css',
		'comment' => '(eg. "index.css")'
	);

	$theme['config'][] = Array(
		'title' => 'CSS stylesheet name',
		'name' => 'basecss',
		'type' => 'text',
		'default' => 'index.css',
		'comment' => '(eg. "index.css" - see templates/themes/index for details)'
	);
	
	// Unique function name for building everything
	$theme['build_function'] = 'index_build';
	$theme['install_callback'] = 'index_install';

	if (!function_exists('index_install')) {
		function index_install($settings) {
			if (!is_numeric($settings['limit_images']) || $settings['limit_images'] < 0)
				return Array(false, '<strong>' . utf8tohtml($settings['limit_images']) . '</strong> is not a non-negative integer.');
			if (!is_numeric($settings['limit_posts']) || $settings['limit_posts'] < 0)
				return Array(false, '<strong>' . utf8tohtml($settings['limit_posts']) . '</strong> is not a non-negative integer.');
			if (!is_numeric($settings['no_recent']) || $settings['no_recent'] < 0)
				return Array(false, '<strong>' . utf8tohtml($settings['no_recent']) . '</strong> is not a non-negative integer.');
		}
	}
	
