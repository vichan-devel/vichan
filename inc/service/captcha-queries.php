<?php // Verify captchas server side.
namespace Vichan\Service;

use Vichan\Driver\HttpDriver;

defined('TINYBOARD') or exit;


class RemoteCaptchaQuery {
	private HttpDriver $http;
	private string $secret;
	private string $endpoint;


	/**
	 * Creates a new CaptchaRemoteQueries instance using the google recaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return CaptchaRemoteQueries A new captcha query instance.
	 */
	public static function withRecaptcha(HttpDriver $http, string $secret): RemoteCaptchaQuery {
		return new self($http, $secret, 'https://www.google.com/recaptcha/api/siteverify');
	}

	/**
	 * Creates a new CaptchaRemoteQueries instance using the hcaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return CaptchaRemoteQueries A new captcha query instance.
	 */
	public static function withHCaptcha(HttpDriver $http, string $secret): RemoteCaptchaQuery {
		return new self($http, $secret, 'https://hcaptcha.com/siteverify');
	}

	private function __construct(HttpDriver $http, string $secret, string $endpoint) {
		$this->http = $http;
		$this->secret = $secret;
		$this->endpoint = $endpoint;
	}

	/**
	 * Checks if the user at the remote ip passed the captcha.
	 *
	 * @param string $response User provided response.
	 * @param string $remote_ip User ip.
	 * @return bool Returns true if the user passed the captcha.
	 * @throws RuntimeException|JsonException Throws on IO errors or if it fails to decode the answer.
	 */
	public function verify(string $response, string $remote_ip): bool {
		$data = array(
			'secret' => $this->secret,
			'response' => $response,
			'remoteip' => $remote_ip
		);

		$ret = $this->http->requestGet($this->endpoint, $data);
		$resp = json_decode($ret, true, 16, JSON_THROW_ON_ERROR);

		return isset($resp['success']) && $resp['success'];
	}
}

class NativeCaptchaQuery {
	private HttpDriver $http;
	private string $domain;
	private string $provider_check;


	/**
	 * @param HttpDriver $http The http client.
	 * @param string $domain The server's domain.
	 * @param string $provider_check Path to the endpoint.
	 */
	function __construct(HttpDriver $http, string $domain, string $provider_check) {
		$this->http = $http;
		$this->domain = $domain;
		$this->provider_check = $provider_check;
	}

	/**
	 * Checks if the user at the remote ip passed the native vichan captcha.
	 *
	 * @param string $extra Extra http parameters.
	 * @param string $user_text Remote user's text input.
	 * @param string $user_cookie Remote user cookie.
	 * @return bool Returns true if the user passed the check.
	 * @throws RuntimeException Throws on IO errors.
	 */
	public function verify(string $extra, string $user_text, string $user_cookie): bool {
		$data = array(
			'mode' => 'check',
			'text' => $user_text,
			'extra' => $extra,
			'cookie' => $user_cookie
		);

		$ret = $this->http->requestGet($this->domain . '/' . $this->provider_check, $data);
		return $ret === '1';
	}
}
