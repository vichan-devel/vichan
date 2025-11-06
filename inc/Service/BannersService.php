<?php

namespace Vichan\Service;

use Vichan\Data\Driver\LogDriver;
use Vichan\Data\Model\ImageType;


class BannersService {
	private const BANNERS_DIR = 'static/banners/';
	private const PRIORITY_DIR = 'static/banners_priority/';
	private LogDriver $logger;
	private int $priority_denominator;

	private static function isImage(string $fileName): bool {
		// For speed reasons, we trust the extension.
		$extension = \strtolower(\pathinfo($fileName, PATHINFO_EXTENSION));
		return \in_array($extension, ImageType::KNOWN_WEB_IMAGE_EXT, true);
	}

	private static function getFilesInDirectory(string $dir): array {
		return \array_diff(\scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
	}

	private static function serveBanner(string $filePath): void {
		header("Location: $filePath", true, 307);
		header('Cache-Control: no-cache');
		exit;
	}

	/**
	 * @param LogDriver $logger Driver to write logs
	 * @param int $priority_denominator The denominator over the likelihood of a priory banner being chosen.
	 *                                  Must be >= 0. Use 0 to disable priority banners (except as a fallback).
	 */
	public function __construct(LogDriver $logger, int $priority_denominator) {
		$this->logger = $logger;
		$this->priority_denominator = $priority_denominator;
	}

	/**
	 * Select a banner file to serve
	 * @param string $dir The directory the files belong to.
	 * @param array $fileNames The file names
	 * @return ?string Path to the selected file, if a suitable one is found.
	 */
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
			return $filePath;
		}
		return null;
	}

	public function serve(string $subdir): void {
		$usePriority = empty($subdir) || ($this->priority_denominator > 0 && \mt_rand(0, $this->priority_denominator) === 0);

		if (!$usePriority) {
			$bannerDir = self::BANNERS_DIR . $subdir . '/';

			if (\is_dir($bannerDir)) {
				$names = self::getFilesInDirectory($bannerDir);
				$filePath = $this->selectFile($bannerDir, $names);
				if ($filePath !== null) {
					self::serveBanner($filePath);
				}
			}
		}

		$names = self::getFilesInDirectory(self::PRIORITY_DIR);
		$filePath = $this->selectFile(self::PRIORITY_DIR, $names);
		if ($filePath !== null) {
			self::serveBanner( $filePath);
		} else {
			$this->logger->log(LogDriver::ERROR, "No suitable image for banner found!");
			\http_response_code(404);
			exit;
		}
	}
}
