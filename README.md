# SmartyML

SmartyML adds multilingual support for the popular PHP-written template engine Smarty.
The current version, **SmartyML 2**, supports Smarty 3 or newer, including Smarty 4.
For Smarty 2.x, please check out older versions of SmartyML, like SmartyML 1.x.

## About
SmartyML extends Smarty by multilingual support.
Placeholders in templates are replaced by translations during template compilation.
Once a template is compiled, there is no further latency impact in comparison without using SmartyML.

Because variables in templates are handled after compilation, SmartyML also offers the replacement of placeholders for assigned strings.
This enables using placeholders in variables, e.g. for alerts or other dynamic values.

## Requirements (SmartyML 2.x)
* Smarty 3 or newer
  * https://github.com/smarty-php/smarty
  * SmartyML officially supports Smarty 4
* PHP 7.4 or newer
  * Official support for PHP 8.0 and 8.1
* mbstring-extension for PHP

## Installation
1. Add SmartyML-class to your repository and make sure, your classloader can find the class.
2. Create a directory, where translation files will be stored. This directory is typically named "locales" and is located at the root directory of the project.
3. Create files for each language you want to support in the locales directory. Nonexisting files for defined languages will cause an exception.

### SPFW
If you use the SPFW framework, SmartyML is already included and ready to use.

## Configuration

### Language files
For each language, a language file has to be created in the locales directory.
The file name has to match the language name combined with the postfix ".txt", e.g. "en.txt" for English.

The language file contains key-value pairs.
The keys are placeholders for later usage in template files.
Each line can contain one definition.
The key and value are separated by an equals symbol, like:

```
question=Möchten Sie fortfahren
Yes=Ja
No=Nein
```

A few noticeable information:
* Empty lines are ignored
* Lines, beginning with double slash "//", are also ignored
* The first equal symbol is interpreted as key-value separator, while the value is able to contain unlimited amounts of equal symbols. This means an equal symbol cannot be used as part of the placeholder.
* A double number sign cannot be used as part of the placeholder, because it is used for indicating a placeholder in templates (see Usage > Templates section in this README).
* SmartyML does not check, if all language files define the identical placeholders. Also, the order of definitions does not matter.

### Setting up languages
Because SmartyML extends Smarty, it can be used just like Smarty.
Replace Smarty-class by SmartyML-class wherever you need a template with multilingual support.
By default, the English language is the available and default language.

To use more than one language (the reason, why you use SmartyML ;-)), you need to make them manually available.
This and other multilingual options are set in the constructor of SmartyML:
* Available languages,
* default language (default: "en" for English),
* current language (default: default language) and
* location of the locales directory, when other than _../locales/_.

Example:
```
$available_languages = ['de', 'en', 'es'];  // default: ['en']
$default_language = 'de';                   // default: 'en'
$current_language = 'es';                   // default: default language, here: 'en'
$locales_dir = '../../lang/';               // default: '../locales/'

$smarty = new SmartyML(
    $available_languages,
    $default_language,
    $current_language,
    $locales_dir
);
```

Unfortunately these parameters always have to be set, when SmartyML is instantiated.
If SmartyML is used at a few different places, you should consider an additional solution.
A solution strongly depends on how your settings are defined (hopefully not global variables).
There are a few popular techniques how to mitigate the overhead:
* Create a class which returns a pre-configured SmartyML-object (like View-class in SPFW) or
* extend SmartyML by an init-method, which returns a pre-configured SmartyML-object.

Note:
It is recommended to use the short forms of a language, e.g. "es" for Spanish.
Short forms can be useful when combining different techniques for localization or internationalization.
SmartyML does not force to use a specific language terminology.

### SPFW
The SPFW-version of SmartyML has only one difference to the stand-alone-version:
There is an init-method for initializing SmartyML by SmartyMlConfig.
The SmartyMlConfig-class is provided by SPFW and can be used for defining Smarty-configuration.
This makes it easier to create instances of SmartyML, because constructor variables are already filled.

## Usage
To use the power of translation, you just need to use the defined placeholders in your existing templates.

### Templates
Each defined placeholder in language files can be used in the templates when wrapped by two number signs: `##placeholder##`.

Example:
```
<div>
    <p>##question##?</p>
    <div>
        <a href="agree">##Yes##</a>
        <a href="abort">##No##</a>
    </div>
</div>
```

This example contains the placeholders _question_, _Yes_ and _No_.

If a placeholder is not defined in a translation file, it is ignored (technically replaced by empty string).

#### Special variable
To access the current language, SmartyML assigns the variable `$lang` to the template.
This can be useful in master templates

Example:
```
<html lang="{$lang}">
[...]
</html>
```

### Assigning variables
When variables are assigned by Smarty's `assign()`-method, they are not modified.
To make SmartyML translating placeholders in assigned variables, they need to be assigned by `assignML()`-method.
`assignML()` has the same method signature than Smarty's `assign()` (see Smarty docs: https://smarty-php.github.io/smarty/designers/language-variables/language-assigned-variables.html).
While `assignML()` accepts any type of variable, **only strings are taken into account for translation**.

Example:
```
$smartyML_object->assignML(
    'question',
    '##question##?'
);
```

## Contact / Support
If you have any questions, please feel free to use the issue section on GitHub.
I am happy to receive bug reports to make SmartyML even better.
When you fix a bug yourself, please consider opening a pull request for SmartyML on GitHub.

For the SPFW integration, please contact the SPFW community.

## Credits
SmartyML 2.0 is a full new development for PHP > 7 and Smarty > 3.x.
The original idea is based on published code from André Rabold on the previous official Smarty discussion board:
http://smarty.incutio.com/?page=SmartyMultiLanguageSupport
