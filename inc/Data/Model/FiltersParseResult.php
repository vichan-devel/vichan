<?php
namespace Vichan\Data\Model;


class FiltersParseResult {
	public array $body = [];
	public ?string $subject = null;
	public ?string $name = null;
	public ?string $board = null;
	public ?string $flag = null;
	public ?int $id = null;
	public ?int $thread = null;
}
