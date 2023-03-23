<?php
	require 'info.php';

	function catalog_build($action, $settings, $board) {
		global $config;

		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)
		//	- post (a reply has been made)
		//	- post-thread (a thread has been made)

		$boards = explode(' ', $settings['boards']);

		if ($action == 'all') {
			foreach ($boards as $board) {
				$b = new Catalog();

				$action = generation_strategy("sb_catalog", array($board));
				if ($action == 'delete') {
					file_unlink($config['dir']['home'] . $board . '/catalog.html');
					file_unlink($config['dir']['home'] . $board . '/index.rss');
				}
				elseif ($action == 'rebuild') {
					$b->build($settings, $board);
				}
			}
		} elseif ($action == 'post-thread' || ($settings['update_on_posts'] && $action == 'post') || ($settings['update_on_posts'] && $action == 'post-delete') && in_array($board, $boards)) {
			$b = new Catalog();

			$action = generation_strategy("sb_catalog", array($board));
			if ($action == 'delete') {
				file_unlink($config['dir']['home'] . $board . '/catalog.html');
				file_unlink($config['dir']['home'] . $board . '/index.rss');
			}
			elseif ($action == 'rebuild') {
				$b->build($settings, $board);
			}
		}
	}

	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class Catalog {
		public function build($settings, $board_name, $mod = false) {
			global $config, $board;

			if (!isset($board) || $board['uri'] != $board_name) {
				if (!openBoard($board_name)) {
					error(sprintf(_("Board %s doesn't exist"), $board_name));
				}
			}

			$recent_images = array();
			$recent_posts = array();
			$stats = array();

                        $query = query(sprintf("SELECT *, `id` AS `thread_id`,
				(SELECT COUNT(`id`) FROM ``posts_%s`` WHERE `thread` = `thread_id`) AS `reply_count`,
				(SELECT SUM(`num_files`) FROM ``posts_%s`` WHERE `thread` = `thread_id` AND `num_files` IS NOT NULL) AS `image_count`,
				'%s' AS `board` FROM ``posts_%s`` WHERE `thread`  IS NULL ORDER BY `bump` DESC",
			$board_name, $board_name, $board_name, $board_name, $board_name)) or error(db_error());

			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {

				if ($mod)
					$post['link'] = $config['root'] . $config['file_mod'] . '?/'. $board['dir'] . $config['dir']['res'] . link_for($post);
				else
					$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . link_for($post);
				$post['board_name'] = $board['name'];

				if ($post['embed'] && preg_match('/^https?:\/\/(\w+\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9\-_]{10,11})(&.+)?$/i', $post['embed'], $matches)) {
					$post['youtube'] = $matches[2];
				}

				if (isset($post['files']) && $post['files']) {
					$files = json_decode($post['files']);

					if ($files[0]) {
						if ($files[0]->file == 'deleted') {
							if (count($files) > 1) {
								foreach ($files as $file) {
									if (($file == $files[0]) || ($file->file == 'deleted')) continue;
									$post['file'] = $config['uri_thumb'] . $file->thumb;
								}

								if (empty($post['file'])) $post['file'] = $config['image_deleted'];
							}
							else {
								$post['file'] = $config['image_deleted'];
							}
						}
						else if($files[0]->thumb == 'spoiler') {
							$post['file'] = $config['root'] . $config['spoiler_image'];
						}
						else {
							$post['file'] = $config['uri_thumb'] . $files[0]->thumb;
						}
					}
				} else {
					$post['file'] = $config['root'] . $config['image_deleted'];
				}

				if (empty($post['image_count'])) $post['image_count'] = 0;
				$post['pubdate'] = date('r', $post['time']);
				$recent_posts[] = $post;
			}

			$required_scripts = array('js/jquery.min.js', 'js/jquery.mixitup.min.js', 'js/catalog.js');

			foreach($required_scripts as $i => $s) {
				if (!in_array($s, $config['additional_javascript']))
					$config['additional_javascript'][] = $s;
			}

			$link = ($mod) ? $config['root'] . $config['file_mod'] . '?/' . $board['dir'] : $config['root'] . $board['dir'];

			$element = Element('themes/catalog/catalog.html', Array(
				'settings' => $settings,
				'config' => $config,
				'boardlist' => createBoardlist($mod),
				'recent_images' => $recent_images,
				'recent_posts' => $recent_posts,
				'stats' => $stats,
				'board' => $board_name,
				'link' => $link,
				'mod' => $mod
			));

			if ($mod) {
				return $element;
			} else {
				file_write($config['dir']['home'] . $board_name . '/catalog.html', $element);

				file_write($config['dir']['home'] . $board_name . '/index.rss', Element('themes/catalog/index.rss', Array(
					'config' => $config,
					'recent_posts' => $recent_posts,
					'board' => $board
				)));
			}
		}
	};
