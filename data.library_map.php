<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/inc.language.php");

global $siteconfig;

$params = array();
$librarysel = (isset($_CLEAN['library'])? $_CLEAN['library'] : '');
$params['fid'] = (isset($_CLEAN['fid'])? $_CLEAN['fid'] : '');
$region = (isset($_CLEAN['region'])? $_CLEAN['region'] : '');

/* $librarysel = (isset($_POST['library'])? $_POST['library'] : '');
$params['fid'] = (isset($_POST['fid'])? $_POST['fid'] : '');
$region = (isset($_POST['region'])? $_POST['region'] : ''); */

//can specify 1 library
//can specify all libraries by leaving blank or providing an invalid library
//OR can specify a region which implies certain library selection (overridden by specific library selection, above)

$params['library'] = array();
if (array_key_exists($librarysel, $siteconfig['media_dbs'])) {
    $params['library'] = array($siteconfig['media_dbs'][$librarysel]); //one
} else {
    if ($librarysel == '' && $region > '') {
       switch ($region) { //hardcoded
           case 'albertine'     : $params['library'] = array($siteconfig['media_dbs']['eia'], $siteconfig['media_dbs']['land']);
                                    break;
           case 'mountains'     : $params['library'] = array($siteconfig['media_dbs']['mnt']); break;
           case 'lakes'         : $params['library'] = array($siteconfig['media_dbs']['lake']); break;
           default              : $params['library'] = array(); break; //get something right, please
       }
    } else { //all
        $params['library'] = array(
            $siteconfig['media_dbs']['eia'],
            $siteconfig['media_dbs']['land'],
            $siteconfig['media_dbs']['lake'],
            $siteconfig['media_dbs']['mnt']);
    }
}

//get documents from $library which are linked to the layer AND/OR the layer's feature $fid
function GetLibraryDocsForMapFeature ($arrlibraries, $fid) {
    global $siteconfig;
    $doclinks = array();
    
    if (sizeof($arrlibraries) == 0) return $doclinks; //no libraries
        
    mysql_connect($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']) OR DIE("<p><b>DATABASE ERROR: </b>Unable to connect to database server</p>");
    
    //get layer geoserver name from fid
    $layerid = 0;
            
    foreach ($arrlibraries as $librarydb) {
    
        @mysql_select_db($librarydb) or die( "<p><b>DATABASE ERROR: </b>Unable to open database</p>");
    
        $libraryURL = str_replace("_","-",$librarydb); //note: this relies on the DB and the URL being simply related
    
        if ($layerid == 0) { //haven't found layer id - should be same in all dbs so jsut use first
            $fidbits = explode('.',$fid);
            $sql = "SELECT id FROM tblgislayer WHERE geoserver_name = CONCAT('cite:','" . $fidbits[0] . "')";    
            $res = mysql_query($sql);
            if (!$res) return "SQL error";
            while ($row = mysql_fetch_array($res)) { //should only be one
                $layerid = $row['id'];
            }
        }
        //linked to actual feature
        $sql = "SELECT * FROM tbldocuments WHERE linkedgisfeature = '" . mysql_real_escape_string($fid) . "' ORDER BY `name` ASC ";    
        $res = mysql_query($sql);
        if (!$res) return "SQL error";
        while ($row = mysql_fetch_array($res)) {        
            $doclink = array();
            $doclink['library'] = $librarydb;
            $doclink['type'] = getMLtext('map_feature');
            $doclink['url'] = $libraryURL . "/out/out.ViewDocument.php?documentid=" . $row['id'] . "&showtree=1'";
            $doclink['name'] = $row['name'];
            $doclinks[] = $doclink;        
        }
    
        //linked to parent layer
        $sql = "SELECT * FROM tbldocuments WHERE ((IFNULL(linkedgisfeature,'')='' OR linkedgisfeature='0') AND linkedgislayer = " . $layerid . ") ORDER BY `name` ASC";    
        $res = mysql_query($sql);
        if (!$res) return "SQL error";
        while ($row = mysql_fetch_array($res)) {
            $doclink = array();
            $doclink['library'] = $librarydb;
            $doclink['type'] = getMLtext('map_layer');
            $doclink['url'] = $libraryURL . "/out/out.ViewDocument.php?documentid=" . $row['id'] . "&showtree=1'";
            $doclink['name'] = $row['name'];
            $doclinks[] = $doclink;
        }
    }
    //sort?
    
    //foreach ($doclinks as $dl) {
    //    echo "<a href='" . $dl['url'] . "'>" . htmlspecialchars($dl['name']) . "</a> [" . $dl['type'] . "]<br/>";
    //}
    return $doclinks;
}

$arr = GetLibraryDocsForMapFeature($params['library'], $params['fid']);
echo json_encode($arr);
?>