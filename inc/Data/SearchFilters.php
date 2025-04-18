<?php
namespace Vichan\Data;


/**
 * POD with the fragments of each filter.
 */
class SearchFilters {
	/**
	 * @var array<array<string>>
	 */
	public array $body = [];
	/**
	 * @var array<string>
	 */
	public array $subject = [];
	/**
	 * @var array<string>
	 */
	public array $name = [];
	/**
	 * @var ?string
	 */
	public ?string $board = null;
	/**
	 * @var array<string>
	 */
	public array $flag = [];
	public ?int $id = null;
	public ?int $thread = null;
	public float $weight = 0;
}
