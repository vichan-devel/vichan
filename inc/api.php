<?php
/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

defined('TINYBOARD') or exit;

/**
 * Class for generating json API compatible with 4chan API
 */
class Api {
	private bool $show_filename;
	private bool $hide_email;
	private bool $country_flags;
	private array $postFields;

	private const INTS = [
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
		'replies' => 1,
		'images' => 1,
		'sticky' => 1,
		'locked' => 1,
		'last_modified' => 1
	];

	private const THREADS_PAGE_FIELDS = [
		'id' => 'no',
		'bump' => 'last_modified'
	];

	private const FILE_FIELDS = [
		'thumbheight' => 'tn_h',
		'thumbwidth' => 'tn_w',
		'height' => 'h',
		'width' => 'w',
		'size' => 'fsize'
	];

	public function __construct(bool $show_filename, bool $hide_email, bool $country_flags) {
		// Translation from local fields to fields in 4chan-style API
		$this->show_filename = $show_filename;
		$this->hide_email = $hide_email;
		$this->country_flags = $country_flags;

		$this->postFields = [
			'id' => 'no',
			'thread' => 'resto',
			'subject' => 'sub',
			'body' => 'com',
			'email' => 'email',
			'name' => 'name',
			'trip' => 'trip',
			'capcode' => 'capcode',
			'time' => 'time',
			'omitted' => 'omitted_posts',
			'omitted_images' => 'omitted_images',
			'replies' => 'replies',
			'images' => 'images',
			'sticky' => 'sticky',
			'locked' => 'locked',
			'cycle' => 'cyclical',
			'bump' => 'last_modified',
			'embed' => 'embed',
		];

		if (isset($config['api']['extra_fields']) && gettype($config['api']['extra_fields']) == 'array'){
			$this->postFields = array_merge($this->postFields, $config['api']['extra_fields']);
		}
	}

	private function translateFields($fields, $object, &$apiPost) {
		foreach ($fields as $local => $translated) {
			if (!isset($object->$local)) {
				continue;
			}

			$toInt = isset(self::INTS[$translated]);
			$val = $object->$local;
			if ($this->hide_email && $local === 'email') {
				$val = '';
			}
			if ($val !== null && $val !== '') {
				$apiPost[$translated] = $toInt ? (int) $val : $val;
			}
		}
	}

	private function translateFile($file, $post, &$apiPost) {
		$this->translateFields(self::FILE_FIELDS, $file, $apiPost);
		$dotPos = strrpos($file->file, '.');
		$apiPost['ext'] = substr($file->file, $dotPos);
		$apiPost['tim'] = substr($file->file, 0, $dotPos);

		if ($this->show_filename) {
			$apiPost['filename'] = @substr($file->name, 0, strrpos($file->name, '.'));
		} else {
			$apiPost['filename'] = substr($file->file, 0, $dotPos);
		}
		if (isset ($file->hash) && $file->hash) {
			$apiPost['md5'] = base64_encode(hex2bin($file->hash));
		} elseif (isset ($post->filehash) && $post->filehash) {
			$apiPost['md5'] = base64_encode(hex2bin($post->filehash));
		}
	}

	private function translatePost($post, bool $threadsPage = false) {
		global $config, $board;

		$apiPost = [];
		$fields = $threadsPage ? self::THREADS_PAGE_FIELDS : $this->postFields;
		$this->translateFields($fields, $post, $apiPost);


		if (isset($config['poster_ids']) && $config['poster_ids']) {
			$apiPost['id'] = poster_id($post->ip, $post->thread ?? $post->id);
		}
		if ($threadsPage) {
			return $apiPost;
		}

		// Handle country field
		if (isset($post->body_nomarkup) && $this->country_flags) {
			$modifiers = extract_modifiers($post->body_nomarkup);
			if (isset($modifiers['flag']) && isset($modifiers['flag alt']) && preg_match('/^[a-z]{2}$/', $modifiers['flag'])) {
				$country = strtoupper($modifiers['flag']);
				if ($country) {
					$apiPost['country'] = $country;
					$apiPost['country_name'] = $modifiers['flag alt'];
				}
			}
		}

		if ($config['slugify'] && !$post->thread) {
			$apiPost['semantic_url'] = $post->slug;
		}

		// Handle files
		// Note: 4chan only supports one file, so only the first file is taken into account for 4chan-compatible API.
		if (isset($post->files) && $post->files && !$threadsPage) {
			$file = $post->files[0];
			$this->translateFile($file, $post, $apiPost);

			if (sizeof($post->files) > 1) {
				$extra_files = [];
				foreach ($post->files as $i => $f) {
					if ($i == 0) {
						continue;
					}

					$extra_file = [];
					$this->translateFile($f, $post, $extra_file);

					$extra_files[] = $extra_file;
				}
				$apiPost['extra_files'] = $extra_files;
			}
		}

		return $apiPost;
	}

	public function translateThread(Thread $thread, bool $threadsPage = false) {
		$apiPosts = [];
		$op = $this->translatePost($thread, $threadsPage);
		if (!$threadsPage) $op['resto'] = 0;
		$apiPosts['posts'][] = $op;

		foreach ($thread->posts as $p) {
			$apiPosts['posts'][] = $this->translatePost($p, $threadsPage);
		}

		return $apiPosts;
	}

	public function translatePage(array $threads) {
		$apiPage = [];
		foreach ($threads as $thread) {
			$apiPage['threads'][] = $this->translateThread($thread);
		}
		return $apiPage;
	}

	public function translateCatalogPage(array $threads, bool $threadsPage = false) {
		$apiPage = [];
		foreach ($threads as $thread) {
			$ts = $this->translateThread($thread, $threadsPage);
			$apiPage['threads'][] = current($ts['posts']);
		}
		return $apiPage;
	}

	public function translateCatalog($catalog, bool $threadsPage = false) {
		$apiCatalog = [];
		foreach ($catalog as $page => $threads) {
			$apiPage = $this->translateCatalogPage($threads, $threadsPage);
			$apiPage['page'] = $page;
			$apiCatalog[] = $apiPage;
		}

		return $apiCatalog;
	}
}
