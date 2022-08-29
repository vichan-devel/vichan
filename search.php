<?php
	require 'inc/bootstrap.php';
	
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
			echo Element($config['file_page_template'], Array(
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
			
		$query = prepare(sprintf("SELECT * FROM ``posts_%s`` WHERE " . $like . " ORDER BY `time` DESC LIMIT :limit", $board['uri']));
		$query->bindValue(':limit', $search_limit, PDO::PARAM_INT);
		$query->execute() or error(db_error($query));
		
		if($query->rowCount() == $search_limit) {
			_syslog(LOG_WARNING, 'Query too broad.');
			$body .= '<p class="unimportant" style="text-align:center">('._('Query too broad.').')</p>';
			echo Element($config['file_page_template'], Array(
				'config'=>$config,
				'title'=>'Search',
				'body'=>$body,
			));
			exit;
		}

		$temp = '';
		while($post = $query->fetch()) {
			if(!$post['thread']) {
				$po = new Thread($post);
			} else {
				$po = new Post($post);
			}
			$temp .= $po->build(true) . '<hr/>';
		}
		
		if(!empty($temp))
			$_body .= '<fieldset><legend>' .
					sprintf(ngettext('%d result in', '%d results in', $query->rowCount()), 
					$query->rowCount()) . ' <a href="/' .
					sprintf($config['board_path'], $board['uri']) . $config['file_index'] .
			'">' .
			sprintf($config['board_abbreviation'], $board['uri']) . ' - ' . $board['title'] .
			'</a></legend>' . $temp . '</fieldset>';
		
		$body .= '<hr/>';
		if(!empty($_body))
			$body .= $_body;
		else
			$body .= '<p style="text-align:center" class="unimportant">('._('No results.').')</p>';
	}
		
	echo Element($config['file_page_template'], Array(
		'config'=>$config,
		'title'=>_('Search'),
		'boardlist'=>createBoardlist(),
		'body'=>'' . $body
	));
