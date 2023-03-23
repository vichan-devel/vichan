<?php

class JSParser {

	protected $content;
	protected $keywords;
	protected $regs = array();
	protected $regsCounter = 0;
	protected $strings = array();
	protected $stringsCounter = 0;

	protected function _extractRegs($match) {
		$this->regs[$this->regsCounter] = $match[1];
		$id = "<<reg{$this->regsCounter}>>";
		$this->regsCounter++;
		return $id;
	}
	protected function _extractStrings($match) {
		$this->strings[$this->stringsCounter] = $this->importRegExps($match[0]);
		$id = "<<s{$this->stringsCounter}>>";
		$this->stringsCounter++;
		return $id;
	}
	protected function importRegExps($input) {
		$regs = $this->regs;
		return preg_replace_callback("#<<reg(\d+)>>#", function ($match) use($regs) {
			return $regs[$match[1]];
		}, $input);
	}

	protected function importStrings($input) {
		$strings = $this->strings;
		return preg_replace_callback("#<<s(\d+)>>#", function ($match) use($strings) {
			return $strings[$match[1]];
		}, $input);
	}

	public function __construct($file, $keywords = '_') {
		$this->content = file_get_contents($file);
		$this->keywords = (array)$keywords;
	}
	
	public function parse() {
		$output = $this->content; //htmlspecialchars($this->content, ENT_NOQUOTES);

		// extract reg exps
		$output = preg_replace_callback(
			'# ( / (?: (?>[^/\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\/ )+ (?<!\\\\)/ ) [a-z]* \b #ix',
			array($this, '_extractRegs'), $output
		);

		// extract strings
		$output = preg_replace_callback(
			array(
				'# " ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)" #ix',
				"# ' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)' #ix"
			), array($this, '_extractStrings'), $output
		);

		// delete line comments
		$output = preg_replace("#(//.*?)$#m", '', $output);

		// delete multiline comments
		$output = preg_replace('#/\*(.*?)\*/#is', '', $output);

		$strings = $this->strings;
		$output = preg_replace_callback("#<<s(\d+)>>#", function($match) use($strings) {
			return $strings[$match[1]];
		}, $output);

		$keywords = implode('|', $this->keywords);

		$strings = array();

		// extract func calls
		preg_match_all(
			'# (?:'.$keywords.') \(\\ *" ( (?: (?>[^"\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\" )* ) (?<!\\\\)"\\ *\) #ix',
			$output, $matches, PREG_SET_ORDER
		);

		foreach ($matches as $m) $strings[] = stripslashes($m[1]);

		$matches = array();
		preg_match_all(
			"# (?:$keywords) \(\\ *' ( (?: (?>[^'\\\\]++) | \\\\\\\\ | (?<!\\\\)\\\\(?!\\\\) | \\\\' )* ) (?<!\\\\)'\\ *\) #ix",
			$output, $matches, PREG_SET_ORDER
		);

		foreach ($matches as $m) $strings[] = stripslashes($m[1]);

		return $strings;
	}
}
?>
