<?php
	require 'info.php';
	
	function ukko_build($action, $settings) {
		global $config;

		$ukko = new ukko();
		$ukko->settings = $settings;

		if (! ($action == 'all' || $action == 'post' || $action == 'post-thread' || $action == 'post-delete')) {
			return;
		}

		$action = generation_strategy('sb_ukko', array());

		if ($action == 'delete') {
			file_unlink($settings['uri'] . '/index.html');
		}
		elseif ($action == 'rebuild') {
			file_write($settings['uri'] . '/index.html', $ukko->build());
		}
	}
	
	class ukko {
		public $settings;
		public function build($mod = false) {
			global $config;
			$boards = listBoards();
			
			$body = '';
			$overflow = array();
			$board = array(
				'url' => $this->settings['uri'],
				'title' => $this->settings['title'],
				'subtitle' => sprintf($this->settings['subtitle'], $this->settings['thread_limit'])
			);

			$query = '';
			foreach($boards as &$_board) {
				if(in_array($_board['uri'], explode(' ', $this->settings['exclude'])))
					continue;
				$query .= sprintf("SELECT *, '%s' AS `board` FROM ``posts_%s`` WHERE `thread` IS NULL UNION ALL ", $_board['uri'], $_board['uri']);
			}
			$query = preg_replace('/UNION ALL $/', 'ORDER BY `bump` DESC', $query);
			$query = query($query) or error(db_error());

			$count = 0;
			$threads = array();
			while($post = $query->fetch()) {

				if(!isset($threads[$post['board']])) {
					$threads[$post['board']] = 1;
				} else {
					$threads[$post['board']] += 1;
				}
	
				if($count < $this->settings['thread_limit']) {				
					openBoard($post['board']);			
					$thread = new Thread($post, $mod ? '?/' : $config['root'], $mod);

					$posts = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE `thread` = :id ORDER BY `id` DESC LIMIT :limit", $post['board']));
					$posts->bindValue(':id', $post['id']);
					$posts->bindValue(':limit', ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']), PDO::PARAM_INT);
					$posts->execute() or error(db_error($posts));
					
					$num_images = 0;
					while ($po = $posts->fetch()) {
						if ($po['files'])
							$num_images++;
						
						$thread->add(new Post($po, $mod ? '?/' : $config['root'], $mod));
					
					}
					if ($posts->rowCount() == ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview'])) {
						$ct = prepare(sprintf("SELECT COUNT(`id`) as `num` FROM ``posts_%s`` WHERE `thread` = :thread UNION ALL SELECT COUNT(`id`) FROM ``posts_%s`` WHERE `files` IS NOT NULL AND `thread` = :thread", $post['board'], $post['board']));
						$ct->bindValue(':thread', $post['id'], PDO::PARAM_INT);
						$ct->execute() or error(db_error($count));
						
						$c = $ct->fetch();
						$thread->omitted = $c['num'] - ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']);
						
						$c = $ct->fetch();
						$thread->omitted_images = $c['num'] - $num_images;
					}


					$thread->posts = array_reverse($thread->posts);
					$body .= '<h2><a href="' . $config['root'] . $post['board'] . '">/' . $post['board'] . '/</a></h2>';
					$body .= $thread->build(true);
				} else {
					$page = 'index';
					if(floor($threads[$post['board']] / $config['threads_per_page']) > 0) {
						$page = floor($threads[$post['board']] / $config['threads_per_page']) + 1;
					}
					$overflow[] = array('id' => $post['id'], 'board' => $post['board'], 'page' => $page . '.html');
				}

				$count += 1;
			}

			$body .= '<script> var overflow = ' . json_encode($overflow) . '</script>';
			$body .= '<script type="text/javascript" src="/'.$this->settings['uri'].'/ukko.js"></script>';

			return Element('index.html', array(
				'config' => $config,
				'board' => $board,
				'no_post_form' => true,
				'body' => $body,
				'mod' => $mod,
				'boardlist' => createBoardlist($mod),
			));
		}
		
	};
	
?>
