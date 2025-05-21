<?php

namespace Vichan\Twig\FilesystemCache;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Twig\Cache\FilesystemCache;

class TinyboardFilesystem extends FilesystemCache
{
	private $directory;

	/**
	 * {@inheritdoc}
	 */
	public function __construct($directory, $options = 0)
	{
		parent::__construct($directory, $options);
		$this->directory = $directory;
	}

	/**
	 * This function was removed in Twig 2.x due to developer views on the Twig library. Who says we can't keep it for ourselves though?
	 */
	public function clear()
	{
		foreach (new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->directory),
			RecursiveIteratorIterator::LEAVES_ONLY
		) as $file) {
			if ($file->isFile()) {
				@unlink($file->getPathname());
			}
		}
	}
}
