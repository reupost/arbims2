<?php

require_once("models/table_base.php");

const GEOSERVER_WORKSPACE = "cite:";

$DEBUGGING = true;

class MapLayers extends Table_Base {

    var $sql_listing = "SELECT *, EXTRACT(EPOCH FROM CURRENT_TIMESTAMP-whenadded )/3600 AS layer_age_hours, CASE WHEN EXTRACT(EPOCH FROM CURRENT_TIMESTAMP-whenadded )/3600 < 24.0 THEN 'new_layer' ELSE 'no' END AS layer_is_new FROM gislayer"; 
    var $fieldmap_orderby = array(
        "layer" => "layer_order",
        "name" => "displayname",
        "map_service" => "geoserver_name",
        "allow_download" => "allow_download",
        "allow_identify" => "allow_identify",
        "disabled" => "disabled",
        "allow_display_albertine" => "allow_display_albertine",
        "allow_display_mountains" => "allow_display_mountains",
        "allow_display_lakes" => "allow_display_lakes",
        "layer_is_new" => "layer_is_new"
    );
    var $fieldmap_filterby = array(
        "allow_download" => "allow_download", 
        "allow_identify" => "allow_identify",
        "disabled" => "disabled",
        "layer_type" => "layer_type",
        "allow_display_albertine" => "allow_display_albertine",
        "allow_display_mountains" => "allow_display_mountains",
        "allow_display_lakes" => "allow_display_lakes",
        "filtercontent" => "to_tsvector('english', lower(coalesce(displayname,'') || ' ' || coalesce(geoserver_name,'') || ' ' || coalesce(projection,'') )) @@ plainto_tsquery('***')",
    );    
    
    public function GetJavascriptLayerArray() {
        $layers_arr = $this->GetRecords();
        
        $jstring = "var user_layers = [";    
        $firstelem = 1;
        foreach ($layers_arr as $layer) {
            if ($firstelem) {
                $firstelem = 0;
            } else {
                $jstring .=  ", ";
            }
            $jstring .= "{";
            $firstkey = 1;
            foreach (array_keys($layer) as $key) {
                if ($firstkey) {
                    $firstkey = 0;
                } else {
                    $jstring .= ", ";
                }
                $jstring .= $key . ":\"" . $layer[$key] . "\"";
            }
            $jstring .= ", ml_name:\"" . getMLtext($layer['displayname']) . "\"";
            $jstring .= "}";
        }    
        $jstring .= "];";
        $jstring .= "var openlayers_obj = [];";
        return $jstring;
    }
    
    //requires same layers in same order as layerarray
    public function GetJavascriptLayerInit() {
        global $siteconfig;

        $layers_arr = $this->GetRecords();
        
        $jstring = "";
        $array_elem = 0;
        foreach ($layers_arr as $layer) {
            //$jstring .= "user_layers[" . $array_elem . "].openlayers_obj = new OpenLayers.Layer.WMS(";
            $jstring .= "openlayers_obj[" . $array_elem . "] = new OpenLayers.Layer.WMS(";
            $jstring .= "\"" . getMLtext($layer['displayname']) . "\", \"" . $siteconfig['path_geoserver'] . "/wms\",";
            $jstring .= "{";
            $jstring .= "LAYERS: '" . $layer['geoserver_name'] . "',";
            $jstring .= "STYLES: '',";
            $jstring .= "format: format,";
            $jstring .= "transparent: true,";
            $jstring .= "tiled: true,";
            $jstring .= "tilesOrigin: map.maxExtent.left + ',' + map.maxExtent.bottom";
            $jstring .= "},";
            $jstring .= "{";
            $jstring .= "buffer: 0,";
            $jstring .= "displayOutsideMaxExtent: true,";
            $jstring .= "isBaseLayer: false,";
            $jstring .= "yx: {'" . $layer['projection'] . "': true}";
            $jstring .= "}";
            $jstring .= ");";
            $array_elem++;
        }
        return $jstring;
    }
    
    public function GetJavascriptLayerList($onlyidentifyable = false) {        
        $layers_arr = $this->GetRecords();
        
        $jstring = "";
        $array_elem = 0;
        foreach ($layers_arr as $layer) {
            if (!$onlyidentifyable || $layer['allow_identify'] == "t") {
                //$jstring .= "user_layers[" . $array_elem . "].openlayers_obj, ";
                $jstring .= "openlayers_obj[" . $array_elem . "], ";
            }
            $array_elem++;
        }
        $jstring = substr($jstring,0,-2);
        return $jstring;
    }
    
