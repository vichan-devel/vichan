<?php

// Installation/upgrade file	
define('VERSION', '5.1.4');
require 'inc/bootstrap.php';
loadConfig();

// Salt generators
class SaltGen {
	public $salt_length = 128;

	// Best function I could think of for non-SSL PHP 5
	private function generate_install_salt() {
		$ret = "";

		// This is bad! But what else can we do sans OpenSSL?
		for ($i = 0; $i < $this->salt_length; ++$i) {
			$s = pack("c", mt_rand(0,255));
			$ret = $ret . $s;
		}

		return base64_encode($ret);
	}

	// Best function of the lot. Works with any PHP version as long as OpenSSL extension is on
	private function generate_install_salt_openssl() {
		$ret = openssl_random_pseudo_bytes($this->salt_length, $strong);
		if (!$strong) {
			error(_("Misconfigured system: OpenSSL returning weak salts. Cannot continue."));
		}
		return base64_encode($ret);
	}

	private function generate_install_salt_php7() {
		return base64_encode(random_bytes($this->salt_length));
	}

	// TODO: Perhaps add mcrypt as an option? Maybe overkill.
	public function generate() {
		if (extension_loaded('openssl')) {
			return "OSSL." . $this->generate_install_salt_openssl();
		} else if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7) {
			return "PHP7." . $this->generate_install_salt_php7();
		} else {
			return "INSECURE." . $this->generate_install_salt();
		}
	}
}

$step = isset($_GET['step']) ? round($_GET['step']) : 0;
$page = array(
	'config' => $config,
	'title' => 'Install',
	'body' => '',
	'nojavascript' => true
);

// this breaks the display of licenses if enabled
$config['minify_html'] = false;

