<?php

namespace Vichan\Service;

use Throwable;
use Vichan\Data\Driver\LogDriver;

/**
 * Applies filters to posts based on configured conditions.
 */
class FilterService {
	/**
	 * @var array<int, array<string, mixed>> Loaded filter configurations.
	 */
	private array $filters;

	/**
	 * @var FloodService Handles retrieval of flood data and checking flood conditions for filters.
	 */
	private FloodService $floodService;

	/**
	 * @var LogDriver Logger instance.
	 */
	private LogDriver $logger;


	/**
	 * Filter service constructor
	 *
	 * @param array<int, array<string, mixed>> $filters The config filters.
	 * @param FloodService $floodService The FloodService.
	 * @param LogDriver $logger The LogDriver.
	 */
	public function __construct(array $filters, FloodService $floodService, LogDriver $logger) {
		$this->filters = $filters;
		$this->floodService = $floodService;
		$this->logger = $logger;
	}

	/**
	 * Applies all filters to a post and returns the first matching filter.
	 *
	 * @param array<string, mixed> $post The post array.
	 * @return array|null The matching filter configuration or null if no filter matched.
	 * @throws \Throwable Only if there's a critical error that can't be handled internally
	 */
	public function applyFilters(array $post): ?array {
		$floodEntries = $this->floodService->getFloodEntries($post);

		foreach ($this->filters as $index => $filter) {
			try {
				if ($this->checkFilter($filter['condition'], $post, $floodEntries)) {
					return $filter;
				}
			} catch (\Throwable $e) {
				$this->logger->log(
					LogDriver::ERROR,
					"Filter #{$index} failed: {$e->getMessage()}\nDetails: " .
					\json_encode($filter, \JSON_PRETTY_PRINT)
				);
			}
		}

		return null;
	}

	/**
	 * Checks if a post matches all conditions defined within a single filter.
	 *
	 * @param array<string, mixed> $conditions The conditions for this specific filter.
	 * @param array $post The post data array.
	 * @param array<int, array<string, mixed>> $floodEntries Recent post history for flood checks.
	 * @return bool True if the post matches all conditions, false otherwise.
	 * @throws \RuntimeException If evaluation of a specific condition fails.
	 */
	private function checkFilter(array $conditions, array $post, array $floodEntries): bool {
		foreach ($conditions as $condition => $value) {
			try {
				$this->validateType($condition, 'string', 'filter condition');

				$negate = $condition[0] === '!';
				$conditionName = $negate ? \substr($condition, 1) : $condition;

				if (empty($conditionName)) {
					throw new \InvalidArgumentException("Empty condition name after negation");
				}

				$result = $this->matchCondition($conditionName, $value, $post, $floodEntries, $conditions);

				if ($result === $negate) {
					return false;
				}
			} catch (\Throwable $e) {
				throw new \RuntimeException(
					"Error in condition '$condition': {$e->getMessage()}",
					0,
					$e
				);
			}
		}

		return true;
	}

	/**
	 * Validates that a value is of a expected type.
	 *
	 * @param mixed $value The value to check.
	 * @param string $expectedType The required type ('string', 'array', 'numeric').
	 * @param string $fieldName A descriptive name of the field/value being checked for error messages.
	 * @throws \InvalidArgumentException If the type of $value does not match $expectedType.
	 */
	private function validateType(mixed $value, string $expectedType, string $fieldName): void {
		$isValid = false;

		switch ($expectedType) {
			case 'string':
				$isValid = \is_string($value);
				break;
			case 'array':
				$isValid = \is_array($value);
				break;
			case 'numeric':
				$isValid = \is_numeric($value);
				break;
			default:
				break;
		}

		if (!$isValid) {
			throw new \InvalidArgumentException(
				"Filter configuration error: '$fieldName' must be type '{$expectedType}', but got '" . \gettype($value) . "'."
			);
		}
	}

