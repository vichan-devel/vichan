<?php
	require 'info.php';
	
	function rss_recentposts_build($action, $settings, $board) {
		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)
		//	- post (a post has been made)
		//	- post-thread (a thread has been made)
		
		$b = new RSSRecentPosts();
		$b->build($action, $settings);
	}
	
	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class RSSRecentPosts {
		public function build($action, $settings) {
			global $config, $_theme;
			
			/*if ($action == 'all') {
				copy('templates/themes/recent/' . $settings['basecss'], $config['dir']['home'] . $settings['css']);
			}*/
			
			$this->excluded = explode(' ', $settings['exclude']);

			if ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete') {
				file_write($config['dir']['home'] . $settings['xml'], $this->homepage($settings));
				file_write($config['dir']['home'] . $settings['xml_op'], $this->homepage($settings, true));
			}
		}
		
		// Build news page
		public function homepage($settings, $op_only = false) {
			global $config, $board;
			
			//$recent_images = Array();
			$recent_posts = Array();
			//$stats = Array();
			
			$boards = listBoards();
			
			/*$query = '';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` WHERE `file` IS NOT NULL AND `file` != 'deleted' AND `thumb` != 'spoiler' UNION ALL ", $_board['uri'], $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', 'ORDER BY `time` DESC LIMIT ' . (int)$settings['limit_images'], $query);
			$query = query($query) or error(db_error());
			
			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				openBoard($post['board']);
				
				// board settings won't be available in the template file, so generate links now
				$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
				$post['src'] = $config['uri_thumb'] . $post['thumb'];
				
				//$recent_images[] = $post;
			}*/
			


			// SELECT b1.*, b2.subject AS `title`, 'b' AS `board` 
			// FROM `posts_b` b1
			// LEFT JOIN `posts_b` b2
			// ON (b1.thread IS NOT NULL AND b1.thread = b2.id)
			// OR (b1.thread IS NULL AND b1.id = b2.id)
			// ORDER BY `time` DESC LIMIT 30

			

			$query = '';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				// $query .= sprintf("SELECT b1.*, b2.subject AS `title`, '%s' AS `board` FROM `posts_%s` b1 INNER JOIN `posts_%s` b2 ON (b1.thread IS NOT NULL AND b1.thread = b2.id) OR (b1.thread IS NULL AND b1.id = b2.id) UNION ALL ", $_board['uri'], $_board['uri'], $_board['uri']);
				// // $query .= sprintf("SELECT t1.*, IF (t1.thread IS NULL, t1.subject, t2.subject) AS `title`, '%s' AS `board` FROM ``posts_%s`` t1, ``posts_%s`` t2 WHERE t1.thread IS NULL OR t2.id = t1.thread UNION ALL ", $_board['uri'], $_board['uri'], $_board['uri']);
				if($op_only)
					$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` WHERE `thread` IS NULL UNION ALL ", $_board['uri'], $_board['uri']);
				else
					$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` UNION ALL ", $_board['uri'], $_board['uri']);
			}

			$query = preg_replace('/UNION ALL $/', 'ORDER BY `time` DESC LIMIT ' . (int)$settings['limit_posts'], $query);
			$query = query($query) or error(db_error());

			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				openBoard($post['board']);
				
				$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
				$post['snippet'] = str_replace('&hellip;', '...', pm_snippet($post['body'], 80));
				$post['board_name'] = $board['name'];
				$post['pub_date'] = date('r', $post['time']);
				
				$recent_posts[] = $post;
			}


			// Get titles of threads
			$threads = array();
			if(!$op_only) {
				if(sizeof($recent_posts) > 0) {
					foreach($recent_posts as $p) {
						if($p['thread'] != null && (!isset($threads[$p['board']]) || !in_array($p['thread'], $threads[$p['board']])))
							$threads[$p['board']][] = $p['thread'];
					}

					// Build query
					$query = '';
					foreach($threads as $board => $thread_list) {
						$query .= sprintf("SELECT `id`, `subject`, '%s' AS `board` FROM ``posts_%s`` WHERE `id` IN (%s) UNION ALL ", $board, $board, implode(',', $threads[$board]));
					}
					$query = preg_replace('/UNION ALL $/', '', $query);
					$query = query($query) or error(db_error());

					// Fetch and Organize fetched data in a usefull manner 
					$threads = array();
					while ($t = $query->fetch(PDO::FETCH_ASSOC)) {
						$threads[$t['board']][$t['id']] = $t['subject'];
					}
				}

			}

			// Update all posts with thread subject data
			foreach($recent_posts as &$post) {
				if($post['thread'] == null)
					$post['title'] = $post['subject'];
				else
					$post['title'] = $threads[$post['board']][$post['thread']];

				// Trunkate title
				$post['title'] = !empty($post['title'])?str_replace('&hellip;', '...', pm_snippet($post['title'], 25)):"No title";
			}


			// Total posts
			/*$query = 'SELECT SUM(`top`) FROM (';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				$query .= sprintf("SELECT MAX(`id`) AS `top` FROM ``posts_%s`` UNION ALL ", $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
			$query = query($query) or error(db_error());*/
			//$stats['total_posts'] = number_format($query->fetchColumn());
			
			// Unique IPs
			/*$query = 'SELECT COUNT(DISTINCT(`ip`)) FROM (';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				$query .= sprintf("SELECT `ip` FROM ``posts_%s`` UNION ALL ", $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
			$query = query($query) or error(db_error());
			//$stats['unique_posters'] = number_format($query->fetchColumn());*/
			
			// Active content
			/*$query = 'SELECT SUM(`filesize`) FROM (';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				$query .= sprintf("SELECT `filesize` FROM ``posts_%s`` UNION ALL ", $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', ') AS `posts_all`', $query);
			$query = query($query) or error(db_error());
			//$stats['active_content'] = $query->fetchColumn();*/

			
			// error(var_export($settings));
			
			return Element('themes/rss/rss.xml', Array(
				'settings' => $settings,
				'config' => $config,
				'op_only' => $op_only,
				//'boardlist' => createBoardlist(),
				//'recent_images' => $recent_images,
				'recent_posts' => $recent_posts,
				//'stats' => $stats
			));
		}
	};
	
?>
