<?php
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
		$resp = \json_decode($ret, true, 16, \JSON_THROW_ON_ERROR);

		return isset($resp['success']) && $resp['success'];
	}
}
