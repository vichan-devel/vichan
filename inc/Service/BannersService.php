<?php

namespace Vichan\Service;

use Vichan\Data\Driver\LogDriver;
use Vichan\Data\Model\ImageType;

class BannersService {
	private const BANNERS_DIR = 'static/banners/';
	private const PRIORITY_DIR = 'static/banners_priority/';
	private const UKKO = 'ukko';
	private LogDriver $logger;

	private static function isImage(string $fileName): bool {
		// For speed reasons, we trust the extension.
		$extension = \strtolower(\pathinfo($fileName, PATHINFO_EXTENSION));
		return \in_array($extension, ImageType::KNOWN_WEB_IMAGE_EXT, true);
	}

	private static function getFilesInDirectory(string $dir): array {
		return \array_diff(\scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
	}

	public function __construct(LogDriver $logger) {
		$this->logger = $logger;
	}

	private function selectFile(string $dir, array $fileNames): ?string {
		if (empty($fileNames)) {
			return null;
		}
		$offset = \mt_rand(0, \count($fileNames));
		for ($i = 0; $i < \count($fileNames); $i++) {
			$j = ($offset + $i) % \count($fileNames);
			$name = $fileNames[$j];
			$filePath = $dir . $name;

			if (!\is_file($filePath)) {
				$this->logger->log(LogDriver::ERROR, "Banner '{$filePath}' is not file");
				continue;
			}
			if (!\is_readable($filePath)) {
				$this->logger->log(LogDriver::ERROR, "Banner '{$filePath}' is not readable");
				continue;
			}
			if (!self::isImage($filePath)) {
				$this->logger->log(LogDriver::ERROR, "Banner '{$filePath}' is not an valid image");
				continue;
			}
			return $name;
		}
		return null;
	}

	private function serveBanner(string $dir, string $name): void {
		$filePath = $dir . $name;

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

	public function serve(string $subdir): void {
		$usePriority = empty($subdir) || \mt_rand(0, 3) === 0;

		if (!$usePriority) {
			$bannerDir = self::BANNERS_DIR . $subdir . '/';

			if (\is_dir($bannerDir)) {
				$names = self::getFilesInDirectory($bannerDir);
				$name = $this->selectFile($bannerDir, $names);
				if ($name !== null) {
					$this->serveBanner($bannerDir, $name);
				}
			}
		}

		$names = self::getFilesInDirectory(self::PRIORITY_DIR);
		$name = $this->selectFile(self::PRIORITY_DIR, $names);
		if ($name !== null) {
			$this->serveBanner(self::PRIORITY_DIR, $name);
		} else {
			$this->logger->log(LogDriver::ERROR, "No suitable image for banner found!");
			\http_response_code(404);
			exit;
		}
	}
}
