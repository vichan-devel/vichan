<?php // Verify captchas server side.
namespace Vichan\Service;

use Vichan\Data\Driver\HttpDriver;

defined('TINYBOARD') or exit;


class ReCaptchaQuery implements RemoteCaptchaQuery {
	private HttpDriver $http;
	private string $secret;

	/**
	 * Creates a new ReCaptchaQuery using the google recaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return ReCaptchaQuery A new ReCaptchaQuery query instance.
	 */
	public function __construct(HttpDriver $http, string $secret) {
		$this->http = $http;
		$this->secret = $secret;
	}

	public function responseField(): string {
		return 'g-recaptcha-response';
	}

	public function verify(string $response, ?string $remote_ip): bool {
		$data = [
			'secret' => $this->secret,
			'response' => $response
		];

		if ($remote_ip !== null) {
			$data['remoteip'] = $remote_ip;
		}

		$ret = $this->http->requestGet('https://www.google.com/recaptcha/api/siteverify', $data);
		$resp = json_decode($ret, true, 16, JSON_THROW_ON_ERROR);

		return isset($resp['success']) && $resp['success'];
	}
}

class HCaptchaQuery implements RemoteCaptchaQuery {
	private HttpDriver $http;
	private string $secret;
	private string $sitekey;

	/**
	 * Creates a new HCaptchaQuery using the hCaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return HCaptchaQuery A new hCaptcha query instance.
	 */
	public function __construct(HttpDriver $http, string $secret, string $sitekey) {
		$this->http = $http;
		$this->secret = $secret;
		$this->sitekey = $sitekey;
	}

	public function responseField(): string {
		return 'h-captcha-response';
	}

	public function verify(string $response, ?string $remote_ip): bool {
		$data = [
			'secret' => $this->secret,
			'response' => $response,
			'sitekey' => $this->sitekey
		];

		if ($remote_ip !== null) {
			$data['remoteip'] = $remote_ip;
		}

		$ret = $this->http->requestGet('https://hcaptcha.com/siteverify', $data);
		$resp = json_decode($ret, true, 16, JSON_THROW_ON_ERROR);

		return isset($resp['success']) && $resp['success'];
	}
}

class YandexCaptchaQuery implements RemoteCaptchaQuery {
	private HttpDriver $http;
	private string $secret;

	/**
	 * Creates a new YandexCaptchaQuery using the Yandex SmartCaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return ReCaptchaQuery A new ReCaptchaQuery query instance.
	 */
	public function __construct(HttpDriver $http, string $secret) {
		$this->http = $http;
		$this->secret = $secret;
	}

	public function responseField(): string {
		return 'smart-captcha';
	}

	public function verify(string $response, ?string $remote_ip): bool {
		$data = [
			'secret' => $this->secret,
			'token' => $response
		];

		if ($remote_ip !== null) {
			$data['ip'] = $remote_ip;
		}

		$ret = $this->http->requestGet('https://smartcaptcha.yandexcloud.net/validate', $data);
		$resp = json_decode($ret, true, 16, JSON_THROW_ON_ERROR);

		return isset($resp['status']) && $resp['status'] === 'ok';
	}
}

interface RemoteCaptchaQuery {
	/**
	 * Name of the response field in the form data expected by the implementation.
	 *
	 * @return string The name of the field.
	 */
	public function responseField(): string;

	/**
	 * Checks if the user at the remote ip passed the captcha.
	 *
	 * @param string $response User provided response.
	 * @param ?string $remote_ip User ip. Leave to null to only check the response value.
	 * @return bool Returns true if the user passed the captcha.
	 * @throws RuntimeException|JsonException Throws on IO errors or if it fails to decode the answer.
	 */
	public function verify(string $response, ?string $remote_ip): bool;
}

class NativeCaptchaQuery {
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
