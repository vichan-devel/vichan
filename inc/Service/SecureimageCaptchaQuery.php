<?php
namespace Vichan\Service;

use Vichan\Data\Driver\HttpDriver;

defined('TINYBOARD') or exit;


class SecureImageCaptchaQuery {
	private HttpDriver $http;
	private string $domain;
	private string $provider_check;
	private string $extra;

	/**
	 * @param HttpDriver $http The http client.
	 * @param string $domain The server's domain.
	 * @param string $provider_check Path to the endpoint.
	 * @param string $extra Extra http parameters.
	 */
	function __construct(HttpDriver $http, string $domain, string $provider_check, string $extra) {
		$this->http = $http;
		$this->domain = $domain;
		$this->provider_check = $provider_check;
		$this->extra = $extra;
	}

	/**
	 * Checks if the user at the remote ip passed the native vichan captcha.
	 *
	 * @param string $user_text Remote user's text input.
	 * @param string $user_cookie Remote user cookie.
	 * @return bool Returns true if the user passed the check.
	 * @throws RuntimeException Throws on IO errors.
	 */
	public function verify(string $user_text, string $user_cookie): bool {
		$data = [
			'mode' => 'check',
			'text' => $user_text,
			'extra' => $this->extra,
			'cookie' => $user_cookie
		];

		$ret = $this->http->requestGet($this->domain . '/' . $this->provider_check, $data);
		return $ret === '1';
	}
}
