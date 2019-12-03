<?php
require_once("includes/config.php");

$id = (isset($_CLEAN['id'])? $_CLEAN['id'] : '');
$name = (isset($_CLEAN['name'])? $_CLEAN['name'] : '');

//name overrides id if specified

if ($name > '') { //try find valid id
    $id2 = 0;
    $res = pg_query_params("SELECT id FROM gislayer WHERE geoserver_name = $1", array($name));
    if ($res) {
        $row = pg_fetch_array($res, null, PGSQL_ASSOC);
        if ($row) $id2 = $row['id']; //valid name
    }
    if (!$id2 && !strpos(":",$name)) { //try prepending 'cite:'
        $res = pg_query_params("SELECT id FROM gislayer WHERE geoserver_name = $1", array('cite:' . $name));
        if ($res) {
            $row = pg_fetch_array($res, null, PGSQL_ASSOC);
            if ($row) $id2 = $row['id']; //valid name
        }
    }
    if ($id2 > 0) $id = $id2;
}

$layerid = 0;
if ($id > '') {
    if (is_numeric($id)) {
        $id2 = $id + 0;
        if (is_int($id2)) {
            $res = pg_query_params("SELECT * FROM gislayer_feature WHERE gislayer_id = $1", array($id2));
            if ($res) {
                $row = pg_fetch_array($res, null, PGSQL_ASSOC);
                if ($row) $layerid = $id2; //valid id
            }
        }
    }
}

//get features for layer specified by ID 
function GetFeaturesForLayer ($id) {    
    $features = array();
    
    $res = pg_query_params("SELECT *, CASE WHEN description_text != '' THEN 1 ELSE 0 END AS has_description FROM gislayer_feature WHERE gislayer_id = $1 ORDER BY has_description DESC, description_text ASC, attributes_concat ASC", array($id));
    if (!$res) return $features; //error
    while ($row = pg_fetch_array($res, null, PGSQL_ASSOC)) {
        $feature = array();
        $feature['fid'] = $row['fid'];
        if ($row['description_text'] > '') {
            $feature['descr'] = $row['description_text'];
        } else {
            $feature['descr'] = $row['attributes_concat'];
        }
        $features[] = $feature;
    }
    
    return $features;
}

$arr = array();
if ($layerid > 0) $arr = GetFeaturesForLayer($layerid);
echo json_encode($arr);
?>