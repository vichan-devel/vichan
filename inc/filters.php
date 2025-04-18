<?php

/*
 *  Copyright (c) 2010-2013 Tinyboard Development Group
 */

use Vichan\Context;
use Vichan\Controller\FloodManager;

defined('TINYBOARD') or exit;

function do_filters(Context $ctx, array $post): void {
	$config = $ctx->get('config');

	if (empty($config['filters'])) {
		return;
	}

	$floodManager = $ctx->get(FloodManager::class);
	$filterResult = $floodManager->processPost($post);

	if ($filterResult) {
		$action = $filterResult['action'] ?? 'reject';

		if ($action === 'reject') {
			error($filterResult['message'] ?? _('Posting throttled by filter.'));
		} elseif ($action === 'ban') {
			Bans::new_ban(
				$post['ip'],
				$filterResult['reason'] ?? _('Banned by filter'),
				$filterResult['expires'] ?? false,
				$filterResult['all_boards'] ? false : $post['board'],
				-1
			);

			if ($filterResult['reject'] ?? true) {
				error(
					$filterResult['message']
					??
					_('You have been banned. <a href="/banned.php">Click here to view.</a>')
				);
			}
		}
	}
}
