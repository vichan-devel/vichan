<?php
// A script to create public statistics file

require dirname(__FILE__) . '/inc/cli.php';

if (!isset ($argv[1])) {
	die("Usage: tools/public_statistics_cli.php stat_file\n");
}

$stat_file = $argv[1];


// Build lost of boards listed at top of page (visible boards)
$board_list = listBoards(false);
$boards = array();
if($config['public_stat']['boards'] === true) {
    $boards = $board_list;
} else if($config['public_stat']['boards'] === false) {
    foreach($board_list as $board) {
        if(in_array_r($board['uri'], $config['boards'], true))
            $boards[] = $board;
    }
} else if(is_array($config['public_stat']['boards'])) {
    foreach($board_list as $board) {
        if(in_array($board['uri'], $config['public_stat']['boards'], true))
            $boards[] = $board;
    }
} else {
    error("Board list config is corrupt.");
}

if(count($boards) == 0) {
    error("No boards to show stat for.");
}


// Write Main Stat File
file_write($config['dir']['home'] . $stat_file, statpage(false, $boards, $stat_file));

// Write Stat File for Each Board
foreach($boards as $board) {
    file_write($config['dir']['home'] . sprintf($config['board_path'], $board['uri']) . $stat_file, statpage($board['uri'], $boards, $stat_file));
}

echo("done\n");



// Build statistic page
function statpage($board = false, $boards, $stat_file) {
    global $config;
    
    // Get Statistic from db
    $statistics_hour = $config['public_stat']['hourly']?Statistic::get_stat_24h($board, false, $boards):false;
    $this_week = Statistic::get_stat_week(false, $board, false, $config['public_stat']['hourly'], $boards);
    $prev_week = Statistic::get_stat_week(true, $board, false, $config['public_stat']['hourly'], $boards);

    return Element('page.html', array(
        'config' => $config,
        'mod' => false,  
        'hide_dashboard_link' => true,
        'title' => _("Statistics") . ($board?" for /" . $board . "/":""),
        'subtitle' => _("Last Updated : ") . gmstrftime($config['public_stat']['date']),
        'nojavascript' => true,
        'boardlist' => createBoardlist(false),
        'body' => Element('mod/statistics.html', array(
            'mod' => false,
            'boards' => $boards,

            'stat_filename' => $stat_file,

            'public_hourly' => $config['public_stat']['hourly'],
		    'statistics_24h' => $statistics_hour,

            'statistics_week_labels' => Statistic::get_stat_week_labels($this_week),
            'statistics_week' => Statistic::get_stat_week_jsdata($this_week),
            'statistics_week_past' => Statistic::get_stat_week_jsdata($prev_week)
        ))
    ));
}


function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

?>