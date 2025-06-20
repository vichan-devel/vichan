<?php

namespace Vichan\Service;

use Vichan\Data\Driver\LogDriver;

class BannersService {
	private const BANNERS_DIR = 'static/banners/%s/';
	private const PRIORITY_DIR = 'static/banners_priority/';
	private const UKKO = 'ukko';
	private array $allowed_exts;
	private LogDriver $logger;

	public function __construct(array $exts, LogDriver $logger) {
		$this->allowed_exts = $exts;
		$this->logger = $logger;
	}

	private function getFilesInDirectory(string $dir): array {
		if (!\is_dir($dir)) {
			$this->logger->log(
				LogDriver::WARNING,
				'Trying to fetch images from a non existent directory, falling back to priority dir'
			);
			$dir = self::PRIORITY_DIR;
		}

		$listFiles = \array_diff(\scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
		$listFiles = \array_filter($listFiles, fn ($file) => \is_file($dir . $file) && $this->isImage($file));

		return $listFiles;
	}

	private function isImage(string $fileName): bool {
		$extension = \strtolower(\pathinfo($fileName, PATHINFO_EXTENSION));
		return \in_array($extension, $this->allowed_exts, true);
	}

	private function serveRandomBanner(string $dir, array $files): void {
		if ($files === []) {
			\http_response_code(404);
			exit;
		}

		$name = $files[\array_rand($files)];
		$filePath = $dir . $name;

		if (!\is_file($filePath) || !\is_readable($filePath)) {
			\http_response_code(404);
			exit;
		}

		$ext = \pathinfo((string) $name, PATHINFO_EXTENSION);
		$lastModified = \filemtime($filePath);
		$etag = \md5_file($filePath);

		\header("Content-Type: image/{$ext}");
		\header("Content-Length: " . \filesize($filePath));
		\header("Cache-Control: public, max-age=" . (60 * 60 * 24 * 30 * 6)); // 6 months
		\header("ETag: \"$etag\"");
		\header("Last-Modified: " . \gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
		\header("X-Content-Type-Options: nosniff");

		$ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '';
		$ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

		if (
			(!empty($ifModifiedSince) && \strtotime((string) $ifModifiedSince) === $lastModified) ||
			(!empty($ifNoneMatch) && \trim((string) $ifNoneMatch) === $etag)
		) {
			\header("HTTP/1.1 304 Not Modified");
			exit;
		}

		\readfile($filePath);
		exit;
	}

	public function serve(string $board): void {
		if (!\getBoardInfo($board)) {
			$this->logger->log(
				LogDriver::WARNING,
				'Trying to fetch images from a non existent board, falling back to ukko'
			);
			$board = self::UKKO;
		}

		$priorityFiles = $this->getFilesInDirectory(self::PRIORITY_DIR);
		$bannerDir = \sprintf(self::BANNERS_DIR, $board);
		$bannerFiles = $this->getFilesInDirectory($bannerDir);

		$usePriority = $priorityFiles !== [] && (\mt_rand(0, 3) === 0 || $bannerFiles === [] || $board === self::UKKO);

		if ($usePriority) {
			$this->serveRandomBanner(self::PRIORITY_DIR, $priorityFiles);
		} else {
			$this->serveRandomBanner($bannerDir, $bannerFiles);
		}
	}
}