<?php

namespace Vichan\Twig\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Tinyboard extends AbstractExtension
{
	/**
	* Returns a list of filters to add to the existing list.
	*
	* @return array An array of filters
	*/
	public function getFilters()
	{
		return array(
			new TwigFilter('filesize', 'format_bytes'),
			new TwigFilter('truncate', 'twig_truncate_filter'),
			new TwigFilter('truncate_body', 'truncate'),
			new TwigFilter('truncate_filename', 'twig_filename_truncate_filter'),
			new TwigFilter('extension', 'twig_extension_filter'),
			new TwigFilter('sprintf', 'sprintf'),
			new TwigFilter('capcode', 'capcode'),
			new TwigFilter('remove_modifiers', 'remove_modifiers'),
			new TwigFilter('hasPermission', 'twig_hasPermission_filter'),
			new TwigFilter('date', 'twig_date_filter'),
			new TwigFilter('poster_id', 'poster_id'),
			new TwigFilter('count', 'count'),
			new TwigFilter('ago', 'Vichan\Functions\Format\ago'),
			new TwigFilter('until', 'Vichan\Functions\Format\until'),
			new TwigFilter('push', 'twig_push_filter'),
			new TwigFilter('bidi_cleanup', 'bidi_cleanup'),
			new TwigFilter('addslashes', 'addslashes'),
			new TwigFilter('cloak_ip', 'cloak_ip'),
			new TwigFilter('cloak_mask', 'cloak_mask'),
		);
	}

	/**
	* Returns a list of functions to add to the existing list.
	*
	* @return array An array of filters
	*/
	public function getFunctions()
	{
		return array(
			new TwigFunction('time', 'time'),
			new TwigFunction('floor', 'floor'),
			new TwigFunction('hiddenInputs', 'hiddenInputs'),
			new TwigFunction('hiddenInputsHash', 'hiddenInputsHash'),
			new TwigFunction('ratio', 'twig_ratio_function'),
			new TwigFunction('secure_link_confirm', 'twig_secure_link_confirm'),
			new TwigFunction('secure_link', 'twig_secure_link'),
			new TwigFunction('link_for', 'link_for')
		);
	}

	/**
	* Returns the name of the extension.
	*
	* @return string The extension name
	*/
	public function getName()
	{
		return 'tinyboard';
	}
}