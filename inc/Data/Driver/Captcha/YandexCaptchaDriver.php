<?php
namespace Vichan\Data\Driver\Captcha;

use Vichan\Data\Driver\HttpDriver;

defined('TINYBOARD') or exit;


class YandexCaptchaDriver implements RemoteCaptchaDriver {
	private HttpDriver $http;
	private string $secret;

	/**
	 * Creates a new YandexCaptchaDriver using the Yandex SmartCaptcha service.
	 *
	 * @param HttpDriver $http The http client.
	 * @param string $secret Server side secret.
	 * @return YandexCaptchaDriver A new YandexCaptchaDriver query instance.
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