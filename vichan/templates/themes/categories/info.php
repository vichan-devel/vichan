<?php
	$theme = Array();
	
	// Theme name
	$theme['name'] = 'Categories';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 
'Group-ordered, category-aware modification of the Frameset theme, with removable sidebar frame.

Requires $config[\'categories\'].';
	$theme['version'] = 'v0.3';
	
	// Theme configuration	
	$theme['config'] = Array();
	
	$theme['config'][] = Array(
		'title' => 'Site title',
		'name' => 'title',
		'type' => 'text'
	);
	
	$theme['config'][] = Array(
		'title' => 'Slogan',
		'name' => 'subtitle',
		'type' => 'text',
		'comment' => '(optional)'
	);
	
	$theme['config'][] = Array(
		'title' => 'Main HTML file',
		'name' => 'file_main',
		'type' => 'text',
		'default' => $config['file_index'],
		'comment' => '(eg. "index.html")'
	);
	
	$theme['config'][] = Array(
		'title' => 'Sidebar file',
		'name' => 'file_sidebar',
		'type' => 'text',
		'default' => 'sidebar.html',
		'comment' => '(eg. "sidebar.html")'
	);
	
	$theme['config'][] = Array(
		'title' => 'News file',
		'name' => 'file_news',
		'type' => 'text',
		'default' => 'news.html',
		'comment' => '(eg. "news.html")'
	);
	
	// Unique function name for building everything
	$theme['build_function'] = 'categories_build';
	$theme['install_callback'] = 'categories_install';
	
	if (!function_exists('categories_install')) {
		function categories_install($settings) {
			global $config;
			
			if (!isset($config['categories'])) {
				return Array(false, '<h2>Prerequisites not met!</h2>' . 
					'This theme requires $config[\'boards\'] and $config[\'categories\'] to be set.');
			}
		}
	}
?>