    //get a list of attributes for a GIS layer using a WFS call
    public function GetLayerAttributes($layername) {
        global $siteconfig;
        require_once($siteconfig['path_basefolder'] . '/includes/wfs-parser.php'); 
                
        $wfs_server                     = $siteconfig['path_geoserver'] . '/wfs?';
        $wfs_server_getlayerattributes  = $wfs_server."SERVICE=wfs&VERSION=1.1.0&REQUEST=describefeaturetype&TYPENAME=" . $layername;

        $geoserver      = fopen($wfs_server_getlayerattributes, "r");
        $content        = stream_get_contents($geoserver);
        fclose($geoserver);

        $caps = new WFSParser();
        $caps->SetWFSParserAttributes($layername);
        $caps->parseAttributes($content);
        $caps->free_parser();
        
        return $caps->GetAttributeList();
    }
    
    //get a list of features for a GIS layer using a WFS call - exclude geometry attributes to save bandwidth on call
    public function GetLayerFeaturesNonGeom($layername) {
        global $siteconfig;        
        require_once($siteconfig['path_basefolder'] . '/includes/wfs-parser.php'); 
        
        $attribarray = $this->GetLayerAttributes($layername);
        
        //remove geom, _geom or the_geom attributes from array
        $cleanattriblist = "";        
        foreach ($attribarray as $key => $attrib) {           
            if (strtolower($attrib) == '_geom' || strtolower($attrib) == '_geom' || strtolower($attrib) == 'the_geom') continue;
            $cleanattriblist .= ($cleanattriblist > ''? ',':'') . $attrib;
        }
                
        $wfs_server                     = $siteconfig['path_geoserver'] . '/wfs?';
        $wfs_server_getlayerfeatures    = $wfs_server."SERVICE=wfs&version=1.1.0&request=GetFeature&typeName=" . $layername . "&propertyname=" . $cleanattriblist;
        $geoserver      = fopen($wfs_server_getlayerfeatures, "r");
        $content        = stream_get_contents($geoserver);
        fclose($geoserver);
        
        $caps = new WFSParser();
        $caps->SetWFSParserFeatures($layername);
        $caps->parseFeatures($content);
        $caps->free_parser();
        
        return $caps->GetFeatureList(); 
    }
    
   
    public function WriteNonGeomLayerFeaturesToDB($layername) {
        global $siteconfig;
        global $DEBUGGING;

        if ($DEBUGGING) {
            echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB - " . $layername . "<br/>";
            myFlush();
        }
        $res = pg_query_params("SELECT id, layer_type, datafile_path, db_table_name FROM gislayer WHERE geoserver_name = $1", array($layername));
        if (!$res) return 0; //sql error
        if (!($row = pg_fetch_array($res))) return 0; //layer not found
        
        $feats = $this->GetLayerFeaturesNonGeom($layername);
        
        pg_query_params("DELETE FROM gislayer_feature WHERE gislayer_id = $1", array($row['id']));
        foreach ($feats as $feat) {
            $concat_attribs = '';
            $descr = '';
            foreach ($feat as $attrib => $value) {
                if ($attrib == 'fid') continue;
                if (strtolower($attrib) == 'descriptio') $descr = $value;
                $concat_attribs .= ($concat_attribs > ''? "; " : "") . $attrib . ": " . $value;                
            }
            pg_query_params("INSERT INTO gislayer_feature (fid, gislayer_id, attributes_concat, description_text) VALUES ($1, $2, $3, $4)", array($feat['fid'], $row['id'], $concat_attribs, $descr));
        }

        if ($row['layer_type'] == 'raster') {
            if ($DEBUGGING) {
                echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $layername . "<br/>";
                myFlush();
            }
            if ($row['datafile_path'] > '') {
                $output = array();
                $outputlastline = exec("\"" . $siteconfig['path_raster2pgsql_exe'] . "\" -d -s 4326 -I -C -M -t 100x100 " . "\"" . $row['datafile_path'] . "\" public." . $row['db_table_name'] . " > " . $siteconfig['path_tmp'] . "/rast.sql", $output);
                //TODO: check for errors
                $output = array();
                $outputlastline = exec("\"" . $siteconfig['path_psql_exe'] . "\"-d arbims -f " . $siteconfig['path_tmp'] . "/rast.sql -U root", $output);
                //TODO: check for errors
            } else {
                if ($DEBUGGING) {
                    echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $layername . " - no datafile specified!<br/>";
                    myFlush();
                }
            }
            if ($DEBUGGING) {
                echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $layername . " - finished<br/>";
                myFlush();
            }
        }
        return -1;  
    }
    
