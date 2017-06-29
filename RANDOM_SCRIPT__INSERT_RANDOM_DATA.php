<?php



// require 'inc/config.php';
// require 'inc/config_instance.php';
require 'inc/functions.php';


global $config, $pdo;

// Check so only ADMIN can run script
check_login(true);
if (!$mod || $mod['type'] != ADMIN)
    die("You need to be logged in as admin");



// Set timelimit to what it is for rebuild
@set_time_limit($config['mod']['rebuild_timelimit']);


$page['title'] = 'Insert Random Data in Boards';


$step = isset($_GET['step']) ? round($_GET['step']) : 0;

switch($step)
{
    default:
    case 0:
        $page['body'] = '<p style="text-align:center">You are about to insert random data in boards for testing purposes.</p>';
        $page['body'] .= '<p style="text-align:center"><a href="?step=2">Click here to continue.</a></p>';
    break;
	case 2:
		$page['body'] = '<p style="text-align:center">Random Data have been Generated.</p>';

		$sql_errors = "";
		$file_errors = "";

		// Seed Random
		srand(time());

		// Update posts_* table to archive function
		// Get list of boards	
		$boards = listBoards();
		foreach ($boards as &$_board) {
			// Make "Threads"
			srand(time());
			for($i=0; $i<50; $i++) {
				$time_devi = rand(0, 60*60*24*14);
				$time = time() - $time_devi;
				$query = sprintf("INSERT INTO ``posts_%s`` VALUES (NULL, NULL, 'Tread Test #%d', NULL, NULL, NULL, NULL, 'Thread Body #%d', 'Thread Body #%d', %d, NULL, NULL, 0, NULL, NULL, '1234abcd1234abcd1234abcd', '1234abcd1234abcd1234abcd', 0, 0, 0, 0, NULL, NULL)", $_board['uri'], $i, $i, $i, $time);
				$query = query($query) or $sql_errors .= sprintf("<li>Add Dummy Thread %s<br/>", $_board['uri']) . db_error() . '</li>';

				// Make Relpies to thread
				srand(time());
				$thread_id = $pdo->lastInsertId();
				$reply_count = rand(0, 100);
				for($y=0; $y<$reply_count; $y++) {
					$time = time() - rand(0, $time_devi);
					$query = sprintf("INSERT INTO ``posts_%s`` VALUES (NULL, %d, 'Tread Test #%d Reply #%d', NULL, NULL, NULL, NULL, 'Reply Body #%d',  'Reply Body #%d',  %d, NULL, NULL, 0, NULL, NULL, '1234abcd1234abcd1234abcd', '1234abcd1234abcd1234abcd', 0, 0, 0, 0, NULL, NULL)", $_board['uri'], $thread_id, $thread_id, $y, $y, $y, $time);
					$query = query($query) or $sql_errors .= sprintf("<li>Add Dummy Thread #%d Reply %s<br/>", $thread_id, $_board['uri']) . db_error() . '</li>';
				}
			}
		}

		if (!empty($sql_errors))
		    $page['body'] .= '<div class="ban"><h2>SQL errors</h2><p>SQL errors were encountered when trying to update the database.</p><p>The errors encountered were:</p><ul>' . $sql_errors . '</ul></div>';
		if (!empty($file_errors))
		    $page['body'] .= '<div class="ban"><h2>File System errors</h2><p>File System errors were encountered when trying to create folders.</p><p>The errors encountered were:</p><ul>' . $file_errors . '</ul></div>';

		break;
}


echo Element('page.html', $page);

?>
<!-- There is probably a much better way to do this, but eh. -->
<link rel="stylesheet" type="text/css" href="stylesheets/style.css" />
