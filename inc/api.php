<?php
/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */


/**
 * Class for generating json API compatible with 4chan API
 */
class Api {
	function __construct(){
		global $config;
		/**
		 * Translation from local fields to fields in 4chan-style API
		 */
		$this->config = $config;

		$this->postFields = array(
			'id' => 'no',
			'thread' => 'resto',
			'subject' => 'sub',
			'body' => 'com',
			'email' => 'email',
			'name' => 'name',
			'trip' => 'trip',
			'capcode' => 'capcode',
			'time' => 'time',
			'thumbheight' => 'tn_w',
			'thumbwidth' => 'tn_h',
			'fileheight' => 'w',
			'filewidth' => 'h',
			'filesize' => 'fsize',
			'filename' => 'filename',
			'omitted' => 'omitted_posts',
			'omitted_images' => 'omitted_images',
			'sticky' => 'sticky',
			'locked' => 'locked',
		);

		if (isset($config['api']['extra_fields']) && gettype($config['api']['extra_fields']) == 'array'){
			$this->postFields = array_merge($this->postFields, $config['api']['extra_fields']);
		}
	}

	private static $ints = array(
		'no' => 1,
		'resto' => 1,
		'time' => 1,
		'tn_w' => 1,
		'tn_h' => 1,
		'w' => 1,
		'h' => 1,
		'fsize' => 1,
		'omitted_posts' => 1,
		'omitted_images' => 1,
		'sticky' => 1,
		'locked' => 1,
	);

	private function translatePost($post) {
		$apiPost = array();
		foreach ($this->postFields as $local => $translated) {
			if (!isset($post->$local))
				continue;

			$toInt = isset(self::$ints[$translated]);
			$val = $post->$local;
			if ($val !== null && $val !== '') {
				$apiPost[$translated] = $toInt ? (int) $val : $val;
			}

		}

		if (isset($post->filename)) {
			$dotPos = strrpos($post->filename, '.');
			$apiPost['filename'] = substr($post->filename, 0, $dotPos);
			$apiPost['ext'] = substr($post->filename, $dotPos);
		}

		// Handle country field
		if (isset($post->body_nomarkup) && $this->config['country_flags']) {
			$modifiers = extract_modifiers($post->body_nomarkup);
			if (isset($modifiers['flag']) && isset($modifiers['flag alt']) && preg_match('/^[a-z]{2}$/', $modifiers['flag'])) {
				$country = strtoupper($modifiers['flag']);
				if ($country) {
					$apiPost['country'] = $country;
					$apiPost['country_name'] = $modifiers['flag alt'];
				}
			}
		}

		return $apiPost;
	}

	function translateThread(Thread $thread) {
		$apiPosts = array();
		$op = $this->translatePost($thread);
		$op['resto'] = 0;
		$apiPosts['posts'][] = $op;

		foreach ($thread->posts as $p) {
			$apiPosts['posts'][] = $this->translatePost($p);
		}

		return $apiPosts;
	}

	function translatePage(array $threads) {
		$apiPage = array();
		foreach ($threads as $thread) {
			$apiPage['threads'][] = $this->translateThread($thread);
		}
		return $apiPage;
	}

	function translateCatalogPage(array $threads) {
		$apiPage = array();
		foreach ($threads as $thread) {
			$ts = $this->translateThread($thread);
			$apiPage['threads'][] = current($ts['posts']);
		}
		return $apiPage;
	}

	function translateCatalog($catalog) {
		$apiCatalog = array();
		foreach ($catalog as $page => $threads) {
			$apiPage = $this->translateCatalogPage($threads);
			$apiPage['page'] = $page;
			$apiCatalog[] = $apiPage;
		}

		return $apiCatalog;
	}
}
