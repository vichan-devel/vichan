<?php
namespace Vichan\Service;

use Vichan\Data\Driver\HttpDriver;

defined('TINYBOARD') or exit;


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
		$resp = \json_decode($ret, true, 16, \JSON_THROW_ON_ERROR);

		return isset($resp['success']) && $resp['success'];
	}
}