	/**
	 * Performs a regex match and throws an exception on PCRE errors.
	 *
	 * @param string $pattern The regex pattern.
	 * @param string $subject The string to match against.
	 * @param string $fieldName Contextual name for error messages (e.g., 'IP', 'body').
	 * @return bool True if the pattern matches, false otherwise.
	 * @throws \InvalidArgumentException If the regex pattern is invalid.
	 */
	private function checkRegex(string $pattern, string $subject, string $fieldName): bool {
		$result = @\preg_match($pattern, $subject);

		if ($result === false) {
			throw new \InvalidArgumentException(
				"Invalid regex pattern for '{$fieldName}' ('{$pattern}'): " . $this->getPregErrorMessage()
			);
		}

		return (bool)$result;
	}

	/**
	 * Returns a human-readable description of the last regex error.
	 *
	 * @return string The error description.
	 */
	private function getPregErrorMessage(): string {
		$error = \preg_last_error();
		switch ($error) {
			case \PREG_NO_ERROR:
				return "No error";
			case \PREG_INTERNAL_ERROR:
				return "Internal PCRE error";
			case \PREG_BACKTRACK_LIMIT_ERROR:
				return "Backtrack limit exceeded";
			case \PREG_RECURSION_LIMIT_ERROR:
				return "Recursion limit exceeded";
			case \PREG_BAD_UTF8_ERROR:
				return "Invalid UTF-8 data";
			case \PREG_BAD_UTF8_OFFSET_ERROR:
				return "Invalid UTF-8 offset";
			case \PREG_JIT_STACKLIMIT_ERROR:
				return "JIT stack limit exceeded";
			default:
				return "Unknown PCRE error ($error)";
		}
	}

	/**
	 * Dispatches the condition check to the appropriate matching method.
	 *
	 * @param string $condition The name of the condition to check (e.g., 'ip', 'body', 'flood-count').
	 * @param mixed $value The value associated with the condition in the filter config.
	 * @param array<string, mixed> $post The post data array.
	 * @param array<int, array<string, mixed>> $floodEntries The recent post history.
	 * @param array<string, mixed> $allConditions All conditions for the current filter (needed for dependencies like flood-count/flood-time).
	 * @return bool True if the condition matches, false otherwise.
	 * @throws \InvalidArgumentException If regex is invalid.
	 * @throws \RuntimeException For errors during custom callbacks or flood condition checks.
	 */
	private function matchCondition(
		string $condition,
		mixed $value,
		array $post,
		array $floodEntries,
		array $allConditions
	): bool {
		$conditionLower = \strtolower($condition);

		switch ($conditionLower) {
			case 'custom':

				return $this->matchCustom($value, $post);
			case 'flood-match':
				$this->validateType($value, 'array', 'flood-match condition value');

				return $this->matchFloodCondition($value, $post, $floodEntries);
			case 'flood-time':
				$this->validateType($value, 'numeric', 'flood-time condition value');

				return $this->checkFloodTime((int)$value, $floodEntries);
			case 'flood-count':
				$this->validateType($value, 'numeric', 'flood-count condition value');
				$timeLimit = $allConditions['flood-time'] ?? 0;
				$this->validateType($timeLimit, 'numeric', 'flood-time condition value');

				return $this->checkFloodCount((int)$value, $floodEntries, (int)$timeLimit);
			case 'name':
			case 'trip':
			case 'email':
			case 'subject':
			case 'body':
			case 'filehash':
				$this->validateType($value, 'string', "'{$conditionLower}' condition pattern");
				if (!isset($post[$condition]) && $condition !== 'filehash') {
					return false;
				}

				return $this->checkRegex($value, $post[$condition] ?? '', $condition);
			case 'body_reg':
				$this->validateType($value, 'array', 'body_reg condition patterns');

				return $this->matchBodyReg($value, $post);
			case 'filename':
			case 'extension':
				$this->validateType($value, 'string', "'{$conditionLower}' condition pattern");

				return $this->matchFileCondition($value, $condition, $post);
			case 'ip':
				$this->validateType($value, 'string', 'IP condition pattern');

				if (!isset($post['ip'])) {
					$this->logger->log(LogDriver::WARNING, "Post missing 'ip' field for IP condition");
					return false;
				}

				return $this->checkRegex($value, $post['ip'], 'IP');
			case 'rdns':
				$this->validateType($value, 'string', 'RDNS condition pattern');

				if (!isset($post['ip'])) {
					$this->logger->log(LogDriver::WARNING, "Post missing 'ip' field for RDNS condition");
					return false;
				}

				$hostname = \rDNS($post['ip']);
				if ($hostname === $post['ip']) {
					$this->logger->log(LogDriver::WARNING, "RDNS lookup failed for IP: {$post['ip']}");
					return false;
				}

				return $this->checkRegex($value, $hostname, 'RDNS');
			case 'agent':
				$this->validateType($value, 'array', 'Agent condition list');
				return $this->matchAgentCondition($value);
			case 'op':
			case 'has_file':
			case 'board':
			case 'password':
				if (!isset($post[$condition])) {
					$this->logger->log(LogDriver::WARNING, "Post missing field '{$conditionLower}' required for condition.");
					return false;
				}

				return $post[$condition] === $value;
			default:
				$this->logger->log(LogDriver::WARNING, "Encountered unknown filter condition type: '{$condition}'.");

				return false;
		}
	}

