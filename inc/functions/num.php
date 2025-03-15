<?php
namespace Vichan\Functions\Num;

// Highest common factor
function hcf($a, $b){
	$gcd = 1;

	if ($a > $b) {
		$a = $a+$b;
		$b = $a-$b;
		$a = $a-$b;
	}
	if ($b == (round($b / $a)) * $a) {
		$gcd = $a;
	} else {
		for ($i = round($a / 2); $i; $i--) {
			if ($a == round($a / $i) * $i && $b == round($b / $i) * $i) {
				$gcd = $i;
				$i = false;
			}
		}
	}

	return $gcd;
}

function fraction($numerator, $denominator, $sep) {
	$gcf = hcf($numerator, $denominator);
	$numerator = $numerator / $gcf;
	$denominator = $denominator / $gcf;

	return "{$numerator}{$sep}{$denominator}";
}
