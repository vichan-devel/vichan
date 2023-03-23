<?php

	require_once 'JSParser.php';
	require_once 'PoeditParser.php';

	function buildOptions($args) {
		$options = array(
			'files' => array(),
			'-o'	=> null,
			'-k'	=> '_'
		);
		$len = count($args);
		$i = 1;
		while ($i < $len) {
			if (preg_match('#^-[a-z]$#i', $args[$i])) {
				$options[$args[$i]] = isset($args[$i+1]) ? trim($args[$i+1]) : true;
				$i += 2;
			}
			else {
				$options['files'][] = $args[$i];
				$i++;
			}
		}
		return $options;
	}

	$options = buildOptions($argv);

	if (!file_exists($options['-o'])) {
		touch($options['-o']);
	}

	if (!is_writable($options['-o'])) {
		die("Invalid output file name. Make sure it exists and is writable.");
	}

	$inputFiles = $options['files'];

	if (empty($inputFiles)) {
		die("You did not provide any input file.");
	}

	$poeditParser = new PoeditParser($options['-o']);
	$poeditParser->parse();

	$errors = array();

	foreach ($inputFiles as $f) {
		if (!is_readable($f) || !preg_match('#\.js$#', $f)) {
			$errors[] = ("$f is not a valid javascript file.");
			continue;
		}
		$jsparser = new JSParser($f, explode(' ', $options['-k']));
		$jsStrings = $jsparser->parse();
		$poeditParser->merge($jsStrings);
	}

	if (!empty($errors)) {
		echo "\nThe following errors occured:\n" . implode("\n", $errors) . "\n";
	}

	$poeditParser->save();
?>
