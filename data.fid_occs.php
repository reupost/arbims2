<?php
require_once("includes/config.php");

$fid = (isset($_CLEAN['fid'])? $_CLEAN['fid'] : '');

if ($fid == '') exit;
$fidsplit = explode(".",$fid);
if (sizeof($fidsplit) != 2) exit; //invalid fid

$parser = null;

$occs_array = array();    
$insidelist = false;
$GMLcontent = "";

//populates $occs_array with ID's of occurrence records within the given FID
function GetOccsForFID($fid) {
    global $siteconfig;
    global $parser;
    global $occs_array;
    global $GMLcontent;
    
    $arr = explode(".", $fid);
    $layer_id = "cite:" . $arr[0];
    $wfs_server = $siteconfig['path_geoserver'] . '/wfs';
    $request = "request=getfeature&version=1.1.0&service=wfs&typename=cite:occurrence&CQL_FILTER=INTERSECTS(_geom2,querySingle('" . $layer_id . "','the_geom','IN(''" . $fid . "'')'))";
    $wfs_query_url = $wfs_server . "?" . $request;
    
    $query = fopen($wfs_query_url, "r");
    $GMLcontent = stream_get_contents($query);
    fclose($query);

    //var_dump($content);
    
    $parser = xml_parser_create("UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_set_element_handler($parser, "_startElementAttrib", "_endElementAttrib");
    xml_set_character_data_handler($parser, "_characterDataAttrib");  
    $occs_array = array(); 
    
    xml_parse($parser, $GMLcontent, TRUE);
    xml_parser_free($parser);
    
    //var_dump($occs_array);
}    

function _startElementAttrib($parser, $name, $attrs) {
    global $insideid;
    if ($name == 'cite:_id') {
        $insideid = true;
    } else {
        $insideid = false;
    }
}

function _endElementAttrib($parser, $name) {
}

function _characterDataAttrib($parser, $data) {
    global $insideid;
    global $occs_array;
    if ($insideid) {
        $occs_array[] = $data;
    }
}

//writes the array to the database with the sessionid (first deleting any existing)
function WriteOccsToDB() {
    global $occs_array;
    global $USER_SESSION;        
    global $fid;
    
    $session_id = session_id();
    
    $res = pg_query_params("DELETE FROM session_searchdata WHERE sessionid = $1", array($session_id));
    if (!$res) return 0; //SQL error
    
    //also prune off any old data from the table in a general sense to avoid extinct session searches clogging up the works
    $res = pg_query_params("DELETE FROM session_searchdata WHERE when_added < NOW() - INTERVAL '30 days'", array());
    
    //echo $USER_SESSION['username'] . " " . $USER_SESSION['id'] . " " . $session_id . "<br/>";
    foreach ($occs_array as $occid) {
        $res = pg_query_params("INSERT INTO session_searchdata (sessionid, userid, occurrenceid, description) VALUES ($1, $2, $3, $4)", array($session_id, $USER_SESSION['id'], intval($occid), $fid));
        if (!$res) return 0;
        if (pg_affected_rows($res) != 1) return 0; //some other error?
    }
    return -1;
}

GetOccsForFID($fid);
$ok = WriteOccsToDB();
header ("Content-Type:text/xml");
//pass WFS request response back to caller
echo $GMLcontent;
?>