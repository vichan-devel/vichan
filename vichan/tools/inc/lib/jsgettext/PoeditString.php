<?php

class PoeditString {
	public $key;
	public $value;
	public $fuzzy;
	public $comments;

	function __construct($key, $value = '', $fuzzy = false, $comments = array()) {
		$this->key = $key;
		$this->value = $value;
		$this->fuzzy = $fuzzy;
		$this->comments = (array)$comments;
	}

	public function __toString() {
		$str ='';
		foreach ($this->comments as $c) {
			$str .= "#: $c\n";
		}
		if ($this->fuzzy) $str .= "#, fuzzy\n";
		$str .= 'msgid "'.str_replace('"', '\\"', $this->key).'"' . "\n";
		$str .= 'msgstr "'.str_replace('"', '\\"', $this->value).'"' . "\n";
		$str .= "\n";
		return $str;
	}
}

?>