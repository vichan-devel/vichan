<?php
	$theme = Array();
	
	// Theme name
	$theme['name'] = 'JSFrameset';
	// Description (you can use Tinyboard markup here)
	$theme['description'] = 
'Use a basic frameset layout, with a list of boards and pages on a sidebar to the left of the page.

Users never have to leave the homepage; they can do all their browsing from the one page.';
	$theme['version'] = 'v0.1';
	
	// Theme configuration	
	$theme['config'] = Array();
	
	
	$theme['config'][] = Array(
		'title' => 'Sidebar file',
		'name' => 'file_sidebar',
		'type' => 'text',
		'default' => 'sidebar.html',
		'comment' => '(eg. "sidebar.html") IF YOU CHANGE YOU NEED TO UPDATE JS TO REFLECT CHANGE'
	);
	
	// Unique function name for building everything
	$theme['build_function'] = 'jsframeset_build';
?>
