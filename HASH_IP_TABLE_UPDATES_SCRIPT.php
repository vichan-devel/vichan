<?php



// require 'inc/config.php';
// require 'inc/config_instance.php';
require 'inc/functions.php';


global $config;

// Check so only ADMIN can run script
check_login(true);
if (!$mod || $mod['type'] != ADMIN)
    die("You need to be logged in as admin");




$page['title'] = 'Updating Database Hashing IP';


$step = isset($_GET['step']) ? round($_GET['step']) : 0;

switch($step)
{
    default:
    case 0:
        $page['body'] = '<p style="text-align:center">You are about to update database to be able to handle hashed version of ip addresses.</p><p><a href="?step=2">Click here to update database tables.</a></p>';
    break;
    case 2:
        $page['body'] = '<p style="text-align:center">All database tables have been updated to hashed version.</p>';

        $sql_errors = "";

        // Update bans table to accept hashed ip 
        // query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
        // query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARBINARY(61) NOT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARBINARY(61) NULL DEFAULT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

        // Update custom_goip table to accept hashed ip
        // query("ALTER TABLE ``custom_geoip`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``custom_geoip`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NOT NULL") or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error() . '</li>';

        // Update flood table to accept hashed ip
        // query("ALTER TABLE ``flood`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``flood`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii COLLATE ascii_bin NOT NULL") or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';

        // Update ip_notes table to accept hashed ip
        // query("ALTER TABLE ``ip_notes`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``ip_notes`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL") or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';

        // Update modlogs table to accept hashed ip
        // query("ALTER TABLE ``modlogs`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``modlogs`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL") or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';

        // Update mutes table to accept hashed ip
        // query("ALTER TABLE ``mutes`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``mutes`` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL") or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';

        // Update posts_* table to accept hashed ip
        // Get list of boards	
        $boards = listBoards();
        foreach ($boards as &$_board) {
            // Increase field sizes
            // query(sprintf("ALTER TABLE `posts_%s` CHANGE `ip` `ip`  VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL", $_board['uri'])) or $sql_errors .= '<li>Alter posts_%s<br/>' . db_error() . '</li>';
            query(sprintf("ALTER TABLE `posts_%s` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NOT NULL", $_board['uri'])) or $sql_errors .= '<li>Alter posts_%s<br/>' . db_error() . '</li>';
        }

        // Update reports table to accept hashed ip
        // query("ALTER TABLE `reports` CHANGE `ip` `ip`  VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';
        query("ALTER TABLE `reports` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL") or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';
		
        // Update nicenotices table to accept hashed ip
        // query("ALTER TABLE `nicenotices` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter nicenotices<br/>' . db_error() . '</li>';
        query("ALTER TABLE `nicenotices` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET utf8mb4 COLLATE utf8_general_ci NOT NULL") or $sql_errors .= '<li>Alter nicenotices<br/>' . db_error() . '</li>';

        // Update search_queries table to accept hashed ip
        // query("ALTER TABLE `search_queries` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';
        query("ALTER TABLE `search_queries` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET utf8mb4 COLLATE utf8_general_ci NOT NULL") or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';

        // Update warnings table to accept hashed ip
        // query("ALTER TABLE `warnings` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter warnings<br/>' . db_error() . '</li>';
        query("ALTER TABLE `warnings` CHANGE `ip` `ip` VARCHAR(61) CHARACTER SET utf8mb4 COLLATE utf8_general_ci NOT NULL") or $sql_errors .= '<li>Alter warnings<br/>' . db_error() . '</li>';


        if (!empty($sql_errors))
            $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database and hashing ip addresses.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';
        


        $page['body'] .= '<p style="text-align:center">You are about to update the entries in the database to hashed version.<br/>THIS IS CAH NOT BE UNDONE!<br/>If any error occure during update and you run the update again some ip data might be wrong in db and not recoverable.</p>';
        $page['body'] .= '<p><a href="?step=3">Click here to update database entries. WARNING: UPDATE IS IRREVERSABLE.</a></p>';


        break;
        case 3:
            $page['body'] = '<p style="text-align:center">All database entries have been updated to hashed version.</p>';

            $sql_errors = "";

            // Update bans table to accept hashed ip 
            $query = prepare("SELECT DISTINCT `ipstart` FROM ``bans``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``bans`` SET `ipstart` = :ip, `ipend` = NULL WHERE `ipstart` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ipstart']));
                $update_query->bindValue(':ip_org', $entry['ipstart']);
                $update_query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
            }

            // Update custom_goip table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``custom_geoip``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``custom_geoip`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error() . '</li>';
            }

            // Update flood table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``flood``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``flood`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';
            }


            // Update ip_notes table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``ip_notes``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``ip_notes`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';
            }


            // Update modlogs table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``modlogs``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``modlogs`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';
            }

            // Update mutes table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``mutes``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``mutes`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';
            }

            // Update posts_* table to accept hashed ip
            // Get list of boards	
            $boards = listBoards();
            foreach ($boards as &$_board) {
                $query = prepare(sprintf("SELECT DISTINCT `ip` FROM ``posts_%s``", $_board['uri']));
                $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

                while($entry = $query->fetch()) {
                    $update_query = prepare(sprintf("UPDATE ``posts_%s`` SET `ip` = :ip WHERE `ip` = :ip_org", $_board['uri']));
                    $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                    $update_query->bindValue(':ip_org', $entry['ip']);
                    $update_query->execute() or $sql_errors .= '<li>Alter posts_*<br/>' . db_error() . '</li>';
                }
            }


            // Update reports table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``reports``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``reports`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';
            }
			
            // Update nicenotices table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``nicenotices``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``nicenotices`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $update_query->bindValue(':ip', get_ip_hash($entry['ip']));
                $update_query->bindValue(':ip_org', $entry['ip']);
                $update_query->execute() or $sql_errors .= '<li>Alter nicenotices<br/>' . db_error() . '</li>';
            }

            if (!empty($sql_errors))
                $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database and hashing ip addresses.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';
			
            // Update search_queries table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``search_queries``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

            while($entry = $query->fetch()) {
                $update_query = prepare("UPDATE ``search_queries`` SET `ip` = :ip WHERE `ip` = :ip_org");
                $query->bindValue(':ip', get_ip_hash($entry['ip']));
                $query->bindValue(':ip_org', $entry['ip']);
                $query->execute() or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';
            }


            // Update warnings table to accept hashed ip
            $query = prepare("SELECT DISTINCT `ip` FROM ``warnings``");
            $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

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