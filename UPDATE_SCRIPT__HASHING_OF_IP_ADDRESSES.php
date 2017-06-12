<?php



// require 'inc/config.php';
// require 'inc/config_instance.php';
require 'inc/functions.php';


global $config;

// Check so only ADMIN can run script
check_login(true);
if (!$mod || $mod['type'] != ADMIN)
    die("You need to be logged in as admin");



// Set timelimit to what it is for rebuild
@set_time_limit($config['mod']['rebuild_timelimit']);


$page['title'] = 'Updating Database Hashing IP';


$step = isset($_GET['step']) ? round($_GET['step']) : 0;

switch($step)
{
    default:
    case 0:
        $page['body'] = '<p style="text-align:center">You are about to update the entries in the database to hashed version.<br/>THIS IS CAH NOT BE UNDONE!<br/>If any error occure during update and you run the update again some ip data might be wrong in db and not recoverable.</p>';
        $page['body'] .= '<p style="text-align:center"><a href="?step=2">Click here to update database entries. WARNING: UPDATE IS IRREVERSABLE.</a></p>';
    break;
	case 2:
		$page['body'] = '<p style="text-align:center">All database IP entries have been hashed.</p>';

		$sql_errors = "";

		// Update bans table to hashed ip 
		$query = prepare("SELECT DISTINCT `ipstart` FROM ``bans`` WHERE `ipstart` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``bans`` SET `ipstart` = :ip, `ipend` = NULL WHERE `ipstart` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ipstart']));
		    $update_query->bindValue(':ip_org', $entry['ipstart']);
		    $update_query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
		}

		// Update custom_geoip table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``custom_geoip`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>custom_geoip<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``custom_geoip`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error() . '</li>';
		}

		// Update flood table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``flood`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``flood`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';
		}


		// Update ip_notes table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``ip_notes`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``ip_notes`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';
		}


		// Update modlogs table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``modlogs`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``modlogs`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';
		}

		// Update mutes table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``mutes`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``mutes`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';
		}

		// Update posts_* table to hashed ip
		// Get list of boards	
		$boards = listBoards();
		foreach ($boards as &$_board) {
		    $query = prepare(sprintf("SELECT DISTINCT `ip` FROM ``posts_%s`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'", $_board['uri']));
		    $query->execute() or $sql_errors .= '<li>posts_*<br/>' . db_error() . '</li>';

		    while($entry = $query->fetch()) {
		        $update_query = prepare(sprintf("UPDATE ``posts_%s`` SET `ip` = :ip WHERE `ip` = :ip_org", $_board['uri']));
		        $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		        $update_query->bindValue(':ip_org', $entry['ip']);
		        $update_query->execute() or $sql_errors .= '<li>Alter posts_*<br/>' . db_error() . '</li>';
		    }
		}
			// Update reports table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``reports`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``reports`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';
		}
		// Update nicenotices table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``nicenotices`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter nicenotices<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``nicenotices`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $query->bindValue(':ip_org', $entry['ip']);
		    $query->execute() or $sql_errors .= '<li>Alter nicenotices<br/>' . db_error() . '</li>';
		}
		// Update search_queries table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``search_queries`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``search_queries`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $query->bindValue(':ip_org', $entry['ip']);
		    $query->execute() or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';
		}
		// Update warnings table to hashed ip
		$query = prepare("SELECT DISTINCT `ip` FROM ``warnings`` WHERE `ip` REGEXP '^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$'");
		$query->execute() or $sql_errors .= '<li>Alter warnings<br/>' . db_error() . '</li>';

		while($entry = $query->fetch()) {
		    $update_query = prepare("UPDATE ``warnings`` SET `ip` = :ip WHERE `ip` = :ip_org");
		    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
		    $update_query->bindValue(':ip_org', $entry['ip']);
		    $update_query->execute() or $sql_errors .= '<li>Alter warnings<br/>' . db_error() . '</li>';
		}

		if (!empty($sql_errors))
		    $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database and hashing ip addresses.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';

		break;
}


echo Element('page.html', $page);

?>
<!-- There is probably a much better way to do this, but eh. -->
<link rel="stylesheet" type="text/css" href="stylesheets/style.css" />
