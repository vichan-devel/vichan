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
			global $config;
			
			/*if ($action == 'all') {
				copy('templates/themes/recent/' . $settings['basecss'], $config['dir']['home'] . $settings['css']);
			}*/
			
			$this->excluded = explode(' ', $settings['exclude']);
			
			if ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete')
				file_write($config['dir']['home'] . $settings['xml'], $this->homepage($settings));
		}
		
		// Build news page
		public function homepage($settings) {
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
			
			
			$query = '';
			foreach ($boards as &$_board) {
				if (in_array($_board['uri'], $this->excluded))
					continue;
				$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` UNION ALL ", $_board['uri'], $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', 'ORDER BY `time` DESC LIMIT ' . (int)$settings['limit_posts'], $query);
			$query = query($query) or error(db_error());
			
			while ($post = $query->fetch(PDO::FETCH_ASSOC)) {
				openBoard($post['board']);
				
				$post['link'] = $config['root'] . $board['dir'] . $config['dir']['res'] . sprintf($config['file_page'], ($post['thread'] ? $post['thread'] : $post['id'])) . '#' . $post['id'];
				$post['snippet'] = pm_snippet($post['body'], 30);
				$post['board_name'] = $board['name'];
				
				$recent_posts[] = $post;
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
			
			return Element('themes/rss/rss.xml', Array(
				'settings' => $settings,
				'config' => $config,
				//'boardlist' => createBoardlist(),
				//'recent_images' => $recent_images,
				'recent_posts' => $recent_posts,
				//'stats' => $stats
			));
		}
	};
	
?>