    //push revised gislayer and gislayer_feature tables to libraries
    public function SynchGISLayerDataToLibraries() {
        global $siteconfig;
        global $DEBUGGING;
		// Create connection
		$conn = new mysqli($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']);

		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
        //mysql_connect($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']) OR DIE("<p><b>DATABASE ERROR: </b>Unable to connect to database server</p>");
        foreach ($siteconfig['media_dbs'] as $theme => $dbname) {            
            @mysqli_select_db($conn, $dbname) or die( "<p><b>DATABASE ERROR: </b>Unable to open database $dbname</p>");
            mysqli_query($conn,"DELETE FROM tblgislayer");
            $from = pg_query_params("SELECT * FROM gislayer WHERE disabled = false AND allow_identify = true", array());
            if ($DEBUGGING) {
                echo date("h:i:sa") . ": SynchGISLayerDataToLibraries - " . $dbname . "<br/>";
                myFlush();
            }
            while ($fromrow = pg_fetch_array($from)) {
                $sql = "INSERT INTO tblgislayer (id, layer_order, displayname, geoserver_name, ";
                $sql .= "allow_display_albertine, allow_display_mountains, allow_display_lakes, disabled) ";
                $sql .= "VALUES (";
                $sql .= $fromrow['id'] . ",";
                $sql .= $fromrow['layer_order'] . ",";
                $sql .= "'" . mysqli_real_escape_string($conn,$fromrow['displayname']) . "',";
                $sql .= "'" . mysqli_real_escape_string($conn,$fromrow['geoserver_name']) . "',";
                $sql .= ($fromrow['allow_display_albertine'] == 't'? 'true' : 'false') . ",";
                $sql .= ($fromrow['allow_display_mountains'] == 't'? 'true' : 'false') . ",";
                $sql .= ($fromrow['allow_display_lakes'] == 't'? 'true' : 'false') . ",";
                $sql .= ($fromrow['disabled'] == 't'? 'true' : 'false') . ")";
                //echo $sql;
                $res = mysqli_query($conn,$sql); //TODO: error-checks
            }
            mysqli_query($conn,"DELETE FROM tblgislayer_feature");
            $from = pg_query_params("SELECT * FROM gislayer_feature", array());
            while ($fromrow = pg_fetch_array($from)) {
                $sql = "INSERT INTO tblgislayer_feature (fid, gislayer_id, attributes_concat, description_text) ";
                $sql .= "VALUES (";
                $sql .= "'" . $fromrow['fid'] . "',";
                $sql .= $fromrow['gislayer_id'] . ",";
                $sql .= "'" . mysqli_real_escape_string($conn,$fromrow['attributes_concat']) . "',";
                $sql .= "'" . mysqli_real_escape_string($conn,$fromrow['description_text']) . "')"; 
                //echo $sql;
                $res = mysqli_query($conn,$sql); //TODO: error-checks
            }
        }
    }
    
    //returns geoserver WMS layers list object
    private function GetGeoserverLayers() {
        global $siteconfig;
        require_once($siteconfig['path_basefolder'] . '/includes/wms-parser.php'); 
                
        $wms_server                 = $siteconfig['path_geoserver'];
        $wms_server_ows             = $wms_server."/ows?";
        $wms_server_getcapabilities = $wms_server_ows."service=wms&version=1.1.1&request=GetCapabilities";

        //$gestor     = fopen($wms_server_getcapabilities, "r");
        //$contenido  = stream_get_contents($gestor);
        //fclose($gestor);

        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,$wms_server_getcapabilities);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
        $contenido = curl_exec($curl_handle);
        curl_close($curl_handle);

        $caps = new CapabilitiesParser();
        $caps->parse($contenido);
        $caps->free_parser();
        
        return $caps;
    }

    //returns geoserver WCS XML
    private function GetGeoserverWCS() {
        global $siteconfig;

        $wms_server                 = $siteconfig['path_geoserver'];
        $wms_server_ows             = $wms_server."/ows?";
        $wms_server_getcapabilities = $wms_server_ows."service=wcs&version=1.1.1&request=GetCapabilities";

        //$gestor     = fopen($wms_server_getcapabilities, "r");
        //$contenido  = stream_get_contents($gestor);
        //fclose($gestor);

        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,$wms_server_getcapabilities);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
        $contenido = curl_exec($curl_handle);
        curl_close($curl_handle);

