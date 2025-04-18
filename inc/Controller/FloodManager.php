<?php

namespace Vichan\Controller;

use Vichan\Data\Driver\LogDriver;
use Vichan\Data\IpNoteQueries;
use Vichan\Service\FilterService;
use Vichan\Service\FloodService;

/**
 * Manages the overall process of checking posts against filters and flood controls.
 * Orchestrates FilterService and FloodService for incoming posts.
 */
class FloodManager {
	/**
	 * @var FilterService Service to apply configured filters to posts.
	 */
	private FilterService $filterService;

	/**
	 * @var FloodService Service to record post history for flood detection.
	 */
	private FloodService $floodService;

	/**
	 * @var IpNoteQueries Handles database operations for IP notes.
	 */
	private IpNoteQueries $notes;

	/**
	 * @var LogDriver Logger instance for recording errors.
	 */
	private LogDriver $logger;

	/**
	 * Constructor for FloodManager.
	 *
	 * @param FilterService $filterService The filter checking service.
	 * @param FloodService $floodService The flood recording and checking service.
	 * @param IpNoteQueries $notes The IP note database query handler.
	 * @param LogDriver $logger The logging service.
	 */
	public function __construct(
		FilterService $filterService,
		FloodService $floodService,
		IpNoteQueries $notes,
		LogDriver $logger
	) {
		$this->filterService = $filterService;
		$this->floodService = $floodService;
		$this->notes = $notes;
		$this->logger = $logger;
	}

	/**
	 * Processes an incoming post through the filter and flood system.
	 *
	 * Applies filters first. If a filter matches, returns the filter result
	 * If no filter matches, records a flood entry, purges old entries, and returns null.
	 * Handles critical errors internally.
	 *
	 * @param array<string, mixed> $post The post data array.
	 * @return array|null The matching filter configuration if found, otherwise null.
	 * 						Returns null also if a critical error occurs during processing.
	 */
	public function processPost(array $post): ?array {
		try {
			$filterResult = $this->filterService->applyFilters($post);

			if ($filterResult !== null) {
				if (isset($filterResult['add_note']) && $filterResult['add_note']) {
					$this->notes->add(
						$post['ip'], -1, 'Autoban message: ' . $post['body']
					);
				}
				return $filterResult;
			}

			$this->floodService->recordFloodEntry($post);

			$this->floodService->purgeOldEntries();

			return null;
		} catch (\Throwable $e) {
			$this->logger->log(
				LogDriver::ERROR,
				"Critical error in flood filtering system: " . $e->getMessage()
			);
			return null;
		}
	}
}
