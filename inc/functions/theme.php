<?php
namespace Vichan\Functions\Theme;


function rebuild_themes(string $action, $boardname = false): void {
	global $config, $board, $current_locale;

	// Save the global variables
	$_config = $config;
	$_board = $board;

	// List themes
	if ($themes = \Cache::get("themes")) {
		// OK, we already have themes loaded
	} else {
		$query = query("SELECT `theme` FROM ``theme_settings`` WHERE `name` IS NULL AND `value` IS NULL") or error(db_error());
		$themes = $query->fetchAll(\PDO::FETCH_NUM);

		\Cache::set("themes", $themes);
	}

	foreach ($themes as $theme) {
		// Restore them
		$config = $_config;
		$board = $_board;

		// Reload the locale
		if ($config['locale'] != $current_locale) {
			$current_locale = $config['locale'];
			init_locale($config['locale']);
		}

		if (PHP_SAPI === 'cli') {
			echo "Rebuilding theme ".$theme[0]."... ";
		}

		rebuild_theme($theme[0], $action, $boardname);

		if (PHP_SAPI === 'cli') {
			echo "done\n";
		}
	}

	// Restore them again
	$config = $_config;
	$board = $_board;

	// Reload the locale
	if ($config['locale'] != $current_locale) {
		$current_locale = $config['locale'];
		init_locale($config['locale']);
	}
}

function load_theme_config($_theme) {
	global $config;

	if (!file_exists($config['dir']['themes'] . '/' . $_theme . '/info.php')) {
		return false;
	}

	// Load theme information into $theme
	include $config['dir']['themes'] . '/' . $_theme . '/info.php';

	return $theme;
}

function rebuild_theme($theme, string $action, $board = false) {
	global $config, $_theme;
	$_theme = $theme;

	$theme = load_theme_config($_theme);

	if (file_exists($config['dir']['themes'] . '/' . $_theme . '/theme.php')) {
		require_once $config['dir']['themes'] . '/' . $_theme . '/theme.php';

		$theme['build_function']($action, theme_settings($_theme), $board);
	}
}

function theme_settings($theme): array {
	if ($settings = \Cache::get("theme_settings_" . $theme)) {
		return $settings;
	}

	$query = prepare("SELECT `name`, `value` FROM ``theme_settings`` WHERE `theme` = :theme AND `name` IS NOT NULL");
	$query->bindValue(':theme', $theme);
	$query->execute() or error(db_error($query));

	$settings = [];
	while ($s = $query->fetch(\PDO::FETCH_ASSOC)) {
		$settings[$s['name']] = $s['value'];
	}

	\Cache::set("theme_settings_".$theme, $settings);

	return $settings;
}