        return $contenido;
    }

    //returns 'raster' (if found in WCS XML) or otherwise 'vector'
    private function GetLayerType($wcsXML, $layer) {
        $inXML = stripos($wcsXML, "<wcs:Identifier>" . $layer . "</wcs:Identifier>");
        if ($inXML) return 'raster';
        return 'vector';
    }
    
    //synchronises gislayer table with geoserver layers
    //returns false if no new layers, true if at least one layer which needs to be configured
    public function UpdateLayersFromGeoserver() {
        global $siteconfig;
        global $DEBUGGING;

        if ($DEBUGGING) {
            echo date("h:i:sa") . ": UpdateLayersFromGeoserver - Start<br/>";
            myFlush();
        }
        $glayers = $this->GetGeoserverLayers();
        if ($DEBUGGING) {
            echo date("h:i:sa") . ": UpdateLayersFromGeoserver - retrieved layers from geoserver<br/>";
            myFlush();
        }
        $rasterlayersWCS = $this->GetGeoserverWCS();
        if ($DEBUGGING) {
            echo date("h:i:sa") . ": UpdateLayersFromGeoserver - retrieved WCS from geoserver<br/>";
            myFlush();
        }

        $new_layers = false;

        $select_workspace_length=strlen(GEOSERVER_WORKSPACE);
        
        //initialise check on DB
        pg_query_params("UPDATE gislayer SET in_geoserver = false", array());
        
        foreach ($glayers->layers as $d) {
            if ($DEBUGGING) {
                echo date("h:i:sa") . ": UpdateLayersFromGeoserver - processing " . $d['Name'] . "<br/>";
                myFlush();
            }
            if (isset($d['queryable']) && $d['queryable']) {    
                if (substr(($d['Name']), 0, $select_workspace_length) == GEOSERVER_WORKSPACE) {
                    if (in_array($d['Name'], $siteconfig['special_layers'])) continue; //skip this one

                    $layer_type = $this->GetLayerType($rasterlayersWCS, $d['Name']);
                    pg_query_params("UPDATE gislayer SET in_geoserver = true, layer_type = $2 WHERE geoserver_name = $1", array($d['Name'], $layer_type));
                    $res = pg_query_params("SELECT id FROM gislayer WHERE geoserver_name = $1", array($d['Name']));
                    if (!$res) { echo "Error in UpdateLayersFromGeoserver - selecting layer"; exit; }
                    if (!($row = pg_fetch_array($res))) { //need to add to DB
                        $res = pg_query_params("INSERT INTO gislayer (geoserver_name, layer_type) SELECT $1, $2", array($d['Name'], $layer_type));
                        if (!$res) { echo "Error in UpdateLayersFromGeoserver - adding layer"; exit; }
                        $new_layers = true;
                    }
                    $res = $this->WriteNonGeomLayerFeaturesToDB($d['Name']);
                    if (!$res) { echo "Error in UpdateLayersFromGeoserver - writing features to DB"; exit; }
                }
            }
        }
        
        pg_query_params("UPDATE gislayer SET disabled = true WHERE in_geoserver = false", array());
        if ($DEBUGGING) {
            echo date("h:i:sa") . ": UpdateLayersFromGeoserver - now synch GIS layer data to libraries<br/>";
            myFlush();
        }
        $this->SynchGISLayerDataToLibraries();
        if ($DEBUGGING) {
            echo date("h:i:sa") . ": UpdateLayersFromGeoserver - End<br/>";
            myFlush();
        }
        return $new_layers;
    }
    
    public function GetLayerFromGeoserver($name) {
        $glayers = $this->GetGeoserverLayers();
        
        $select_workspace_length=strlen(GEOSERVER_WORKSPACE);
        
        foreach ($glayers->layers as $d) {
            if (isset($d['queryable']) && $d['queryable']) {    
                if (substr(($d['Name']), 0, $select_workspace_length) == GEOSERVER_WORKSPACE) {
                    if ($d['Name'] == $name) return $d;
                }
            }
        }
        return array(); //not found        
    }

    public function GetLayersForMap($map = '', $layer_type = '') {
        $this->AddWhere('layer_type','=',$layer_type);
        $this->AddWhere('disabled','=',false);
        switch (strtolower($map)) {
            case 'albertine':
                case 'lakes':
                    case 'mountains':
                        $this->AddWhere('allow_display_' . strtolower($map), '=', true);
                        break;
        }
        $layers_arr = $this->GetRecords();
        return $layers_arr;
    }
}

/**
 * Flush output buffer
 */
function myFlush() {
    echo(str_repeat(' ', 256));
    if (@ob_get_contents()) {
        @ob_end_flush();
    }
    flush();
}

?>