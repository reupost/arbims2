<?php
/* deprecated: */

function path_for($file) {
    global $siteconfig;
    return $siteconfig['path_basefolder'] . '/' . $file;
}

function url_for($item, $params = '') {
    global $siteconfig;
    if (strpos($item, '.') === false) { /* assume its a PHP file that needs to be rendered.  TODO: rethink this */
        return $siteconfig['path_baseurl'] . '/' . $item . '.php' . ($params > '' ? '?' . $params : '');
    } else {
        return $siteconfig['path_baseurl'] . '/' . $item . ($params > '' ? '?' . $params : '');
    }
}

//returns the value, or 1 if non-numeric or not an integer
function GetCleanInteger($from) {
    if (is_numeric($from)) {
        if (is_int($from + 0))
            return $from + 0;
    }
    return 1;
}

//function GetCleanNumber($from) {
//}

function GetCleanString($from) {
    return trim(preg_replace('/[^a-zA-Z,]/', '', $from));
}

function GetCurrentURL()
{
    $currentURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
    $currentURL .= $_SERVER["SERVER_NAME"];
 
    if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
    {
        $currentURL .= ":".$_SERVER["SERVER_PORT"];
    } 
 
        $currentURL .= $_SERVER["REQUEST_URI"];
    return $currentURL;
}

//get a valid 'first record on page' considering total records
//side-effect: can set $pageno to 1 if exceeded number of records
function GetPageRecordOffset($recs_per_page, $total_recs, &$pageno) {    
    $start_rec = ($pageno - 1) * $recs_per_page;
    if ($start_rec > $total_recs) { /* applied a filter so fewer items than current page, e.g. on page 3 applied filter so much less items to be listed */
        $pageno = 1;
        $start_rec = ($pageno - 1) * $recs_per_page;
    }
    return $start_rec;
}

define("FILE_PUT_CONTENTS_ATOMIC_TEMP", dirname(__FILE__)."/cache"); 
define("FILE_PUT_CONTENTS_ATOMIC_MODE", 0777); 

function file_put_contents_atomic($filename, $content) { 
   
    $temp = tempnam(FILE_PUT_CONTENTS_ATOMIC_TEMP, 'temp'); 
    if (!($f = @fopen($temp, 'wb'))) { 
        $temp = FILE_PUT_CONTENTS_ATOMIC_TEMP . DIRECTORY_SEPARATOR . uniqid('temp'); 
        if (!($f = @fopen($temp, 'wb'))) { 
            trigger_error("file_put_contents_atomic() : error writing temporary file '$temp'", E_USER_WARNING); 
            return false; 
        } 
    } 
   
    fwrite($f, $content); 
    fclose($f); 
   
    if (!@rename($temp, $filename)) { 
        @unlink($filename); 
        @rename($temp, $filename); 
    } 
   
    @chmod($filename, FILE_PUT_CONTENTS_ATOMIC_MODE); 
   
    return true; 
   
}

//takes an hierarchical array and returns a 1D array
function FlattenArray($array, $prefix = '') {
    $result = array();
    foreach($array as $key=>$value) {
        if(is_array($value)) {
            $result = $result + FlattenArray($value, $prefix . $key . '.');
        }
        else {
            $value = trim($value);
            if ($value != "")
                $result[$prefix . $key] = $value;
        }
    }
    return $result;
}

function UnzipArchive ($archive, $folderto = '') {
    // get the absolute path to $file
    if ($folderto == '') $folderto = pathinfo(realpath($archive), PATHINFO_DIRNAME); //default to same dir as archive file
    $zip = new ZipArchive;
    $res = $zip->open($archive);
    if ($res === TRUE) {
        // extract it to the path we determined above
        $zip->extractTo($folderto);
        $zip->close();
    } else {
        //failed to open archive file
        return 0;
    }
    return -1;
}

/* function GetFileExt($filename) {
	$arrName = explode(".",$filename);
	return $arrName[count($arrName)-1];
} */

//assumes valid file path string with directories / separated
function GetFileWithoutExtFromPath($filepath) {
	$arrPath = explode("/",$filepath);
    $filenameWithExt = $arrPath[count($arrPath)-1];
    $arrName = explode(".",$filenameWithExt);
    array_pop($arrName); //remove extension
	return implode(".", $arrName);
}

//assumes valid file path string with directories / separated
function GetFileFromPath($filepath) {
	$arrPath = explode("/",$filepath);
    $filenameWithExt = $arrPath[count($arrPath)-1];    
	return $filenameWithExt;
}

function GetCorrectMTime($filePath) 
{ 
    $time = filemtime($filePath); 

    $isDST = (date('I', $time) == 1); 
    $systemDST = (date('I') == 1); 

    $adjustment = 0; 

    if($isDST == false && $systemDST == true) 
        $adjustment = 3600; 
    
    else if($isDST == true && $systemDST == false) 
        $adjustment = -3600; 

    else 
        $adjustment = 0; 

    return ($time + $adjustment); 
} 

function DeleteFilesOlderThan($from_dir, $age_in_secs) {
    if ($handle = opendir($from_dir)) {

        while (false !== ($file = readdir($handle))) { 
            $filelastmodified = GetCorrectMTime($from_dir . '/' . $file);

            if((time() - $filelastmodified) > $age_in_secs && is_file($from_dir . '/' . $file))
            {
                unlink($from_dir . '/' . $file);
            }

        }

        closedir($handle); 
    }
}

//to make sure there is no sql-injection
function Sanitize($elem) 
{ 
    if(!is_array($elem)) 
        $elem = htmlentities($elem,ENT_QUOTES,"UTF-8"); 
    else 
        foreach ($elem as $key => $value) 
            $elem[$key] = Sanitize($value); 
    return $elem; 
} 

//returns a '#001122' string
function GetRandomColorHex() {
    return '#' . substr('00000' . dechex(mt_rand(0, 0xffffff)), -6);
}

function GetMessagePopupJS($session_msg, $type = 'success') {
    $js = "";
    if ($session_msg > '') {
        $js = "<script>";
        $js .= "noty({";
        $js .= "	text: \"" . htmlspecialchars($session_msg) . "\",";
        $js .= "	type: '" . $type . "',";
        $js .= "  dismissQueue: true,";
        $js .= "	layout: 'topRight',";
        $js .= "	theme: 'defaultTheme',";
        $js .= "	timeout: 1500,";
        $js .= "	_template: '<div class=\"noty_message alert alert-block alert-error\"><span class=\"noty_text\"></span><div class=\"noty_close\"></div></div>'";
        $js .= "});";
        $js .= "</script>";
    }
    return $js;
}
?>