	/**
	 * Checks if the current User-Agent is present in a given list of strings.
	 *
	 * @param array<int, mixed> $value The list of user-agent strings from the filter config.
	 * @return bool True if the current User-Agent is found (strictly) in the list, false otherwise.
	 */
	private function matchAgentCondition(array $value): bool {
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
		if ($userAgent === null) {
			$this->logger->log(
				LogDriver::WARNING,
				"HTTP_USER_AGENT server variable not available for 'agent' condition."
			);
		}

		$validAgents = \array_filter($value, 'is_string');

		if (\count($validAgents) !== \count($value)) {
			$this->logger->log(
				LogDriver::WARNING,
				"Non-string value found in 'agent' condition list. Only string values will be checked."
			);
		}

		return \in_array($userAgent, $validAgents, true);
	}
	/**
	 * Executes a custom filter callback provided in the filter configuration.
	 *
	 * @param callable $callback The user-defined filter function (must accept post array, return bool).
	 * @param array<string, mixed> $post The post data array.
	 * @return bool The boolean result returned by the callback.
	 * @throws \RuntimeException Wraps any exception thrown by the callback for context.
	 * @throws \Throwable Original exception is wrapped in RuntimeException
	 */
	private function matchCustom(callable $callback, array $post): bool {
		try {
			$result = $callback($post);
			if (!\is_bool($result)) {
				throw new \UnexpectedValueException("Custom filter callback did not return a boolean value.");
			}
			return $result;
		} catch (\Throwable $e) {
			$functionName = 'anonymous function';

			if (\is_string($callback)) {
				$functionName = $callback;
			} elseif (\is_array($callback) && \count($callback) == 2) {
				$class = \is_object($callback[0]) ? \get_class($callback[0]) : (string)$callback[0];
				$method = (string)$callback[1];
				$separator = \is_object($callback[0]) ? '->' : '::';
				$functionName = $class . $separator . $method;
			}

			throw new \RuntimeException(
				"Error executing custom filter function '{$functionName}': " . $e->getMessage(),
				0,
				$e
			);
		}
	}

