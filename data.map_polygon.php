<?php
require_once("includes/config.php");
require_once("models/singlemaplayer.php");
require_once("models/maplayers.php");
require_once("includes/inc.language.php");
require_once("models/searchpolygon.php");

$polygon = (isset($_CLEAN['polygon'])? $_CLEAN['polygon'] : '');
$map = (isset($_CLEAN['map'])? $_CLEAN['map'] : '');

//allow POST vars
$_CLEANPOST = Sanitize($_POST);
if (isset($_CLEANPOST['polygon'])) {
    $polygon = $_CLEANPOST['polygon'];
}
if (isset($_CLEANPOST['map'])) {
    $map = (isset($_CLEANPOST['map'])? $_CLEANPOST['map'] : '');
}

$mapLayers = new MapLayers();
$layers_to_query = $mapLayers->GetLayersForMap($map, 'raster');

function GetSLDfileAsXML($layer_name) {
    global $siteconfig;

    $use_errors = libxml_use_internal_errors(true);
    $styleXML = simplexml_load_file($siteconfig['path_geoserver_data_dir'] . '/styles/' . $layer_name . '.xml');
    if (false === $styleXML) {
        return ''; //invalid xml or some other error
    }
    libxml_clear_errors();

    $style_array = json_decode(json_encode($styleXML), TRUE);
    $style_flat = FlattenArray($style_array);
    $sldfilename = $style_flat['filename'];
    $sldfile = simplexml_load_file($siteconfig['path_geoserver_data_dir'] . '/styles/' . $sldfilename);
    if (false === $sldfile) {
        return ''; //invalid xml or some other error
    }
    libxml_clear_errors();
    libxml_use_internal_errors($use_errors);

    return $sldfile;
}

//this is amazingly slow for some reason (2min on localhost)
function getSLDfileAsXMLfromGeoserverRestService($layer_name) {
    global $siteconfig;
    global $layer_name;

    $style_url = $siteconfig['path_geoserver'] . '/rest/styles/' . $layer_name . '.sld';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $style_url);
    //curl_setopt($ch, CURLOPT_USERPWD, "admin:geoserver");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $sldfile= curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    if ($info['http_code'] == 401) {
        return ''; //error - e.g. bad credentials
    }
    return $sldfile;
}

function GetRasterLegendDescriptions($layer_name) {

    $style = GetSLDfileAsXML($layer_name);

    if (!$style || $style == '') return array(); //invalid xml or some other error
    $style_array = json_decode(json_encode($style), TRUE);
    $style_flat = FlattenArray($style_array);

    $legend = array();
    foreach ($style_flat as $key => $value) {
        $keypos = stripos($key,"ColorMapEntry.");
        if ($keypos) {
            $keyposend = stripos($key,".@attributes.");
            $keypos = $keypos + strlen("ColorMapEntry.");
            $entryid = substr($key,$keypos, $keyposend - $keypos);
            if (stripos($key,"attributes.quantity")) {
                $legend[$entryid]['quantity'] = $value;
            } elseif (stripos($key,"attributes.label")) {
                $legend[$entryid]['label'] = $value;
            }
        }
    }
    $legend_assoc = array();
    foreach ($legend as $key => $value) {
        $legend_assoc[$value['quantity']] = $value['label'];
    }
    return $legend_assoc;
}
//gets stats for raster values pixel counts within polygon
function GetRasterStatsForPolygon($polygon, $layer_name) {
    global $siteconfig;

    $legend =  GetRasterLegendDescriptions($layer_name);

    //TODO: load raster layers into db when sync
    //TODO: get raster name from call

    $query = "SELECT (pvc).value, SUM((pvc).count) AS tot_pix
 FROM raster_" . $layer_name . " r
  INNER JOIN 
ST_GeomFromText('" . $polygon . "',4326) AS geom 
  ON ST_Intersects(r.rast, geom), 
    ST_ValueCount(ST_Clip(r.rast,geom),1) AS pvc
  GROUP BY (pvc).value
 ORDER BY (pvc).value;";

    $result = @pg_query_params($siteconfig['dwc_db_conn'], $query, array());
    $resArray = array();
    if (!$result) {
        return $resArray; //SQL error
    } else {
        $total_pix = 0;
        while ($rasterRow = pg_fetch_array($result)) {
            $total_pix += $rasterRow['tot_pix'];
            $rowVal = array();
            $rowVal['value'] = $rasterRow['value'];
            $rowVal['label'] = (array_key_exists($rasterRow['value'], $legend)? htmlspecialchars($legend[$rasterRow['value']]) : $rasterRow['value']);
            $rowVal['tot_pix'] = $rasterRow['tot_pix'];
            $rowVal['percentage'] = 0; //complete later
            $resArray[] = $rowVal;
        }
        foreach($resArray as $i => $row) {
            $row['percentage'] = ($row['tot_pix']/$total_pix)*100;
            $resArray[$i] = $row;
        }
    }
    return $resArray;
}

function SavePolygon($polygonToSave) {
    $save_msg = "";
    $searchPoly = new SearchPolygon();
    $polyID = $searchPoly->WriteSearchPolygonToDB($polygonToSave, $save_msg);
    if ($save_msg != '') { //error TODO: this is a bit messy
        echo 'Error: ' . $save_msg;
        return -1;
    }
    return $polyID;
}

$arr = array();

$polygonId = SavePolygon($polygon);
$layerStats = array();
$statsArr = array();
$statsArr['label'] = 'The polygon ID';
$statsArr['value'] = $polygonId;
$layerStats['displayname'] = '__polygonID';
$layerStats['stats'] = array();
$layerStats['stats'][] = $statsArr;
$arr[] = $layerStats;

if (count($layers_to_query) != 0 && $polygon != '') {
    foreach($layers_to_query as $layer) {
        $layer_name_parsed = explode(':', $layer['geoserver_name']);
        $layer_name = end($layer_name_parsed);
        $statsArr = GetRasterStatsForPolygon($polygon, $layer_name);
        $layerStats = array();
        if ($layer['displayname'] != '') {
            $layerStats['displayname'] = getMLText($layer['displayname']);
        } else {
            $layerStats['displayname'] = $layer_name;
        }
        $layerStats['stats'] = $statsArr;
        $arr[] = $layerStats;
    }
}
header ("Content-Type:application/json");
echo json_encode($arr);
?>