<?php
namespace Vichan\Functions\Dice;

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