	/**
	 * Checks if the post body matches any of the provided regex patterns.
	 *
	 * @param array<int, string> $patterns An array of regex patterns.
	 * @param array<string, mixed> $post The post data array, expects 'body_nomarkup'.
	 * @return bool True if any pattern matches the 'body_nomarkup', false otherwise.
	 * @throws \InvalidArgumentException If any pattern is not a string or is an invalid regex.
	 */
	private function matchBodyReg(array $patterns, array $post): bool {
		if (!isset($post['body_nomarkup'])) {
			$this->logger->log(LogDriver::WARNING, "Post missing 'body_nomarkup' for 'body_reg' condition.");
			return false;
		}

		foreach ($patterns as $index => $pattern) {
			$this->validateType($pattern, 'string', "body_reg pattern #{$index}");

			if ($this->checkRegex($pattern, $post['body_nomarkup'], "body_reg pattern #{$index}")) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if any recent post matches a set of flood conditions relative to the current post.
	 *
	 * @param array<int, mixed> $match An array of conditions to check against each flood entry.
	 * @param array<string, mixed> $post The current post data.
	 * @param array<int, array<string, mixed>> $floodEntries Recent post history.
	 * @return bool True if any flood entry satisfies all specified $match conditions.
	 */
	private function matchFloodCondition(array $match, array $post, array $floodEntries): bool {
		if (empty($match)) {
			$this->logger->log(LogDriver::WARNING, "Empty condition list provided for 'flood-match'.");
			return false;
		}

		foreach ($floodEntries as $floodPost) {
			if ($this->matchesAllConditions($match, $floodPost, $post)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if a flood post matches all given conditions.
	 *
	 * @param array<int, mixed> $match Conditions to verify.
	 * @param array<string, mixed> $floodPost Previous post to compare against.
	 * @param array<string, mixed> $post Current post data.
	 * @return bool True if all conditions match.
	 * @throws \RuntimeException When condition check fails.
	 */
	private function matchesAllConditions(array $match, array $floodPost, array $post): bool {
		foreach ($match as $condition) {
			try {
				if (!$this->floodService->checkFloodCondition($condition, $floodPost, $post)) {
					return false;
				}
			} catch (\Throwable $e) {
				$conditionStr = \is_scalar($condition) ? (string)$condition : \json_encode($condition);
				throw new \RuntimeException("Error checking flood-match sub-condition '{$conditionStr}': " . $e->getMessage(), 0, $e);
			}
		}

		return true;
	}

	/**
	 * Checks if any recent post was made within the specified time window.
	 *
	 * @param int $time The time window in seconds.
	 * @param array<int, array<string, mixed>> $floodEntries Recent post history, expecting 'time' key.
	 * @return bool True if any flood entry's timestamp is within the window, false otherwise.
	 */
	private function checkFloodTime(int $time, array $floodEntries): bool {
		if ($time < 0) {
			$this->logger->log(LogDriver::WARNING, "Negative time value '{$time}' provided for 'flood-time'.");
			return false;
		}

		foreach ($floodEntries as $floodPost) {
			if (!isset($floodPost['time'])) {
				$this->logger->log(LogDriver::WARNING, "Flood entry missing 'time' field");
				continue;
			}

			if (\time() - $floodPost['time'] <= $time) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if the count of recent posts within a time limit meets or exceeds a treshold.
	 *
	 * @param int $threshold The minimum number of posts required.
	 * @param array<int, array<string, mixed>> $floodEntries Recent post history, expecting 'time' key.
	 * @param int $timeLimit The time window (in seconds) to count posts within.
	 * @return bool True if the count meets or exceeds the treshold, false otherwise.
	 */
	private function checkFloodCount(int $threshold, array $floodEntries, int $timeLimit): bool {
		$count = \count(\array_filter(
			$floodEntries,
			function ($floodPost) use ($timeLimit) {
				return isset($floodPost['time']) && \time() - $floodPost['time'] <= $timeLimit;
			}
		));

		return $count >= $threshold;
	}

	/**
	 * Checks if any file attached to the post matches a regex pattern on a specific field.
	 *
	 * @param string $value The regex pattern.
	 * @param string $condition The file field to check ('filename', 'extension', 'hash').
	 * @param array<string, mixed> $post The post data array, expects 'files' array.
	 * @return bool True if any file matches the condition, false otherwise.
	 * @throws \InvalidArgumentException If the pattern is an invalid regex.
	 */
	private function matchFileCondition(string $value, string $condition, array $post): bool {
		if (empty($post['files']) || !\is_array($post['files'])) {
			return false;
		}

		foreach ($post['files'] as $file) {
			if (!\is_array($file)) {
				$this->logger->log(LogDriver::WARNING, "File entry is not an array");
				continue;
			}

			if (!isset($file[$condition])) {
				$this->logger->log(LogDriver::WARNING, "File missing '{$condition}' field");
				continue;
			}

			if ($this->checkRegex($value, $file[$condition], "file '{$condition}'")) {
				return true;
			}
		}

		return false;
	}
}
