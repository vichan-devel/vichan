<?php



// require 'inc/config.php';
// require 'inc/config_instance.php';
require 'inc/functions.php';


global $config;

// Check so only ADMIN can run script
check_login(true);
if (!$mod || $mod['type'] != ADMIN)
    die("You need to be logged in as admin");




$page['title'] = 'Updating Database - Archiving of Threads';


$step = isset($_GET['step']) ? round($_GET['step']) : 0;

switch($step)
{
    default:
    case 0:
        $page['body'] = '<p style="text-align:center">You are about to update the database to allow archiving of threads.</p>';
        $page['body'] .= '<p style="text-align:center"><a href="?step=2">Click here to update database entries.</a></p>';
    break;
	case 2:
		$page['body'] = '<p style="text-align:center">Database have been updated.</p>';

		$sql_errors = "";

		// Update posts_* table to archive function
		// Get list of boards	
		$boards = listBoards();
		foreach ($boards as &$_board) {
			$query = Element('archive.sql', array('board' => $_board['uri']));
			if (mysql_version() < 50503)
				$query = preg_replace('/(CHARSET=|CHARACTER SET )utf8mb4/', '$1utf8', $query);
			query($query) or error(db_error());

			// Create Archive Folders
			if (!file_exists($_board['dir'] . $config['dir']['archive']))
				@mkdir($_board['dir'] . $config['dir']['archive'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['archive'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['archive'] . $config['dir']['img']))
				@mkdir($_board['dir'] . $config['dir']['archive'] . $config['dir']['img'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['archive'] . $config['dir']['img'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['archive'] . $config['dir']['thumb']))
				@mkdir($_board['dir'] . $config['dir']['archive'] . $config['dir']['thumb'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['archive'] . $config['dir']['img'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['archive'] . $config['dir']['res']))
				@mkdir($_board['dir'] . $config['dir']['archive'] . $config['dir']['res'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['archive'] . $config['dir']['img'] . ". Check permissions.", true);
			// Create Featured threads Folders
			if (!file_exists($_board['dir'] . $config['dir']['featured']))
				@mkdir($_board['dir'] . $config['dir']['featured'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['featured'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['featured'] . $config['dir']['img']))
				@mkdir($_board['dir'] . $config['dir']['featured'] . $config['dir']['img'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['featured'] . $config['dir']['img'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['featured'] . $config['dir']['thumb']))
				@mkdir($_board['dir'] . $config['dir']['featured'] . $config['dir']['thumb'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['featured'] . $config['dir']['img'] . ". Check permissions.", true);
			if (!file_exists($_board['dir'] . $config['dir']['featured'] . $config['dir']['res']))
				@mkdir($_board['dir'] . $config['dir']['featured'] . $config['dir']['res'], 0777)
					or error("Couldn't create " . $_board['dir'] . $config['dir']['featured'] . $config['dir']['img'] . ". Check permissions.", true);
		}

		if (!empty($sql_errors))
		    $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database and hashing ip addresses.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';

		break;
}


echo Element('page.html', $page);

?>
<!-- There is probably a much better way to do this, but eh. -->
<link rel="stylesheet" type="text/css" href="stylesheets/style.css" />
