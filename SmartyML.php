<?php

use SPFW\system\config\SmartyMLConfig;


/**
 * Smarty Multilingual 2.0
 *
 * @author Jonathan Stoll
 * @version 12
 */
class SmartyML extends Smarty
{
	private const DEFAULT_LOCALES_DIR = '../locales/';


	/** @var string[] Allowed languages */
	private array $languages;

	/** @var array<non-empty-array<string,mixed>> Key = var-name, value = array of [mixed value, bool nocache] */
	private array $cached_variables = [];

	/** @var string Default language */
	private string $default_language;

	/** @var string Current language */
	private string $language;

	/** @var string Directory where locale files are stored */
	private string $locales_dir;

	/** @var array<string,string> Key = match, value = replacement */
	private array $translations = [];


	/**
	 * Creates a new smarty object and sets a language.
	 * If argument $lang is not defined or equals null, system default language will be used.
	 *
	 * @param string[] $allowed_languages List of allowed languages
	 * @param string $default_language Default language, which must be element of allowed languages
	 * @param null|string $language Current language or null for default language
	 * @param null|string $locales_dir Optional: Directory where locale files are stored
	 *
	 * @throws SmartyException Language is invalid or filter couldn't been set up
	 */
	public function __construct(array $allowed_languages, string $default_language, string $language = null, string $locales_dir = null)
	{
		$valid_default_language = in_array($default_language, $allowed_languages, true);
		if (!$valid_default_language) {
			throw new SmartyException('Invalid default language "' . $default_language . '"');
		}

		$valid_language = $language === null || in_array($language, $allowed_languages, true);
		if (!$valid_language) {
			throw new SmartyException('Invalid language "' . $language . '"');
		}

		parent::__construct();

		$this->languages = $allowed_languages;
		$this->default_language = $default_language;
		$this->language = $language ?? $default_language;
		$this->locales_dir = $locales_dir ?? self::DEFAULT_LOCALES_DIR;

		// registers prefilter for language decode automatically
		$this->registerFilter('pre', [$this, 'lang_decode']);
	}

	/**
	 * @param string $tpl_var
	 * @param mixed $value
	 * @param bool $nocache
	 *
	 * @return $this
	 *
	 * @noinspection MissingParameterTypeDeclarationInspection
	 */
	final public function assignML(string $tpl_var, $value = null, bool $nocache = false) : self
	{
		$this->cached_variables[$tpl_var] = ['value' => $value, 'nocache' => $nocache];
		return $this;
	}

	/**
	 * Returns multilingual cache or compile id, which includes the short language name as prefix.
	 *
	 * @param null|mixed $id Original cache or compile id
	 *
	 * @return string Modified id
	 *
	 * @noinspection MissingParameterTypeDeclarationInspection
	 */
	private function mlCacheCompileId($id = null) : string
	{
		return $this->language . '-' . $id;
	}

	/** @noinspection MethodShouldBeFinalInspection */
	public function display($template = null, $cache_id = null, $compile_id = null, $parent = null) : void
	{
		// First: Load language
		$this->lang_load();

		// Then: Parse ML-variables
		foreach ($this->cached_variables as $variable_name => $variable_content) {
			$value = $variable_content['value'];
			if (is_string($value)) {
				$value = $this->lang_decode($value, null);
			}

			$this->assign($variable_name, $value, $variable_content['nocache']);
		}

		// We need to set the cache id and compile id so a new script will be
		// compiled for each language. This makes things really fast ;-)
		$cache_id = $this->mlCacheCompileId($cache_id ?? $this->cache_id);
		$compile_id = $this->mlCacheCompileId($compile_id ?? $this->compile_id);

		// Finally: Parse and display template
		parent::display($template, $cache_id, $compile_id, $parent);
	}

