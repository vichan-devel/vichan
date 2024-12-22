<?php
namespace Vichan\Functions\Dice;

function _get_or_default_int(array $arr, int $index, int $default) {
	return (isset($arr[$index]) && is_numeric($arr[$index])) ? (int)$arr[$index] : $default;
}


/* Die rolling:
 * If "dice XdY+/-Z" is in the email field (where X or +/-Z may be
 * missing), X Y-sided dice are rolled and summed, with the modifier Z
 * added on.  The result is displayed at the top of the post.
 */
function email_dice_roll($post) {
	global $config;
	if(strpos(strtolower($post->email), 'dice%20') === 0) {
		$dicestr = str_split(substr($post->email, strlen('dice%20')));

		// Get params
		$diceX = '';
		$diceY = '';
		$diceZ = '';

		$curd = 'diceX';
		for($i = 0; $i < count($dicestr); $i ++) {
			if(is_numeric($dicestr[$i])) {
				$$curd .= $dicestr[$i];
			} else if($dicestr[$i] == 'd') {
				$curd = 'diceY';
			} else if($dicestr[$i] == '-' || $dicestr[$i] == '+') {
				$curd = 'diceZ';
				$$curd = $dicestr[$i];
			}
		}

		// Default values for X and Z
		if($diceX == '') {
			$diceX = '1';
		}

		if($diceZ == '') {
			$diceZ = '+0';
		}

		// Intify them
		$diceX = intval($diceX);
		$diceY = intval($diceY);
		$diceZ = intval($diceZ);

		// Continue only if we have valid values
		if($diceX > 0 && $diceY > 0) {
			$dicerolls = array();
			$dicesum = $diceZ;
			for($i = 0; $i < $diceX; $i++) {
				$roll = rand(1, $diceY);
				$dicerolls[] = $roll;
				$dicesum += $roll;
			}

			// Prepend the result to the post body
			$modifier = ($diceZ != 0) ? ((($diceZ < 0) ? ' - ' : ' + ') . abs($diceZ)) : '';
			$dicesum = ($diceX > 1) ? ' = ' . $dicesum : '';
			$post->body = '<table class="diceroll"><tr><td><img src="'.$config['dir']['static'].'d10.svg" alt="Dice roll" width="24"></td><td>Rolled ' . implode(', ', $dicerolls) . $modifier . $dicesum . '</td></tr></table><br/>' . $post->body;
		}
	}
}

/**
 * Rolls a dice and generates the appropriate html from the markup.
 * @param array $matches The array of the matches according to the default configuration.
 *                       1 -> The number of dices to roll.
 *                       3 -> The number faces of the dices.
 *                       4 -> The offset to apply to the dice.
 * @param string $img_path Path to the image to use relative to the root. Null if none.
 * @return string The html to replace the original markup with.
 */
function inline_dice_roll_markup(array $matches, ?string $img_path): string {
	global $config;

	$dice_count = _get_or_default_int($matches, 1, 1);
	$dice_faces = _get_or_default_int($matches, 3, 6);
	$dice_offset = _get_or_default_int($matches, 4, 0);

	// Clamp between 1 and max_roll_count.
	$dice_count = max(min($dice_count, $config['max_roll_count']), 1);
	// Must be at least 2.
	if ($dice_faces < 2) {
		$dice_faces = 6;
	}

	$tot = 0;
	for ($i = 0; $i < $dice_count; $i++) {
		$tot += mt_rand(1, $dice_faces);
	}
	// Ensure that final result is at least an integer.
	$tot = abs((int)($dice_offset + $tot));


	if ($img_path !== null) {
		$img_text = "<img src='{$config['root']}{$img_path}' alt='dice' title='dice' class=\"inline-dice\"/>";
	} else {
		$img_text = '';
	}

	if ($dice_offset === 0) {
		$dice_offset_text = '';
	} elseif ($dice_offset > 0) {
		$dice_offset_text = "+{$dice_offset}";
	} else {
		$dice_offset_text = (string)$dice_offset;
	}

	return "<span>$img_text {$dice_count}d{$dice_faces}{$dice_offset_text} = <b>$tot</b></span>";
}
