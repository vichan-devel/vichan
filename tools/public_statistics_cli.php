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
foreach($board_list as $board) {
    if(in_array_r($board['uri'], $config['boards'], true))
        $boards[] = $board;
}


// Write Main Stat File
file_write($config['dir']['home'] . $stat_file, statppage(false, $boards, $stat_file));

// Write Stat File for Each Board
foreach($boards as $board) {
    file_write($config['dir']['home'] . sprintf($config['board_path'], $board['uri']) . $stat_file, statppage($board['uri'], $boards, $stat_file));
}

echo("done\n");



// Build statistic page
function statppage($board = false, $boards, $stat_file) {
    global $config;
    
    // Get Statistic from db
    $this_week = Statistic::get_stat_week(false, $board, false, $boards);
    $prev_week = Statistic::get_stat_week(true, $board, false, $boards);

    return Element('page.html', array(
        'config' => $config,
        'mod' => false,  
        'hide_dashboard_link' => true,
        'title' => _("Statistics") . ($board?" for " . $board:""),
        'subtitle' => "",
        'nojavascript' => true,
        'boardlist' => createBoardlist(false),
        'body' => Element('mod/statistics.html', array(
            'mod' => false,
            'boards' => $boards,

            'stat_filename' => $stat_file,

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