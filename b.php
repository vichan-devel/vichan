<?php

use Vichan\Service\BannersService;

require_once 'inc/bootstrap.php';
use function Vichan\build_context;

$board = htmlspecialchars($_GET['board'] ?? $config['banner_ukko'], ENT_QUOTES, 'UTF-8');
$ctx = build_context($config);
$banners = $ctx->get(BannersService::class);
$banners->serve($board);