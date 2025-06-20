<?php
namespace Vichan\Functions\Hide;

function secure_hash(string $data, bool $binary): string {
	return \hash('tiger160,3', $data, $binary);
}
