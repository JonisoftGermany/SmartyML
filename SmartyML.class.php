<?php

##################################################
##########	Projekt:	SmartyML		##########
##########	Autor:		Jonathan Stoll	##########
##########	Build:		1				##########
##################################################

// needs global $smarty_lang_enable, $smarty_lang_default, $smarty_lang_dir = '';

class SmartyML extends Smarty{

private static $languages = array('en');
private static $language = '';
private static $translations = null;


/**
 * Returns the current set language
 * 
 * @access public
 * @static
 * @return string - empty string when no language is set yet
 */
public static function getLanguage()
{
	return self::$language;
}


/**
 * Gives you all allowed languages
 * 
 * @access public
 * @static
 * @return string[] language-names
 */
public static function getValidLanguages()
{
	return self::$languages;
}


public static function lang_compile($text)
{
	if (isset(self::$translations[$text[1]]))
	{
		return self::$translations[$text[1]];
	}
	else
	{
		return '';
	}
}


/**
 * A prefilter for smarty which replaces language-tags by phrases from the translation-array
 * 
 * @access public
 * @static
 * @param mixed $text
 * @return string The translated word or phrase - an empty string if there is no
 * 	translation for the given language-tag
 */
public static function lang_decode($tpl_source, &$smarty)
{
	return preg_replace_callback('/##(.+?)##/', 'SmartyML::lang_compile', $tpl_source);
}


private static function lang_load()
{
	global $smarty_lang_dir;
	$lang_file = $smarty_lang_dir.self::$language.'.txt';
	if (file_exists($lang_file))
	{
		$file = file($lang_file);
		$translations = array();
		foreach ($file as $row)
		{
			// ignore empty rows and comments
			if (strlen($row) > 3 || substr($row, 0, 2) != '//')
			{
				$keyval = explode('=', $row, 2);

				// ignore last character, if it is a line break
				if (substr($keyval[1], -1) == "\n")
				{
					$keyval[1] = substr($keyval[1], 0, -1);
				}

				$translations[$keyval[0]] = $keyval[1];
			}
		}
		self::$translations = $translations;
	}
	else
	{
		throw new RuntimeException('Languagefile was not found');
	}
}


/**
 * Changes the language .
 * 
 * @access public
 * @static
 * @param mixed $lang (default: null)
 * @return void
 */
public static function setLanguage($lang = null)
{
	if (is_null($lang))
	{
		global $smarty_lang_default;
		$lang = $smarty_lang_default;
	}
	else
	{
		// verifying Language
		if (!in_array($lang, self::$languages))
		{
			throw new RuntimeException('Given language is not valid!');
		}
		// NOTE: here could be a check, if language_file is found
		// if not: here could be a warning
	}

	self::$language = $lang;
}


public function __construct($lang = null)
{
	// calls mother-constructer
	$this->Smarty();
	// alternative: parent();

	// set up for (default-)language
	global $smarty_lang_enable;
	if (!is_array($smarty_lang_enable))
	{
		$smarty_lang_enable = array($smarty_lang_enable);
	}
	self::$languages = $smarty_lang_enable;
	self::setLanguage($lang);

	// registers prefilter for language decode automatically
	$this->register_prefilter('SmartyML::lang_decode');
}


public function display($resource_name, $cache_id = null, $compile_id = null)
{
	// loads language-file
	self::lang_load();

	parent::display($resource_name, $cache_id, $compile_id);
}


public function fetch($_smarty_tpl_file, $_smarty_cache_id = null, $_smarty_compile_id = null, $_smarty_display = false)
{
	// We need to set the cache id and the compile id so a new script will be
	// compiled for each language. This makes things really fast ;-)
	$_smarty_compile_id = self::$language.'-'.$_smarty_compile_id;
	$_smarty_cache_id = $_smarty_compile_id;

	// Now call parent method
	return parent::fetch($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $_smarty_display );
}


/**
 * test to see if valid cache exists for this template
 *
 * @param string $tpl_file name of template file
 * @param string $cache_id
 * @param string $compile_id
 * @return string|false results of {@link _read_cache_file()}
 */
public function is_cached($tpl_file, $cache_id = null, $compile_id = null)
{
	if (!$this->caching)
		return false;

	if (!isset($compile_id))
	{
		$compile_id = self::$language.'-'.$this->compile_id;
		$cache_id = $compile_id;
	}

	return parent::is_cached($tpl_file, $cache_id, $compile_id);
}

}



?>