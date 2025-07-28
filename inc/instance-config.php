<?php

/*
 *  Instance Configuration
 *  ----------------------
 *  Edit this file and not config.php for imageboard configuration.
 *
 *  You can copy values from config.php (defaults) and paste them here.
 */
	$config['additional_javascript'][] = 'js/jquery.min.js';
    $config['additional_javascript'][] = 'js/jquery-ui.custom.min.js';
    $config['additional_javascript'][] = 'js/ajax.js';
    $config['additional_javascript'][] = 'js/wPaint/8ch.js';
    $config['additional_javascript'][] = 'js/wpaint.js';
    $config['additional_javascript'][] = 'js/upload-selection.js';
	$config['additional_javascript'][] = 'js/ajax-post-controls.js';
	$config['additional_javascript'][] = 'js/style-select.js';


	// Database stuff
	$config['db']['type']		= 'mysql';
	$config['db']['server']		= 'localhost';
	$config['db']['user']		= '';
	$config['db']['password']	= '';
	$config['db']['database']	= '';
	
	//$config['root']				= '/';
	
	@include('inc/secrets.php');
?>