	/** @noinspection MethodShouldBeFinalInspection */
	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null) : string
	{
		// We need to set the cache id and compile id so a new script will be
		// compiled for each language. This makes things really fast ;-)
		$cache_id = $this->mlCacheCompileId($cache_id ?? $this->cache_id);
		$compile_id = $this->mlCacheCompileId($compile_id ?? $this->compile_id);

		// Call parent method with modified cache and compile ids
		return parent::fetch($template, $cache_id, $compile_id, $parent);
	}

	/**
	 * Returns current language.
	 *
	 * @return string Short name of language
	 *
	 * @noinspection MethodShouldBeFinalInspection
	 */
	public function getLanguage() : string
	{
		return $this->language;
	}

	/**
	 * Return allowed languages.
	 *
	 * @return string[] List of language short names (like "en" for English, "es" for Spanish, ...)
	 */
	final public function getLanguages() : array
	{
		return $this->languages;
	}

	final public static function initBySmartyMlConfig(SmartyMLConfig $smarty_config, string $language = null) : self
	{
		$smarty_object = new self(
				$smarty_config->getLangEnable(),
				$smarty_config->getLangDefault(),
				$language,
				$smarty_config->getLangDir(),
		);

		$smarty_object->setTemplateDir($smarty_config->getTemplateDir());
		$smarty_object->setCompileDir($smarty_config->getCompileDir());
		$smarty_object->setCacheDir($smarty_config->getCacheDir());

		$smarty_object->caching = $smarty_config->isCaching() ? Smarty::CACHING_LIFETIME_CURRENT : Smarty::CACHING_OFF;
		$smarty_object->cache_lifetime = $smarty_config->getCacheLifetime();

		$debug_mode = $smarty_config->isDebugMode();
		$smarty_object->debugging = $debug_mode;
		$smarty_object->compile_check = $debug_mode ? Smarty::COMPILECHECK_ON : Smarty::COMPILECHECK_OFF;
		$smarty_object->force_compile = $debug_mode;

		return $smarty_object;
	}

	/**
	 * Test to see if valid cache exists for this template
	 *
	 * @param null|Smarty_Internal_Template|string $template name of template file
	 * @param null|string $cache_id
	 * @param null|string $compile_id
	 * @param null|object $parent
	 *
	 * @return bool results of {@link _read_cache_file()}
	 * @throws SmartyException
	 *
	 * @noinspection MethodShouldBeFinalInspection
	 */
	public function isCached($template = null, $cache_id = null, $compile_id = null, $parent = null) : bool
	{
		if (!$this->caching) {
			return false;
		}

		$cache_id = $this->mlCacheCompileId($cache_id ?? $this->cache_id);
		$compile_id = $this->mlCacheCompileId($compile_id ?? $this->compile_id);

		return parent::isCached($template, $cache_id, $compile_id, $parent);
	}

	/**
	 * Checks a line of the language file, if it is eligible for language translation.
	 * Empty, commented and lines without an equal sign are rejected.
	 *
	 * @param string $row
	 *
	 * @return bool
	 */
	private static function isLanguageLine(string $row) : bool
	{
		return mb_strlen($row) > 2 && strncmp($row, '//', 2) !== 0 && mb_strpos($row, '=') !== false;
	}

	/** @noinspection PhpUnusedParameterInspection */
	final public function lang_decode(string $tpl_source, ?Smarty_Internal_Template $template) : string
	{
		return preg_replace_callback('/##(.+?)##/', function(array $text) : string {
			return $this->translations[$text[1]] ?? '';
		}, $tpl_source);
	}

	/**
	 * Loads a language file and builds a translation table.
	 *
	 * @throws SmartyException Language file is not found or cannot be opened/read
	 */
	private function lang_load() : void
	{
		$lang_file = $this->locales_dir . $this->language . '.txt';
		if (file_exists($lang_file)) {
			$file = file($lang_file);
			if ($file === false) {
				throw new SmartyException('Could not read language file "' . $lang_file . '"');
			}

			$translations = [];
			foreach ($file as $row) {
				// ignore empty rows and comments
				if (self::isLanguageLine($row)) {
					[$match, $replacement] = explode('=', $row, 2);

					// ignore last character, if it is a line break
					if (substr($replacement, -1) === "\n") {
						$replacement = substr($replacement, 0, -1);
					}

					$translations[$match] = $replacement;
				}
			}
			$this->translations = $translations;
		} else {
			throw new SmartyException('Language file was not found at "' . $lang_file . '"');
		}
	}

	/**
	 * Changes the language or resets value to default.
	 *
	 * @param null|string $lang The language. If null, the default language will be used
	 *
	 * @return void
	 * @throws SmartyException Invalid language
	 */
	final public function setLanguage(string $lang = null) : void
	{
		$lang = $lang ?? $this->default_language;

		// Verifying language
		if (!in_array($lang, $this->languages, true)) {
			throw new SmartyException('Given language "' . $lang . '" is not allowed!');
		}

		$this->language = $lang;
		$this->assign('lang', $lang);
	}
}


?>