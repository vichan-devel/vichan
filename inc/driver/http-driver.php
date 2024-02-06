<?php // Honestly this is just a wrapper for cURL. Still useful to mock it and have an OOP API on PHP 7.

class HttpDrivers {
	const DEFAULT_USER_AGENT = 'Tinyboard';
	const DEFAULT_TIMEOUT = 3;

	public static function get_http_driver() {
		return new HttpDriver(self::DEFAULT_TIMEOUT, self::DEFAULT_USER_AGENT);
	}
}

class HttpDriver {
	private $inner;
	private $timeout;
	private $user_agent;


	private function reset() {
		curl_reset($this->inner);
		curl_setopt_array($this->inner, array(
			CURLOPT_USERAGENT => $this->user_agent,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		));
	}

	function __construct($timeout, $user_agent) {
		$this->inner = curl_init();
		$this->timeout = $timeout;
		$this->user_agent = $user_agent;
	}

	function __destruct() {
		curl_close($this->inner);
	}

	/**
	 * Execute a GET request.
	 *
	 * @param string $endpoint Uri endpoint.
	 * @param array|null $data Optional GET parameters.
	 * @return string Returns the body of the response.
	 * @throws Exception Throws on error.
	 */
	public function send_get($endpoint, $data) {
		if (is_array($data) && !empty($data)) {
			$endpoint .= '?' . http_build_query($data);
		}

		$this->reset();
		curl_setopt($this->inner, CURLOPT_URL, $endpoint);
		curl_setopt($this->inner, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->inner, CURLOPT_TIMEOUT, $this->timeout);
		$ret = curl_exec($this->inner);

		if ($ret === false) {
			throw new Exception(curl_error($this->inner));
		}
		return $ret;
	}

	/**
	 * Execute a POST request.
	 *
	 * @param string $endpoint Uri endpoint.
	 * @param array|null $data Optional POST parameters.
	 * @return string Returns the body of the response.
	 * @throws Exception Throws on error.
	 */
	public function send_post($endpoint, $data) {
		$this->reset();
		curl_setopt($this->inner, CURLOPT_URL, $endpoint);
		curl_setopt($this->inner, CURLOPT_POST, true);
		if (is_array($data) && !empty($data)) {
			curl_setopt($this->inner, CURLOPT_POSTFIELDS, http_build_query($data));
		}
		curl_setopt($this->inner, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->inner, CURLOPT_TIMEOUT, $this->timeout);
		$ret = curl_exec($this->inner);

		if ($ret === false) {
			throw new Exception(curl_error($this->inner));
		}
		return $ret;
	}

	/**
	 * Download the url's target with curl.
	 *
	 * @param string $url Url to the file to download.
	 * @param File $fd File descriptor to save the content to.
	 * @param int $timeout Optional request timeout in seconds. Use the default timeout if 0.
	 * @return void
	 * @throws Exception Throws on error.
	 */
	public function send_get_into($endpoint, $fd, $timeout = 0) {
		if ($timeout == 0) {
			$timeout = $this->timeout;
		}

		$this->reset();
		curl_setopt($this->inner, CURLOPT_URL, $endpoint);
		curl_setopt($this->inner, CURLOPT_FAILONERROR, true);
		curl_setopt($this->inner, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($this->inner, CURLOPT_FILE, $fd);
		curl_setopt($this->inner, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($this->inner, CURLOPT_TIMEOUT, $timeout);
		$ret = curl_exec($this->inner);

		if ($ret === false) {
			throw new Exception(curl_error($this->inner));
		}
	}
}
