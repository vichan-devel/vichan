<?php
	require 'info.php';
	
	function frameset_build($action, $settings, $board) {
		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)
		
		Frameset::build($action, $settings);
	}

	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class Frameset {
		public static function build($action, $settings) {
			global $config;
			
			if ($action == 'all')
				file_write($config['dir']['home'] . $settings['file_main'], Frameset::homepage($settings));
			
			if ($action == 'all' || $action == 'boards')
				file_write($config['dir']['home'] . $settings['file_sidebar'], Frameset::sidebar($settings));
			
			if ($action == 'all' || $action == 'news')
				file_write($config['dir']['home'] . $settings['file_news'], Frameset::news($settings));
		}
		
		// Build homepage
		public static function homepage($settings) {
			global $config;
			
			return Element('themes/frameset/frames.html', Array('config' => $config, 'settings' => $settings));
		}
		
		// Build news page
		public static function news($settings) {
			global $config;
			
			$query = query("SELECT * FROM ``news`` ORDER BY `time` DESC") or error(db_error());
			$news = $query->fetchAll(PDO::FETCH_ASSOC);
			
			return Element('themes/frameset/news.html', Array(
				'settings' => $settings,
				'config' => $config,
				'news' => $news
			));
		}
		
		// Build sidebar
		public static function sidebar($settings) {
			global $config;
			
			return Element('themes/frameset/sidebar.html', Array(
				'settings' => $settings,
				'config' => $config,
				'boards' => listBoards()
			));
		}
	};
	
?>
