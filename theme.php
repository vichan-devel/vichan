<?php
	require 'info.php';
	
	function ukko_build($action, $settings) {
		$ukko = new ukko();
		$ukko->settings = $settings;
		$ukko->build();
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
				'name' => $this->settings['title'],
				'title' => sprintf($this->settings['subtitle'], $this->settings['thread_limit'])
			);

			$query = '';
			foreach($boards as &$_board) {
				if(in_array($_board['uri'], explode(' ', $this->settings['exclude'])))
					continue;
				$query .= sprintf("SELECT *, '%s' AS `board` FROM `posts_%s` WHERE `thread` IS NULL UNION ALL ", $_board['uri'], $_board['uri']);
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
					$thread = new Thread(
						$post['id'], $post['subject'], $post['email'], $post['name'], $post['trip'], $post['capcode'], $post['body'], $post['time'],
						$post['thumb'], $post['thumbwidth'], $post['thumbheight'], $post['file'], $post['filewidth'], $post['fileheight'], $post['filesize'],
						$post['filename'], $post['ip'], $post['sticky'], $post['locked'], $post['sage'], $post['embed'], $mod ? '?/' : $config['root'], $mod
					);

					$posts = prepare(sprintf("SELECT * FROM `posts_%s` WHERE `thread` = :id ORDER BY `id` DESC LIMIT :limit", $post['board']));
					$posts->bindValue(':id', $post['id']);
					$posts->bindValue(':limit', ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview']), PDO::PARAM_INT);
					$posts->execute() or error(db_error($posts));
					
					$num_images = 0;
					while ($po = $posts->fetch()) {
						if ($po['file'])
							$num_images++;
						
						$thread->add(new Post(
							$po['id'], $post['id'], $po['subject'], $po['email'], $po['name'], $po['trip'], $po['capcode'], $po['body'], $po['time'],
							$po['thumb'], $po['thumbwidth'], $po['thumbheight'], $po['file'], $po['filewidth'], $po['fileheight'], $po['filesize'],
							$po['filename'], $po['ip'], $po['embed'], $mod ? '?/' : $config['root'], $mod)
						);
					
					}
					if ($posts->rowCount() == ($post['sticky'] ? $config['threads_preview_sticky'] : $config['threads_preview'])) {
						$ct = prepare(sprintf("SELECT COUNT(`id`) as `num` FROM `posts_%s` WHERE `thread` = :thread UNION ALL SELECT COUNT(`id`) FROM `posts_%s` WHERE `file` IS NOT NULL AND `thread` = :thread", $post['board'], $post['board']));
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
			$body .= '<script type="text/javascript" src="ukko.js"></script>';

			file_write($this->settings['uri'] . '/index.html', Element('index.html', array(
				'config' => $config,
				'board' => $board,
				'no_post_form' => true,
				'body' => $body,
				'boardlist' => createBoardlist($mod)
			)));

			file_write($this->settings['uri'] . '/ukko.js', Element('themes/ukko/ukko.js', array()));			
		}
		
	};
	
?>
