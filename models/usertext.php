<?php

require_once("includes/inc.language.php");

//return empty string if error, otherwise html
function GetUserText($key, $lang) {
    $res = pg_query_params("SELECT * FROM usertext WHERE key = $1 AND lang = $2", array($key, $lang));
    if (!$res) return "";
    $row = pg_fetch_array($res);
    if (!$row) return "";
    return $row['html'];
}

//return true on success, false on failure
function SetUserText($key, $lang, $html, &$save_msg) {
    $res = pg_query_params("UPDATE usertext SET html = $1 WHERE key = $2 AND lang = $3", array($html, $key, $lang));
    if (!$res) { 
        $save_msg = getMLtext("save_text_error");
        return 0;
    } elseif (!pg_affected_rows($res)) { //try insert, maybe key was missing
        $res = pg_query_params("INSERT INTO usertext VALUES ($1, $2, $3)", array($key, $html, $lang));
        if (!$res) {
            $save_msg = getMLtext("save_text_error");
            return 0;
        }
    }
    $save_msg = getMLtext("save_text_ok");
    return -1;
}
?>