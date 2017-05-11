<?php



// require 'inc/config.php';
// require 'inc/config_instance.php';
require 'inc/functions.php';


global $config;


$page['title'] = 'Updating Database Hashing IP';



$step = isset($_GET['step']) ? round($_GET['step']) : 0;

switch($step)
{
    default:
    case 0:
        $page['body'] = '<p style="text-align:center">You are about to update to hashed version of database and database entries. <br/> THIS IS CAH NOT BE UNDONE! <br/>If any error occure during update and you run the update again some ip data might be wrong in db and not recoverable.</p> <p><a href="?step=2">Click here to update UPDATE IS IRREVERSABLE.</a></p></div>';
        break;
    case 2:
        $page['body'] = '<p style="text-align:center">All database entries have been updated to hashed version.</p>';

        $sql_errors = "";

        // Update bans table to accept hashed ip 
        query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';
        query("ALTER TABLE ``bans`` CHANGE `ipstart` `ipstart` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter bans<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``bans`` SET `ipstart` = MD5(AES_ENCRYPT(ipstart, UNHEX(SHA2(:aeskey, 512)))), `ipend` = NULL");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter bans<br/>' . db_error($query) . '</li>';

        // Update custom_goip table to accept hashed ip
        query("ALTER TABLE ``custom_geoip`` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``custom_geoip`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter custom_geoip<br/>' . db_error($query) . '</li>';


        // Update flood table to accept hashed ip
        query("ALTER TABLE ``flood`` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter flood<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``flood`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter flood<br/>' . db_error($query) . '</li>';


        // Update ip_notes table to accept hashed ip
        query("ALTER TABLE ``ip_notes`` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``ip_notes`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter ip_notes<br/>' . db_error($query) . '</li>';


        // Update modlogs table to accept hashed ip
        query("ALTER TABLE ``modlogs`` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter modlogs<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``modlogs`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter modlogs<br/>' . db_error($query) . '</li>';


        // Update mutes table to accept hashed ip
        query("ALTER TABLE ``mutes`` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter mutes<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``modlogs`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter mutes<br/>' . db_error($query) . '</li>';


        // Update posts_* table to accept hashed ip
        // Get list of boards	
        $boards = listBoards();
        foreach ($boards as &$_board) {
            // Increase field sizes
            query(sprintf("ALTER TABLE `posts_%s` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL", $_board['uri'])) or $sql_errors .= '<li>Alter posts_%s<br/>' . db_error() . '</li>';

            $query = prepare(sprintf("UPDATE ``posts_%s`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))", $_board['uri']));
            $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
            $query->execute() or $sql_errors .= '<li>Alter posts_%s<br/>' . db_error($query) . '</li>';
        }


        // Update reports table to accept hashed ip
        query("ALTER TABLE `reports` CHANGE `ip` `ip`  VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter reports<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``reports`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter reports<br/>' . db_error($query) . '</li>';



        // Update mutes table to accept hashed ip
        query("ALTER TABLE `search_queries` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter search_queries<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``search_queries`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter search_queries<br/>' . db_error($query) . '</li>';



        // Update warnings table to accept hashed ip
        query("ALTER TABLE `warnings` CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii NULL DEFAULT NULL") or $sql_errors .= '<li>Alter warnings<br/>' . db_error() . '</li>';

        $query = prepare("UPDATE ``search_queries`` SET `ip` = MD5(AES_ENCRYPT(ip, UNHEX(SHA2(:aeskey, 512))))");
        $query->bindValue(':aeskey', $config['db']['ip_encrypt_key']);
        $query->execute() or $sql_errors .= '<li>Alter warnings<br/>' . db_error($query) . '</li>';



        if (!empty($sql_errors))
            $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database and hashing ip addresses.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';

        break;
}


echo Element('page.html', $page);






?>