if (file_exists($config['has_installed'])) {
	
	// Check the version number
	$version = trim(file_get_contents($config['has_installed']));
	if (empty($version))
		$version = 'v0.9.1';
	
	function __query($sql) {
		sql_open();
		
		if (mysql_version() >= 50503)
			return query($sql);
		else
			return query(str_replace('utf8mb4', 'utf8', $sql));
	}
	
	$boards = listBoards();
	
	switch ($version) {
		case 'v0.9':
		case 'v0.9.1':
			// Upgrade to v0.9.2-dev
			
			foreach ($boards as &$_board) {
				// Add `capcode` field after `trip`
				query(sprintf("ALTER TABLE `posts_%s` ADD  `capcode` VARCHAR( 50 ) NULL AFTER  `trip`", $_board['uri'])) or error(db_error());
				
				// Resize `trip` to 15 characters
				query(sprintf("ALTER TABLE `posts_%s` CHANGE  `trip`  `trip` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.2-dev':
			// Upgrade to v0.9.2-dev-1
			
			// New table: `theme_settings`
			query("CREATE TABLE IF NOT EXISTS `theme_settings` ( `name` varchar(40) NOT NULL, `value` text, UNIQUE KEY `name` (`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;") or error(db_error());
			
			// New table: `news`
			query("CREATE TABLE IF NOT EXISTS `news` ( `id` int(11) NOT NULL AUTO_INCREMENT, `name` text NOT NULL, `time` int(11) NOT NULL, `subject` text NOT NULL, `body` text NOT NULL, UNIQUE KEY `id` (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;") or error(db_error());
		case 'v0.9.2.1-dev':
		case 'v0.9.2-dev-1':
			// Fix broken version number/mistake
			$version = 'v0.9.2-dev-1';
			// Upgrade to v0.9.2-dev-2
			
			foreach ($boards as &$_board) {
				// Increase field sizes
				query(sprintf("ALTER TABLE `posts_%s` CHANGE  `subject` `subject` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL", $_board['uri'])) or error(db_error());
				query(sprintf("ALTER TABLE `posts_%s` CHANGE  `name` `name` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.2-dev-2':
			// Upgrade to v0.9.2-dev-3 (v0.9.2)
			
			foreach ($boards as &$_board) {
				// Add `custom_fields` field
				query(sprintf("ALTER TABLE `posts_%s` ADD `embed` TEXT NULL", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.2-dev-3': // v0.9.2-dev-3 == v0.9.2
		case 'v0.9.2':
			// Upgrade to v0.9.3-dev-1
			
			// Upgrade `theme_settings` table
			query("TRUNCATE TABLE `theme_settings`") or error(db_error());
			query("ALTER TABLE  `theme_settings` ADD  `theme` VARCHAR( 40 ) NOT NULL FIRST") or error(db_error());
			query("ALTER TABLE  `theme_settings` CHANGE  `name`  `name` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL") or error(db_error());
			query("ALTER TABLE  `theme_settings` DROP INDEX  `name`") or error(db_error());
		case 'v0.9.3-dev-1':
			query("ALTER TABLE  `mods` ADD  `boards` TEXT NOT NULL") or error(db_error());
			query("UPDATE `mods` SET `boards` = '*'") or error(db_error());
		case 'v0.9.3-dev-2':
			foreach ($boards as &$_board) {
				query(sprintf("ALTER TABLE `posts_%s` CHANGE `filehash`  `filehash` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.3-dev-3':
			// Board-specifc bans
			query("ALTER TABLE `bans` ADD  `board` SMALLINT NULL AFTER  `reason`") or error(db_error());
		case 'v0.9.3-dev-4':
			// add ban ID
			query("ALTER TABLE `bans` ADD  `id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY ( `id` ), ADD UNIQUE (`id`)");
		case 'v0.9.3-dev-5':
			foreach ($boards as &$_board) {
				// Increase subject field size
				query(sprintf("ALTER TABLE `posts_%s` CHANGE  `subject` `subject` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.3-dev-6':
			// change to MyISAM
			$tables = array(
				'bans', 'boards', 'ip_notes', 'modlogs', 'mods', 'mutes', 'noticeboard', 'pms', 'reports', 'robot', 'theme_settings', 'news'
			);
			foreach ($boards as &$board) {
				$tables[] = "posts_{$board['uri']}";
			}
			
			foreach ($tables as &$table) {
				query("ALTER TABLE  `{$table}` ENGINE = MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci") or error(db_error());
			}
		case 'v0.9.3-dev-7':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  `posts_%s` CHANGE  `filename` `filename` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL", $board['uri'])) or error(db_error());
			}
		case 'v0.9.3-dev-8':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE `posts_%s` ADD INDEX (  `thread` )", $board['uri'])) or error(db_error());
			}
		case 'v0.9.3-dev-9':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE `posts_%s`ADD INDEX (  `time` )", $board['uri'])) or error(db_error());
				query(sprintf("ALTER TABLE `posts_%s`ADD FULLTEXT (`body`)", $board['uri'])) or error(db_error());
			}
		case 'v0.9.3-dev-10':
		case 'v0.9.3':
			query("ALTER TABLE  `bans` DROP INDEX `id`") or error(db_error());
			query("ALTER TABLE  `pms` DROP INDEX  `id`") or error(db_error());
			query("ALTER TABLE  `boards` DROP PRIMARY KEY") or error(db_error());
			query("ALTER TABLE  `reports` DROP INDEX  `id`") or error(db_error());
			query("ALTER TABLE  `boards` DROP INDEX `uri`") or error(db_error());
			
			query("ALTER IGNORE TABLE  `robot` ADD PRIMARY KEY (`hash`)") or error(db_error());
			query("ALTER TABLE  `bans` ADD FULLTEXT (`ip`)") or error(db_error());
			query("ALTER TABLE  `ip_notes` ADD INDEX (`ip`)") or error(db_error());	
			query("ALTER TABLE  `modlogs` ADD INDEX (`time`)") or error(db_error());
			query("ALTER TABLE  `boards` ADD PRIMARY KEY(`uri`)") or error(db_error());
			query("ALTER TABLE  `mutes` ADD INDEX (`ip`)") or error(db_error());
			query("ALTER TABLE  `news` ADD INDEX (`time`)") or error(db_error());
			query("ALTER TABLE  `theme_settings` ADD INDEX (`theme`)") or error(db_error());
		case 'v0.9.4-dev-1':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  `posts_%s` ADD  `sage` INT( 1 ) NOT NULL AFTER  `locked`", $board['uri'])) or error(db_error());
			}
		case 'v0.9.4-dev-2':
			if (!isset($_GET['confirm'])) {
				$page['title'] = 'License Change';
				$page['body'] = '<p style="text-align:center">You are upgrading to a version which uses an amended license. The licenses included with Tinyboard distributions prior to this version (v0.9.4-dev-2) are still valid for those versions, but no longer apply to this and newer versions.</p>' .
					'<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" disabled>' . htmlentities(file_get_contents('LICENSE.md')) . '</textarea>
					<p style="text-align:center">
						<a href="?confirm=1">I have read and understood the agreement. Proceed to upgrading.</a>
					</p>';
				
				file_write($config['has_installed'], 'v0.9.4-dev-2');
				
				break;
			}
		case 'v0.9.4-dev-3':
		case 'v0.9.4-dev-4':
		case 'v0.9.4':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  `posts_%s`
					CHANGE `subject` `subject` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
					CHANGE  `email`  `email` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
					CHANGE  `name`  `name` VARCHAR( 35 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL", $board['uri'])) or error(db_error());
			}
		case 'v0.9.5-dev-1':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  `posts_%s` ADD  `body_nomarkup` TEXT NULL AFTER  `body`", $board['uri'])) or error(db_error());
			}
			query("CREATE TABLE IF NOT EXISTS `cites` (  `board` varchar(8) NOT NULL,  `post` int(11) NOT NULL,  `target_board` varchar(8) NOT NULL,  `target` int(11) NOT NULL,  KEY `target` (`target_board`,`target`),  KEY `post` (`board`,`post`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;") or error(db_error());
		case 'v0.9.5-dev-2':
			query("ALTER TABLE  `boards` 
				CHANGE  `uri`  `uri` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				CHANGE  `title`  `title` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				CHANGE  `subtitle`  `subtitle` VARCHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL") or error(db_error());
		case 'v0.9.5-dev-3':
			// v0.9.5
		case 'v0.9.5':
			query("ALTER TABLE  `boards` 
				CHANGE  `uri`  `uri` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				CHANGE  `title`  `title` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
				CHANGE  `subtitle`  `subtitle` TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL") or error(db_error());
		case 'v0.9.6-dev-1':
			query("CREATE TABLE IF NOT EXISTS `antispam` (
				  `board` varchar(255) NOT NULL,
				  `thread` int(11) DEFAULT NULL,
				  `hash` bigint(20) NOT NULL,
				  `created` int(11) NOT NULL,
				  `expires` int(11) DEFAULT NULL,
				  `passed` smallint(6) NOT NULL,
				  PRIMARY KEY (`hash`),
				  KEY `board` (`board`,`thread`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;") or error(db_error());
		case 'v0.9.6-dev-2':
			query("ALTER TABLE `boards`
				DROP `id`,
				CHANGE  `uri`  `uri` VARCHAR( 120 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL") or error(db_error());
			query("ALTER TABLE  `bans` CHANGE  `board`  `board` VARCHAR( 120 ) NULL DEFAULT NULL") or error(db_error());
			query("ALTER TABLE  `reports` CHANGE  `board`  `board` VARCHAR( 120 ) NULL DEFAULT NULL") or error(db_error());
			query("ALTER TABLE  `modlogs` CHANGE  `board`  `board` VARCHAR( 120 ) NULL DEFAULT NULL") or error(db_error());
			foreach ($boards as $board) {
				$query = prepare("UPDATE `bans` SET `board` = :newboard WHERE `board` = :oldboard");
				$query->bindValue(':newboard', $board['uri']);
				$query->bindValue(':oldboard', $board['id']);
				$query->execute() or error(db_error($query));
				
				$query = prepare("UPDATE `modlogs` SET `board` = :newboard WHERE `board` = :oldboard");
				$query->bindValue(':newboard', $board['uri']);
				$query->bindValue(':oldboard', $board['id']);
				$query->execute() or error(db_error($query));
				
				$query = prepare("UPDATE `reports` SET `board` = :newboard WHERE `board` = :oldboard");
				$query->bindValue(':newboard', $board['uri']);
				$query->bindValue(':oldboard', $board['id']);
				$query->execute() or error(db_error($query));
			}
		case 'v0.9.6-dev-3':
			query("ALTER TABLE  `antispam` CHANGE  `hash`  `hash` CHAR( 40 ) NOT NULL") or error(db_error());
		case 'v0.9.6-dev-4':
			query("ALTER TABLE  `news` DROP INDEX  `id`, ADD PRIMARY KEY ( `id` )") or error(db_error());
		case 'v0.9.6-dev-5':
			query("ALTER TABLE  `bans` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			query("ALTER TABLE  `mods` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			query("ALTER TABLE  `news` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			query("ALTER TABLE  `noticeboard` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			query("ALTER TABLE  `pms` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			query("ALTER TABLE  `reports` CHANGE  `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT") or error(db_error());
			foreach ($boards as $board) {
				query(sprintf("ALTER TABLE  `posts_%s` CHANGE `id`  `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT", $board['uri'])) or error(db_error());
			}
		case 'v0.9.6-dev-6':
			foreach ($boards as &$_board) {
				query(sprintf("CREATE INDEX `thread_id` ON `posts_%s` (`thread`, `id`)", $_board['uri'])) or error(db_error());
				query(sprintf("ALTER TABLE `posts_%s` DROP INDEX `thread`", $_board['uri'])) or error(db_error());
			}
		case 'v0.9.6-dev-7':
		case 'v0.9.6-dev-7 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0-gold</a>':
			query("ALTER TABLE  `bans` ADD  `seen` BOOLEAN NOT NULL") or error(db_error());
		case 'v0.9.6-dev-8':
		case 'v0.9.6-dev-8 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0.1</a>':
		case 'v0.9.6-dev-8 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0.2</a>':
			query("ALTER TABLE  `mods` CHANGE  `password`  `password` CHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT  'SHA256'") or error(db_error());
			query("ALTER TABLE  `mods` ADD  `salt` CHAR( 32 ) NOT NULL AFTER  `password`") or error(db_error());
			$query = query("SELECT `id`,`password` FROM `mods`") or error(db_error());
			while ($user = $query->fetch(PDO::FETCH_ASSOC)) {
				if (strlen($user['password']) == 40) {
					mt_srand(microtime(true) * 100000 + memory_get_usage(true));
					$salt = md5(uniqid(mt_rand(), true));
			
					$user['salt'] = $salt;
					$user['password'] = hash('sha256', $user['salt'] . $user['password']);
			
					$_query = prepare("UPDATE `mods` SET `password` = :password, `salt` = :salt WHERE `id` = :id");
					$_query->bindValue(':id', $user['id']);
					$_query->bindValue(':password', $user['password']);
					$_query->bindValue(':salt', $user['salt']);
					$_query->execute() or error(db_error($_query));
				}
			}
				case 'v0.9.6-dev-9':
		case 'v0.9.6-dev-9 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0.3</a>':
		case 'v0.9.6-dev-9 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0.4-gold</a>':
		case 'v0.9.6-dev-9 + <a href="https://github.com/vichan-devel/Tinyboard/">vichan-devel-4.0.5-gold</a>':
			foreach ($boards as &$board) {
				__query(sprintf("ALTER TABLE `posts_%s`
					CHANGE `subject` `subject` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `email` `email` VARCHAR(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `name` `name` VARCHAR(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `trip` `trip` VARCHAR(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `capcode` `capcode` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `body` `body` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
					CHANGE `body_nomarkup` `body_nomarkup` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `thumb` `thumb` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `thumbwidth` `thumbwidth` INT(11) NULL DEFAULT NULL,
					CHANGE `thumbheight` `thumbheight` INT(11) NULL DEFAULT NULL,
					CHANGE `file` `file` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `filename` `filename` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `filehash` `filehash` TEXT CHARACTER SET ascii COLLATE ascii_general_ci NULL DEFAULT NULL,
					CHANGE `password` `password` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE `ip` `ip` VARCHAR(39) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
					CHANGE `embed` `embed` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;", $board['uri'])) or error(db_error());
			}
			
			__query("ALTER TABLE  `antispam`
				CHANGE  `board`  `board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `hash`  `hash` CHAR( 40 ) CHARACTER SET ASCII COLLATE ascii_bin NOT NULL ,
				DEFAULT CHARACTER SET ASCII COLLATE ascii_bin;") or error(db_error());
			__query("ALTER TABLE  `bans`
				CHANGE  `ip`  `ip` VARCHAR( 39 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `reason`  `reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
				CHANGE  `board`  `board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NULL DEFAULT NULL,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `boards`
				CHANGE  `uri`  `uri` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `title`  `title` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `subtitle`  `subtitle` TINYTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `cites`
				CHANGE  `board`  `board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `target_board`  `target_board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;") or error(db_error());
			__query("ALTER TABLE  `ip_notes`
				CHANGE  `ip`  `ip` VARCHAR( 39 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `body`  `body` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `modlogs`
				CHANGE  `ip`  `ip` VARCHAR( 39 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `board`  `board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NULL DEFAULT NULL ,
				CHANGE  `text`  `text` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `mods`
				CHANGE  `username`  `username` VARCHAR( 30 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `password`  `password` CHAR( 64 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL COMMENT 'SHA256',
				CHANGE  `salt`  `salt` CHAR( 32 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `boards`  `boards` TEXT CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `mutes`
				CHANGE  `ip`  `ip` VARCHAR( 39 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;") or error(db_error());
			__query("ALTER TABLE  `news`
				CHANGE  `name`  `name` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `subject`  `subject` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `body`  `body` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `noticeboard`
				CHANGE  `subject`  `subject` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `body`  `body` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `pms`
				CHANGE  `message`  `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `reports`
				CHANGE  `ip`  `ip` VARCHAR( 39 ) CHARACTER SET ASCII COLLATE ascii_general_ci NOT NULL ,
				CHANGE  `board`  `board` VARCHAR( 120 ) CHARACTER SET ASCII COLLATE ascii_general_ci NULL DEFAULT NULL ,
				CHANGE  `reason`  `reason` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or error(db_error());
			__query("ALTER TABLE  `robot`
				CHANGE  `hash`  `hash` VARCHAR( 40 ) CHARACTER SET ASCII COLLATE ascii_bin NOT NULL COMMENT  'SHA1',
				DEFAULT CHARACTER SET ASCII COLLATE ascii_bin;") or error(db_error());
			__query("ALTER TABLE  `theme_settings`
				CHANGE  `theme`  `theme` VARCHAR( 40 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL ,
				CHANGE  `name`  `name` VARCHAR( 40 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
				CHANGE  `value`  `value` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ,
				DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;") or eror(db_error());
		case 'v0.9.6-dev-10':
			query("ALTER TABLE  `antispam`
				CHANGE  `board`  `board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;") or error(db_error());
			query("ALTER TABLE  `bans`
				CHANGE  `board`  `board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;") or error(db_error());
			query("ALTER TABLE  `boards`
				CHANGE  `uri`  `uri` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;") or error(db_error());
			query("ALTER TABLE  `cites`
				CHANGE  `board`  `board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
				CHANGE  `target_board`  `target_board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
				DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;") or error(db_error());
			query("ALTER TABLE  `modlogs`
				CHANGE  `board`  `board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;") or error(db_error());
			query("ALTER TABLE  `mods`
				CHANGE  `boards`  `boards` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;") or error(db_error());
			query("ALTER TABLE  `reports`
				CHANGE  `board`  `board` VARCHAR( 58 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;") or error(db_error());
		case 'v0.9.6-dev-11':
		case 'v0.9.6-dev-11 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.6</a>':
		case 'v0.9.6-dev-11 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.7-gold</a>':
		case 'v0.9.6-dev-11 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.8-gold</a>':
		case 'v0.9.6-dev-11 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.9-gold</a>':
			foreach ($boards as &$board) {
				__query(sprintf("ALTER TABLE  ``posts_%s``
					CHANGE  `thumb`  `thumb` VARCHAR( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
					CHANGE  `file`  `file` VARCHAR( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL ;",
					$board['uri'])) or error(db_error());
			}
		case 'v0.9.6-dev-12':
		case 'v0.9.6-dev-12 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.10</a>':
		case 'v0.9.6-dev-12 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.11-gold</a>':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  ``posts_%s`` ADD INDEX `ip` (`ip`)", $board['uri'])) or error(db_error());
			}
		case 'v0.9.6-dev-13':
			query("ALTER TABLE ``antispam`` ADD INDEX `expires` (`expires`)") or error(db_error());
		case 'v0.9.6-dev-14':
		case 'v0.9.6-dev-14 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.12</a>':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  ``posts_%s``
					DROP INDEX `body`,
					ADD INDEX `filehash` (`filehash`(40))", $board['uri'])) or error(db_error());
			}
			query("ALTER TABLE ``modlogs`` ADD INDEX `mod` (`mod`)") or error(db_error());
			query("ALTER TABLE ``bans`` DROP INDEX `ip`") or error(db_error());
			query("ALTER TABLE ``bans`` ADD INDEX `ip` (`ip`)") or error(db_error());
			query("ALTER TABLE ``noticeboard`` ADD INDEX `time` (`time`)") or error(db_error());
			query("ALTER TABLE ``pms`` ADD INDEX `to` (`to`, `unread`)") or error(db_error());
		case 'v0.9.6-dev-15':
			foreach ($boards as &$board) {
				query(sprintf("ALTER TABLE  ``posts_%s``
					ADD INDEX `list_threads` (`thread`, `sticky`, `bump`)", $board['uri'])) or error(db_error());
			}
		case 'v0.9.6-dev-16':
		case 'v0.9.6-dev-16 + <a href="https://int.vichan.net/devel/">vichan-devel-4.0.13</a>':
			query("ALTER TABLE ``bans`` ADD INDEX `seen` (`seen`)") or error(db_error());
		case 'v0.9.6-dev-17':
			query("ALTER TABLE ``ip_notes``
				DROP INDEX `ip`,
				ADD INDEX `ip_lookup` (`ip`, `time`)") or error(db_error());
		case 'v0.9.6-dev-18':
			query("CREATE TABLE IF NOT EXISTS ``flood`` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `ip` varchar(39) NOT NULL,
				  `board` varchar(58) CHARACTER SET utf8 NOT NULL,
				  `time` int(11) NOT NULL,
				  `posthash` char(32) NOT NULL,
				  `filehash` char(32) DEFAULT NULL,
				  `isreply` tinyint(1) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `ip` (`ip`),
				  KEY `posthash` (`posthash`),
				  KEY `filehash` (`filehash`),
				  KEY `time` (`time`)
				) ENGINE=MyISAM DEFAULT CHARSET=ascii COLLATE=ascii_bin AUTO_INCREMENT=1 ;") or error(db_error());
		case 'v0.9.6-dev-19':
			query("UPDATE ``mods`` SET `type` = 10 WHERE `type` = 0") or error(db_error());
			query("UPDATE ``mods`` SET `type` = 20 WHERE `type` = 1") or error(db_error());
			query("UPDATE ``mods`` SET `type` = 30 WHERE `type` = 2") or error(db_error());
			query("ALTER TABLE ``mods`` CHANGE `type`  `type` smallint(1) NOT NULL") or error(db_error());
		case 'v0.9.6-dev-20':
			__query("CREATE TABLE IF NOT EXISTS `bans_new_temp` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`ipstart` varbinary(16) NOT NULL,
				`ipend` varbinary(16) DEFAULT NULL,
				`created` int(10) unsigned NOT NULL,
				`expires` int(10) unsigned DEFAULT NULL,
				`board` varchar(58) DEFAULT NULL,
				`creator` int(10) NOT NULL,
				`reason` text,
				`seen` tinyint(1) NOT NULL,
				`post` blob,
				PRIMARY KEY (`id`),
				KEY `expires` (`expires`),
				KEY `ipstart` (`ipstart`,`ipend`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1") or error(db_error());
			$listquery = query("SELECT * FROM ``bans`` ORDER BY `id`") or error(db_error());
			while ($ban = $listquery->fetch(PDO::FETCH_ASSOC)) {
				$query = prepare("INSERT INTO ``bans_new_temp`` VALUES 
					(NULL, :ipstart, :ipend, :created, :expires, :board, :creator, :reason, :seen, NULL)");
				
				$range = Bans::parse_range($ban['ip']);
				if ($range === false) {
					// Invalid retard ban; just skip it.
					continue;
				}
				
				$query->bindValue(':ipstart', $range[0]);
				if ($range[1] !== false && $range[1] != $range[0])
					$query->bindValue(':ipend', $range[1]);
				else
					$query->bindValue(':ipend', null, PDO::PARAM_NULL);
				
				$query->bindValue(':created', $ban['set']);
				
				if ($ban['expires'])
					$query->bindValue(':expires', $ban['expires']);
				else
					$query->bindValue(':expires', null, PDO::PARAM_NULL);
				
				if ($ban['board'])
					$query->bindValue(':board', $ban['board']);
				else
					$query->bindValue(':board', null, PDO::PARAM_NULL);
				
				$query->bindValue(':creator', $ban['mod']);
				
				if ($ban['reason'])
					$query->bindValue(':reason', $ban['reason']);
				else
					$query->bindValue(':reason', null, PDO::PARAM_NULL);
				
				$query->bindValue(':seen', $ban['seen']);
				$query->execute() or error(db_error($query));
			}
			
			// Drop old bans table
			query("DROP TABLE ``bans``") or error(db_error());
			// Replace with new table
			query("RENAME TABLE ``bans_new_temp`` TO ``bans``") or error(db_error());
		case 'v0.9.6-dev-21':
		case 'v0.9.6-dev-21 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.90</a>':
			__query("CREATE TABLE IF NOT EXISTS ``ban_appeals`` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				  `ban_id` int(10) unsigned NOT NULL,
				  `time` int(10) unsigned NOT NULL,
				  `message` text NOT NULL,
				  `denied` tinyint(1) NOT NULL,
				  PRIMARY KEY (`id`),
				  KEY `ban_id` (`ban_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;") or error(db_error());
		case 'v0.9.6-dev-22':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.91</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.92</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.93</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.94</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.95</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.96</a>':
		case 'v0.9.6-dev-22 + <a href="https://int.vichan.net/devel/">vichan-devel-4.4.97</a>':
		case '4.4.97':
			if (!isset($_GET['confirm2'])) {
				$page['title'] = 'License Change';
				$page['body'] = '<p style="text-align:center">You are upgrading to a version which uses an amended license. The licenses included with vichan distributions prior to this version (4.4.98) are still valid for those versions, but no longer apply to this and newer versions.</p>' .
					'<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" disabled>' . htmlentities(file_get_contents('LICENSE.md')) . '</textarea>
					<p style="text-align:center">
						<a href="?confirm2=1">I have read and understood the agreement. Proceed to upgrading.</a>
					</p>';
				
				file_write($config['has_installed'], '4.4.97');
				
				break;
			}
		case '4.4.98-pre':
			if (!$twig) load_twig();
			$twig->clearCacheFiles();
		case '4.4.98':
		case '4.5.0':
		case '4.5.1':
		case '4.5.2':
			if (!isset($_GET['confirm3'])) {
				$page['title'] = 'Breaking change';
				$page['body'] = '<p style="text-align:center">You are upgrading to the 5.0 branch of vichan. Please back up your database, because the process is irreversible. At the current time, if you want a very stable vichan experience, please use the 4.5 branch. This warning will be lifted as soon as we all agree that 5.0 branch is stable enough</p>
					<p style="text-align:center">
						<a href="?confirm3=1">I have read and understood the warning. Proceed to upgrading.</a>
					</p>';
				
				file_write($config['has_installed'], '4.5.2');
				
				break;
			}

			foreach ($boards as &$board) {
				query(sprintf('ALTER TABLE ``posts_%s`` ADD `files` text DEFAULT NULL AFTER `bump`;', $board['uri'])) or error(db_error());
				query(sprintf('ALTER TABLE ``posts_%s`` ADD `num_files` int(11) DEFAULT 0 AFTER `files`;', $board['uri'])) or error(db_error());
				query(sprintf('UPDATE ``posts_%s`` SET `files` = CONCAT(\'[{"file":"\',`file`,\'", "thumb":"\',`thumb`,\'", "filename":"\',`filename`,\'", "size":"\',`filesize`,\'",  "width":"\',`filewidth`,\'","height":"\',`fileheight`,\'","thumbwidth":"\',`thumbwidth`,\'","thumbheight":"\',`thumbheight`,\'"}]\') WHERE `file` IS NOT NULL', $board['uri'], $board['uri'], $board['uri'])) or error(db_error());
				query(sprintf('UPDATE ``posts_%s`` SET `num_files` = 1 WHERE `file` IS NOT NULL', $board['uri'])) or error(db_error());
				query(sprintf('ALTER TABLE ``posts_%s`` DROP COLUMN `thumb`, DROP COLUMN `thumbwidth`, DROP COLUMN `thumbheight`, DROP COLUMN `file`, DROP COLUMN `fileheight`, DROP COLUMN `filesize`, DROP COLUMN `filewidth`, DROP COLUMN `filename`', $board['uri'])) or error(db_error());
				query(sprintf('REPAIR TABLE ``posts_%s``', $board['uri']));
			}
		case '4.9.90':
		case '4.9.91':
		case '4.9.92':
                        foreach ($boards as &$board) {
                                query(sprintf('ALTER TABLE ``posts_%s`` ADD `slug` VARCHAR(255) DEFAULT NULL AFTER `embed`;', $board['uri'])) or error(db_error());
			}
                case '4.9.93':
                        query('ALTER TABLE ``mods`` CHANGE `password` `password` VARCHAR(255) NOT NULL;') or error(db_error());
                        query('ALTER TABLE ``mods`` CHANGE `salt` `salt` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.0':
			query('ALTER TABLE ``mods`` CHANGE `salt` `version` VARCHAR(64) NOT NULL;') or error(db_error());
		case '5.0.1':
		case '5.1.0':
			query('CREATE TABLE IF NOT EXISTS ``pages`` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `board` varchar(255) DEFAULT NULL,
			  `name` varchar(255) NOT NULL,
			  `title` varchar(255) DEFAULT NULL,
			  `type` varchar(255) DEFAULT NULL,
			  `content` text,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `u_pages` (`name`,`board`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;') or error(db_error());
		case '5.1.1':
                        foreach ($boards as &$board) {
                                query(sprintf("ALTER TABLE ``posts_%s`` ADD `cycle` int(1) NOT NULL AFTER `locked`", $board['uri'])) or error(db_error());
                        }
		case '5.1.2':
			query('CREATE TABLE IF NOT EXISTS ``nntp_references`` (
				  `board` varchar(60) NOT NULL,
				  `id` int(11) unsigned NOT NULL,
				  `message_id` varchar(255) CHARACTER SET ascii NOT NULL,
				  `message_id_digest` varchar(40) CHARACTER SET ascii NOT NULL,
				  `own` tinyint(1) NOT NULL,
				  `headers` text,
				  PRIMARY KEY (`message_id_digest`),
				  UNIQUE KEY `message_id` (`message_id`),
				  UNIQUE KEY `u_board_id` (`board`, `id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			') or error(db_error());
		case '5.1.3':
			query('CREATE TABLE IF NOT EXISTS ``captchas`` (
			  	`cookie` varchar(50),
			  	`extra` varchar(200),
			  	`text` varchar(255),
			  	`created_at` int(11),
			  	PRIMARY KEY (`cookie`,`extra`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;') or error(db_error());
		case false:
			// TODO: enhance Tinyboard -> vichan upgrade path.
			query("CREATE TABLE IF NOT EXISTS ``search_queries`` (  `ip` varchar(39) NOT NULL,  `time` int(11) NOT NULL,  `query` text NOT NULL) ENGINE=MyISAM DEFAULT CHARSET=utf8;") or error(db_error());

			// Update version number
			file_write($config['has_installed'], VERSION);
			
			$page['title'] = 'Upgraded';
			$page['body'] = '<p style="text-align:center">Successfully upgraded from ' . $version . ' to <strong>' . VERSION . '</strong>.</p>';
			break;
		default:
			$page['title'] = 'Unknown version';
			$page['body'] = '<p style="text-align:center">vichan was unable to determine what version is currently installed.</p>';
			break;
		case VERSION:
			$page['title'] = 'Already installed';
			$page['body'] = '<p style="text-align:center">It appears that vichan is already installed (' . $version . ') and there is nothing to upgrade! Delete <strong>' . $config['has_installed'] . '</strong> to reinstall.</p>';
			break;
	}			
	
	die(Element('page.html', $page));
}

function create_config_from_array(&$instance_config, &$array, $prefix = '') {
	foreach ($array as $name => $value) {
		if (is_array($value)) {
			$instance_config .= "\n";
			create_config_from_array($instance_config, $value, $prefix . '[\'' . addslashes($name) . '\']');
			$instance_config .= "\n";
		} else {
			$instance_config .= '	$config' . $prefix . '[\'' . addslashes($name) . '\'] = ';

			if (is_numeric($value))
				$instance_config .= $value;
			else
				$instance_config .= "'" . addslashes($value) . "'";

			$instance_config .= ";\n";
		}
	}
}

session_start();

if ($step == 0) {
	// Agreeement
	$page['body'] = '
	<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black" disabled>' . htmlentities(file_get_contents('LICENSE.md')) . '</textarea>
	<p style="text-align:center">
		<a href="?step=1">I have read and understood the agreement. Proceed to installation.</a>
	</p>';
	
	echo Element('page.html', $page);
} elseif ($step == 1) {
	$page['title'] = 'Pre-installation test';
	
	$can_exec = true;
	if (!function_exists('shell_exec'))
		$can_exec = false;
	elseif (in_array('shell_exec', array_map('trim', explode(', ', ini_get('disable_functions')))))
		$can_exec = false;
	elseif (ini_get('safe_mode'))
		$can_exec = false;
	elseif (trim(shell_exec('echo "TEST"')) !== 'TEST')
		$can_exec = false;
	
	if (!defined('PHP_VERSION_ID')) {
		$version = explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	}
	
	// Required extensions
	$extensions = array(
		'PDO' => array(
			'installed' => extension_loaded('pdo'),
			'required' => true
		),
		'GD' => array(
			'installed' => extension_loaded('gd'),
			'required' => true
		),
		'Imagick' => array(
			'installed' => extension_loaded('imagick'),
			'required' => false
		),
		'OpenSSL' => array(
			'installed' => extension_loaded('openssl'),
			'required' => false
		)
	);

	$tests = array(
		array(
			'category' => 'PHP',
			'name' => 'PHP &ge; 5.4',
			'result' => PHP_VERSION_ID >= 50400,
			'required' => true,
			'message' => 'vichan requires PHP 5.4 or better.',
		),
		array(
			'category' => 'PHP',
			'name' => 'PHP &ge; 5.6',
			'result' => PHP_VERSION_ID >= 50600,
			'required' => false,
			'message' => 'vichan works best on PHP 5.6 or better.',
		),
		array(
			'category' => 'PHP',
			'name' => 'mbstring extension installed',
			'result' => extension_loaded('mbstring'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/mbstring.installation.php">mbstring</a> extension.',
		),
		array(
			'category' => 'PHP',
			'name' => 'OpenSSL extension installed or PHP &ge; 7.0',
			'result' => (extension_loaded('openssl') || (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION >= 7)),
			'required' => false,
			'message' => 'It is highly recommended that you install the PHP <a href="http://www.php.net/manual/en/openssl.installation.php">OpenSSL</a> extension and/or use PHP version 7 or above. <strong>If you do not, it is possible that the IP addresses of users of your site could be compromised &mdash; see <a href="https://github.com/vichan-devel/vichan/issues/284">vichan issue #284.</a></strong> Installing the OpenSSL extension allows vichan to generate a secure salt automatically for you.',
		),
		array(
			'category' => 'Database',
			'name' => 'PDO extension installed',
			'result' => extension_loaded('pdo'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.pdo.php">PDO</a> extension.',
		),
		array(
			'category' => 'Database',
			'name' => 'MySQL PDO driver installed',
			'result' => extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers()),
			'required' => true,
			'message' => 'The required <a href="http://www.php.net/manual/en/ref.pdo-mysql.php">PDO MySQL driver</a> is not installed.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD extension installed',
			'result' => extension_loaded('gd'),
			'required' => true,
			'message' => 'You must install the PHP <a href="http://www.php.net/manual/en/intro.image.php">GD</a> extension. GD is a requirement even if you have chosen another image processor for thumbnailing.',
		),
		array(
		 	'category' => 'Image processing',
		 	'name' => 'GD: JPEG',
			'result' => function_exists('imagecreatefromjpeg'),
			'required' => true,
			'message' => 'imagecreatefromjpeg() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD: PNG',
			'result' => function_exists('imagecreatefrompng'),
			'required' => true,
			'message' => 'imagecreatefrompng() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => 'GD: GIF',
			'result' => function_exists('imagecreatefromgif'),
			'required' => true,
			'message' => 'imagecreatefromgif() does not exist. This is a problem.',
		),
		array(
			'category' => 'Image processing',
			'name' => '`convert` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which convert'),
			'required' => false,
			'message' => '(Optional) `convert` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'convert'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`identify` (command-line ImageMagick)',
			'result' => $can_exec && shell_exec('which identify'),
			'required' => false,
			'message' => '(Optional) `identify` was not found or executable; command-line ImageMagick image processing cannot be enabled.',
		),
		array(
			'category' => 'Image processing',
			'name' => '`gm` (command-line GraphicsMagick)',
			'result' => $can_exec && shell_exec('which gm'),
			'required' => false,
			'message' => '(Optional) `gm` was not found or executable; command-line GraphicsMagick (faster than ImageMagick) cannot be enabled.',
			'effect' => function (&$config) { $config['thumb_method'] = 'gm'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`gifsicle` (command-line animted GIF thumbnailing)',
			'result' => $can_exec && shell_exec('which gifsicle'),
			'required' => false,
			'message' => '(Optional) `gifsicle` was not found or executable; you may not use `convert+gifsicle` for better animated GIF thumbnailing.',
			'effect' => function (&$config) { if ($config['thumb_method'] == 'gm')      $config['thumb_method'] = 'gm+gifsicle';
							  if ($config['thumb_method'] == 'convert') $config['thumb_method'] = 'convert+gifsicle'; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`md5sum` (quick file hashing on GNU/Linux)',
			'prereq' => '',
			'result' => $can_exec && shell_exec('echo "vichan" | md5sum') == "141225c362da02b5c359c45b665168de  -\n",
			'required' => false,
			'message' => '(Optional) `md5sum` was not found or executable; file hashing for multiple images will be slower. Ignore if not using Linux.',
			'effect' => function (&$config) { $config['gnu_md5'] = true; },
		),
		array(
			'category' => 'Image processing',
			'name' => '`/sbin/md5` (quick file hashing on BSDs)',
			'result' => $can_exec && shell_exec('echo "vichan" | /sbin/md5 -r') == "141225c362da02b5c359c45b665168de\n",
			'required' => false,
			'message' => '(Optional) `/sbin/md5` was not found or executable; file hashing for multiple images will be slower. Ignore if not using BSD.',
			'effect' => function (&$config) { $config['bsd_md5'] = true; },
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd(),
			'result' => is_writable('.'),
			'required' => true,
			'message' => 'vichan does not have permission to create directories (boards) here. You will need to <code>chmod</code> (or operating system equivalent) appropriately.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/templates/cache',
			'result' => is_writable('templates') || (is_dir('templates/cache') && is_writable('templates/cache')),
			'required' => true,
			'message' => 'You must give vichan permission to create (and write to) the <code>templates/cache</code> directory or performance will be drastically reduced.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/tmp/cache',
			'result' => is_dir('tmp/cache') && is_writable('tmp/cache'),
			'required' => true,
			'message' => 'You must give vichan permission to write to the <code>tmp/cache</code> directory.'
		),
		array(
			'category' => 'File permissions',
			'name' => getcwd() . '/inc/instance-config.php',
			'result' => is_writable('inc/instance-config.php'),
			'required' => false,
			'message' => 'vichan does not have permission to make changes to <code>inc/instance-config.php</code>. To complete the installation, you will be asked to manually copy and paste code into the file instead.'
		),
		array(
			'category' => 'Misc',
			'name' => 'Caching available (APC, XCache, Memcached or Redis)',
			'result' => extension_loaded('apc') || extension_loaded('xcache')
				|| extension_loaded('memcached') || extension_loaded('redis'),
			'required' => false,
			'message' => 'You will not be able to enable the additional caching system, designed to minimize SQL queries and significantly improve performance. <a href="http://php.net/manual/en/book.apc.php">APC</a> is the recommended method of caching, but <a href="http://xcache.lighttpd.net/">XCache</a>, <a href="http://www.php.net/manual/en/intro.memcached.php">Memcached</a> and <a href="http://pecl.php.net/package/redis">Redis</a> are also supported.'
		),
		array(
			'category' => 'Misc',
			'name' => 'vichan installed using git',
			'result' => is_dir('.git'),
			'required' => false,
			'message' => 'vichan is still beta software and it\'s not going to come out of beta any time soon. As there are often many months between releases yet changes and bug fixes are very frequent, it\'s recommended to use the git repository to maintain your vichan installation. Using git makes upgrading much easier.'
		)
	);

	$config['font_awesome'] = true;
	
	$additional_config = array();
	foreach ($tests as $test) {
		if ($test['result'] && isset($test['effect'])) {
			$test['effect']($additional_config);
		}
	}
	$more = '';
	create_config_from_array($more, $additional_config);
	$_SESSION['more'] = $more;

	echo Element('page.html', array(
		'body' => Element('installer/check-requirements.html', array(
			'extensions' => $extensions,
			'tests' => $tests,
			'config' => $config,
		)),
		'title' => 'Checking environment',
		'config' => $config,
	));
} elseif ($step == 2) {

	// Basic config
	$page['title'] = 'Configuration';

	$sg = new SaltGen();
	$config['cookies']['salt'] = $sg->generate();
	$config['secure_trip_salt'] = $sg->generate();
	
	echo Element('page.html', array(
		'body' => Element('installer/config.html', array(
			'config' => $config,
			'more' => $_SESSION['more'],
		)),
		'title' => 'Configuration',
		'config' => $config
	));
} elseif ($step == 3) {
	$more = $_POST['more'];
	unset($_POST['more']);

	$instance_config = 
'<'.'?php

/*
*  Instance Configuration
*  ----------------------
*  Edit this file and not config.php for imageboard configuration.
*
*  You can copy values from config.php (defaults) and paste them here.
*/

';
	
	create_config_from_array($instance_config, $_POST);
	
	$instance_config .= "\n";
	$instance_config .= $more;
	$instance_config .= "\n";
	
	if (@file_put_contents('inc/instance-config.php', $instance_config)) {
		// flushes opcache if php >= 5.5.0 or opcache is installed via PECL
		if (function_exists('opcache_invalidate')) {
			opcache_invalidate('inc/instance-config.php');
		}
		header('Location: ?step=4', true, $config['redirect_http']);
	} else {
		$page['title'] = 'Manual installation required';
		$page['body'] = '
			<p>I couldn\'t write to <strong>inc/instance-config.php</strong> with the new configuration, probably due to a permissions error.</p>
			<p>Please complete the installation manually by copying and pasting the following code into the contents of <strong>inc/instance-config.php</strong>:</p>
			<textarea style="width:700px;height:370px;margin:auto;display:block;background:white;color:black">' . htmlentities($instance_config) . '</textarea>
			<p style="text-align:center">
				<a href="?step=4">Once complete, click here to complete installation.</a>
			</p>
		';
		echo Element('page.html', $page);
	}
} elseif ($step == 4) {
	// SQL installation
	
	buildJavascript();
	
	$sql = @file_get_contents('install.sql') or error("Couldn't load install.sql.");
	
	sql_open();
	$mysql_version = mysql_version();
	
	// This code is probably horrible, but what I'm trying
	// to do is find all of the SQL queires and put them
	// in an array.
	preg_match_all("/(^|\n)((SET|CREATE|INSERT).+)\n\n/msU", $sql, $queries);
	$queries = $queries[2];
	
	$queries[] = Element('posts.sql', array('board' => 'b'));
	
	$sql_errors = '';
	foreach ($queries as $query) {
		if ($mysql_version < 50503)
			$query = preg_replace('/(CHARSET=|CHARACTER SET )utf8mb4/', '$1utf8', $query);
		$query = preg_replace('/^([\w\s]*)`([0-9a-zA-Z$_\x{0080}-\x{FFFF}]+)`/u', '$1``$2``', $query);
		if (!query($query))
			$sql_errors .= '<li>' . db_error() . '</li>';
	}
	
	$page['title'] = 'Installation complete';
	$page['body'] = '<p style="text-align:center">Thank you for using vichan. Please remember to report any bugs you discover. <a href="https://github.com/vichan-devel/vichan/wiki/Configuration-Basics">How do I edit the config files?</a></p>';
	
	if (!empty($sql_errors)) {
		$page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to install the database. This may be the result of using a database which is already occupied with a vichan installation; if so, you can probably ignore this.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul><p><a href="?step=5">Ignore errors and complete installation.</a></p></div>';
	} else {
		$boards = listBoards();
		foreach ($boards as &$_board) {
			setupBoard($_board);
			buildIndex();
		}
		
		file_write($config['has_installed'], VERSION);
		/*if (!file_unlink(__FILE__)) {
			$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
		}*/
	}
	
	echo Element('page.html', $page);
} elseif ($step == 5) {
	$page['title'] = 'Installation complete';
	$page['body'] = '<p style="text-align:center">Thank you for using vichan. Please remember to report any bugs you discover.</p>';
	
	$boards = listBoards();
	foreach ($boards as &$_board) {
		setupBoard($_board);
		buildIndex();
	}
	
	file_write($config['has_installed'], VERSION);
	if (!file_unlink(__FILE__)) {
		$page['body'] .= '<div class="ban"><h2>Delete install.php!</h2><p>I couldn\'t remove <strong>install.php</strong>. You will have to remove it manually.</p></div>';
	}
	
	echo Element('page.html', $page);
}

