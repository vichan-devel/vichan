<?php
namespace Vichan\Service;

defined('TINYBOARD') or exit;


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
