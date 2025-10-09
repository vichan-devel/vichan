<?php
namespace Vichan;


class Context {
	/**
	 * @var array<string, mixed>
	 */
	private array $definitions;

	/**
	 * @param array<string, mixed> $definitions
	 */
	public function __construct(array $definitions) {
		$this->definitions = $definitions;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get(string $name): mixed {
		if (!isset($this->definitions[$name])) {
			throw new \RuntimeException("Could not find a dependency named $name");
		}

		$ret = $this->definitions[$name];
		if (\is_callable($ret) && !\is_string($ret) && !\is_array($ret)) {
			$ret = $ret($this);
			$this->definitions[$name] = $ret;
		}
		return $ret;
	}
}
