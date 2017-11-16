<?php
	require 'inc/functions.php';
	
	if (!$config['search']['enable']) {
		die(_("Post search is disabled"));
	}

	$queries_per_minutes = $config['search']['queries_per_minutes'];
	$queries_per_minutes_all = $config['search']['queries_per_minutes_all'];
	$search_limit = $config['search']['search_limit'];
	
	if (isset($config['search']['boards'])) {
		$boards = $config['search']['boards'];
	} else {
		$boards = listBoards(TRUE);
	}
	
	$body = Element('search_form.html', Array('boards' => $boards, 'board' => isset($_GET['board']) ? $_GET['board'] : false, 'search' => isset($_GET['search']) ? str_replace('"', '&quot;', utf8tohtml($_GET['search'])) : false));
	
	if(isset($_GET['search']) && !empty($_GET['search']) && isset($_GET['board']) && in_array($_GET['board'], $boards)) {		
		$phrase = $_GET['search'];
		$_body = '';
		
		$query = prepare("SELECT COUNT(*) FROM ``search_queries`` WHERE `ip` = :ip AND `time` > :time");
		$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
		$query->bindValue(':time', time() - ($queries_per_minutes[1] * 60));
		$query->execute() or error(db_error($query));
		if($query->fetchColumn() > $queries_per_minutes[0])
			error(_('Wait a while before searching again, please.'));
		
		$query = prepare("SELECT COUNT(*) FROM ``search_queries`` WHERE `time` > :time");
		$query->bindValue(':time', time() - ($queries_per_minutes_all[1] * 60));
		$query->execute() or error(db_error($query));
		if($query->fetchColumn() > $queries_per_minutes_all[0])
			error(_('Wait a while before searching again, please.'));
			
		
		$query = prepare("INSERT INTO ``search_queries`` VALUES (:ip, :time, :query)");
		$query->bindValue(':ip', $_SERVER['REMOTE_ADDR']);
		$query->bindValue(':time', time());
		$query->bindValue(':query', $phrase);
		$query->execute() or error(db_error($query));
		
		_syslog(LOG_NOTICE, 'Searched /' . $_GET['board'] . '/ for "' . $phrase . '"');

		// Cleanup search queries table
		$query = prepare("DELETE FROM ``search_queries`` WHERE `time` <= :time");
		$query->bindValue(':time', time() - ($queries_per_minutes_all[1] * 60));
                $query->execute() or error(db_error($query));
		
		openBoard($_GET['board']);
		
		$filters = Array();
		
		function search_filters($m) {
			global $filters;
			$name = $m[2];
			$value = isset($m[4]) ? $m[4] : $m[3];
			
			if(!in_array($name, array('id', 'thread', 'subject', 'name'))) {
				// unknown filter
				return $m[0];
			}
			
			$filters[$name] = $value;
			
			return $m[1];
		}
		
		$phrase = trim(preg_replace_callback('/(^|\s)(\w+):("(.*)?"|[^\s]*)/', 'search_filters', $phrase));
		
		if(!preg_match('/[^*^\s]/', $phrase) && empty($filters)) {
			_syslog(LOG_WARNING, 'Query too broad.');
			$body .= '<p class="unimportant" style="text-align:center">(Query too broad.)</p>';
			echo Element('page.html', Array(
				'config'=>$config,
				'title'=>'Search',
				'body'=>$body,
			));
			exit;
		}
		
		// Escape escape character
		$phrase = str_replace('!', '!!', $phrase);
		
		// Remove SQL wildcard
		$phrase = str_replace('%', '!%', $phrase);
		
		// Use asterisk as wildcard to suit convention
		$phrase = str_replace('*', '%', $phrase);
		
		// Remove `, it's used by table prefix magic
		$phrase = str_replace('`', '!`', $phrase);

		$like = '';
		$match = Array();
		
		// Find exact phrases
		if(preg_match_all('/"(.+?)"/', $phrase, $m)) {
			foreach($m[1] as &$quote) {
				$phrase = str_replace("\"{$quote}\"", '', $phrase);
				$match[] = $pdo->quote($quote);
			}
		}
		
		$words = explode(' ', $phrase);
		foreach($words as &$word) {
			if(empty($word))
				continue;
			$match[] = $pdo->quote($word);
		}
		
		$like = '';
		foreach($match as &$phrase) {
			if(!empty($like))
				$like .= ' AND ';
			$phrase = preg_replace('/^\'(.+)\'$/', '\'%$1%\'', $phrase);
			$like .= '`body` LIKE ' . $phrase . ' ESCAPE \'!\'';
		}
		
		foreach($filters as $name => $value) {
			if(!empty($like))
				$like .= ' AND ';
			$like .= '`' . $name . '` = '. $pdo->quote($value);
		}
		
		$like = str_replace('%', '%%', $like);
		
		$total = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE " . $like, $board['uri']));
		$total->execute() or error(db_error($query));
		
		// How many items to list per page
        $limit = $config['search']['search_results_per_page'];
        // How many pages will there be
        $pages = ceil($total->rowCount() / $limit);
        // What page are we currently on?
        $page = min($pages, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, array(
            'options' => array(
                'default'   => 1,
                'min_range' => 1,
            ),
        )));
        // Calculate the offset for the query
        $offset = ($page - 1)  * $limit;
        // Some information to display to the user
        $start = $offset + 1;
        $end = min(($offset + $limit), $total->rowCount());
        $url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        // The "back" link
        $prevlink = ($page > 1) ? '<a href="'. $url .'&page=1" title="First page"> &laquo;</a> <a href="'. $url .'&page=' . ($page - 1) . '" title="Previous Page ">&lsaquo; Previous </a>' : '<span class="disabled">&laquo;</span> <span class="disabled">&lsaquo; </span>';
        // The "forward" link
        $nextlink = ($page < $pages) ? '<a href="' . $url . '&page=' . ($page + 1) . '" title="Next page"> Next &rsaquo;</a> <a href="' . $url . '&page=' . $pages . '" title="Last page">&raquo;</a>' : '<span class="disabled">&rsaquo;</span> <span class="disabled">&raquo; </span>';
			
		$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE " . $like . " ORDER BY `time` DESC LIMIT :limit OFFSET :offset", $board['uri']));
		$query->bindValue(':limit', $limit, PDO::PARAM_INT);
		$query->bindValue(':offset', $offset, PDO::PARAM_INT);
		$query->execute();
		
		if($query->rowCount() == $search_limit) {
			_syslog(LOG_WARNING, 'Query too broad.');
			$body .= '<p class="unimportant" style="text-align:center">('._('Query too broad.').')</p>';
			echo Element('page.html', Array(
				'config'=>$config,
				'title'=>'Search',
				'body'=>$body,
			));
			exit;
		}

		$temp = '';
		if ($query->rowCount() > 0) {
		while($post = $query->fetch()) {
			if(!$post['thread']) {
				$po = new Thread($post);
			} else {
				$po = new Post($post);
			}
			$temp .= $po->build(true) . '<hr/>';
		}
		$iterator = new IteratorIterator($query);
		
		if(!empty($temp)){
			$_body .= '<fieldset><legend>' .
					sprintf(ngettext( '%d result in', '%d results in', $total->rowCount()), 
					$total->rowCount()) . ' <a href="/' .
					sprintf($config['board_path'], $board['uri']) . $config['file_index'] . '">' .
			sprintf($config['board_abbreviation'], $board['uri']) . ' - ' . $board['title'] .
			'</a> ' . $prevlink . $page . ' of ' . $pages . ' pages, displaying ' . $start . '-' . $end . ' results ' . $nextlink . '</legend>' . $temp . 
			'<legend>' .
					sprintf(ngettext( '%d result in', '%d results in', $total->rowCount()), 
					$total->rowCount()) . ' <a href="/' .
					sprintf($config['board_path'], $board['uri']) . $config['file_index'] . '">' .
			sprintf($config['board_abbreviation'], $board['uri']) . ' - ' . $board['title'] .
			'</a> ' . $prevlink . $page  . ' of ' . $pages . ' pages, displaying ' . $start . '-' . $end . ' results ' . $nextlink . '</legend></fieldset>';
		}
		$body .= '<hr/>';
		if(!empty($_body)){
		    if($iterator) {
			$body .= $_body;
		    }
		}
		}else{
			$body .= '<p style="text-align:center" class="unimportant">('._('No results.').')</p>';
		}
	}
		
	echo Element('page.html', Array(
		'config'=>$config,
		'boardlist' => createBoardlist(),
		'title'=>_('Search'),
		'body'=>'' . $body 
	));
