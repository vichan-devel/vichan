<?php
namespace Vichan\Data;


/**
 * A page of user posts.
 */
class PageFetchResult {
	/**
	 * @var array[array] Posts grouped by board uri.
	 */
	public array $by_uri;
	public ?string $cursor_prev;
	public ?string $cursor_next;
}
