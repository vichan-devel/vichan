<?php

use Vichan\Service\SearchService;

require 'inc/bootstrap.php';

if (!$config['search']['enable']) {
	die(_("Post search is disabled"));
}

$ctx = Vichan\build_context($config);
$search_service = $ctx->get(SearchService::class);

if (isset($_GET['search']) && !empty($_GET['search'])) {
	$raw_search = $_GET['search'];
	$ip = $_SERVER['REMOTE_ADDR'];
	$fallback_board = (isset($_GET['board']) && !empty($_GET['board'])) ? $_GET['board'] : null;


	if ($search_service->checkFlood($ip, $raw_search)) {
		error(_('Wait a while before searching again, please.'));
	}

	// Actually do the search.
	$parse_res = $search_service->parse($raw_search);
	$filters = $search_service->reduceAndWeight($parse_res);
	$search_res = $search_service->search($ip, $raw_search, $filters, $fallback_board);

	// Needed to set a global variable further down the stack, plus the template.
	$actual_board = $filters->board ?? $fallback_board;

	$body = Element('search_form.html', [
		'boards' => $search_service->getSearchableBoards(),
		'board' => $actual_board,
		'search' => \str_replace('"', '&quot;', utf8tohtml($_GET['search'])),
		'flags_enabled' => $search_service->isFlagFilterEnabled()
	]);

	if ($search_res === null) {
		$body .= '<hr/><p style="text-align:center" class="unimportant">(' . _('Query too broad.') . ')</p>';
	} elseif (empty($search_res)) {
		$body .= '<hr/><p style="text-align:center" class="unimportant">(' . _('No results.') . ')</p>';
	} else {
		$body .= '<hr/>';

		openBoard($actual_board);

		$posts_html = '';
		foreach ($search_res as $post) {
			if (!$post['thread']) {
				$po = new Thread($post);
			} else {
				$po = new Post($post);
			}
			$posts_html .= $po->build(true) . '<hr/>';
		}

		$body .= '<fieldset><legend>' .
			sprintf(ngettext('%d result in', '%d results in', \count($search_res)), \count($search_res)) . ' <a href="/' .
			sprintf($config['board_path'], $board['uri']) . $config['file_index'] . '">' .
			sprintf($config['board_abbreviation'], $board['uri']) . ' - ' . $board['title'] .
			'</a></legend>' . $posts_html . '</fieldset>';
	}
} else {
	$body = Element('search_form.html', [
		'boards' => $search_service->getSearchableBoards(),
		'board' => false,
		'search' => false,
		'flags_enabled' => $search_service->isFlagFilterEnabled()
	]);
}

echo Element('page.html', Array(
	'config'=>$config,
	'title'=> _('Search'),
	'body'=> $body
));
