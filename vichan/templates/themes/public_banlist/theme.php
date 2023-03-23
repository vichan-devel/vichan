<?php
	require 'info.php';
	
	function pbanlist_build($action, $settings, $board) {
		// Possible values for $action:
		//	- all (rebuild everything, initialization)
		//	- news (news has been updated)
		//	- boards (board list changed)
		//	- bans (ban list changed)
		
		PBanlist::build($action, $settings);
	}

	// Wrap functions in a class so they don't interfere with normal Tinyboard operations
	class PBanlist {
		public static function build($action, $settings) {
			global $config;
			
			if ($action == 'all')
				file_write($config['dir']['home'] . $settings['file_bans'], PBanlist::homepage($settings));
			
			if ($action == 'all' || $action == 'bans')
				file_write($config['dir']['home'] . $settings['file_json'], PBanlist::gen_json($settings));
		}

		public static function gen_json($settings) {
			ob_start();
			Bans::stream_json(false, true, true, array());
			$out = ob_get_contents();
			ob_end_clean();
			return $out;
		}
		
		// Build homepage
		public static function homepage($settings) {
			global $config;

		        return Element('page.html', array(
		                'config' => $config,
		                'mod' => false,  
        		        'hide_dashboard_link' => true,
        		        'title' => _("Ban list"),
        		        'subtitle' => "",
		                'nojavascript' => true,
		                'body' => Element('mod/ban_list.html', array(
			                'mod' => false,
			                'boards' => "[]",
			                'token' => false,
			                'token_json' => false,
					'uri_json' => $config['dir']['home'] . $settings['file_json'],
				))
		        ));
		}
	};
	
?>
