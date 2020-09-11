<?php

$LANG = array();
$MISSING_LANG = array();

foreach (getLanguages() as $_lang) {
    if (file_exists("includes/languages/" . $_lang . "/lang.inc")) {
        include "includes/languages/" . $_lang . "/lang.inc";
        $LANG[$_lang] = $text;
    }
}
unset($text);

function getLanguages() {
    $languages = array();
    $path = "includes/languages/";
    $handle = opendir($path);

    while ($entry = readdir($handle)) {
        if ($entry == ".." || $entry == ".")
            continue;
        else if (is_dir($path . $entry))
            array_push($languages, $entry);
    }
    closedir($handle);

    asort($languages);
    return $languages;
}

/**
 * Get translation
 *
 * Returns the translation for a given key. It will replace markers
 * in the form [xxx] with those elements from the array $replace.
 * A default text can be gіven for the case, that there is no translation
 * available. The fourth parameter can override the currently set language
 * in the session or the default language from the configuration.
 *
 * @param string $key key of translation text
 * @param array $replace list of values that replace markers in the text
 * @param string $defaulttext text used if no translation can be found
 * @param string $lang use this language instead of the currently set lang
 */
function getMLText($key, $replace = array(), $defaulttext = "", $lang = "") { /* begin function */
    global $LANG, $MISSING_LANG;
    global $USER_SESSION;

    if (!$lang) {
        $lang = $USER_SESSION['language'];        
    }
    
    if (!isset($LANG[$lang][$key]) || !$LANG[$lang][$key]) {
        if (!$defaulttext) {
            $MISSING_LANG[$key] = $lang; //$_SERVER['SCRIPT_NAME'];
            if (!empty($LANG["en_GB"][$key])) {
                $tmpText = $LANG["en_GB"][$key];
            } else {
                $tmpText = '**' . $key . '**';
            }
        } else
            $tmpText = $defaulttext;
    } else
        $tmpText = $LANG[$lang][$key];

    if (is_null($replace) || count($replace) == 0)
        return $tmpText;

    $keys = array_keys($replace);
    foreach ($keys as $key)
        $tmpText = str_replace("[" . $key . "]", $replace[$key], $tmpText);

    return $tmpText;
}

function printMLText($key, $replace = array(), $defaulttext = "", $lang="") /* begin function */
{
	print getMLText($key, $replace, $defaulttext, $lang);
}

//get an array of keys beginning with a particular prefix
function getMLArrayStartingWith($keyprefix, $lang = "") {
    global $LANG, $MISSING_LANG;
    global $siteconfig;
    global $USER_SESSION;

    if (!$lang) {
        $lang = $USER_SESSION['language'];        
    }
    $retArr = array();
    $prefixlen = strlen($keyprefix);
    foreach ($LANG[$lang] as $key => $value) {
        if (substr($key, 0, $prefixlen) == $keyprefix) {
            $retArr[$key] = $value;
        }
    }
    return $retArr;
}
?>