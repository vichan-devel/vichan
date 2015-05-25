<?php

require 'info.php';
	
function rules_build($action, $settings, $board) {
  rules::build($action, $settings);
}
	
class rules {
  public static function build($action, $settings) {
    global $config;

    if ($action == 'all') {
      file_write($config['dir']['home'] . $settings['file'], rules::install($settings));
    }
  }

  public static function install($settings) {
    global $config;
			
    return Element('themes/rules/rules.html',
                   array('settings'  => $settings,
                         'config'    => $config,
                         'boardlist' => createBoardlist()));
  }
}
	
?>
