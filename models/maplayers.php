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
        "layer_is_new" => "layer_is_new",
        "disabled_layer_order" => "disabled, layer_order, displayname, geoserver_name"
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
                $jstring .= $key . ":\"" . str_replace(array("\r\n", "\r", "\n", '"'), array("<br />", "<br />", "<br />", "&quot;"), $layer[$key]) . "\"";
            }
            $jstring .= ", ml_name:\"" . str_replace(array("\r\n", "\r", "\n", '"'), array("<br />", "<br />", "<br />", "&quot;"),getMLtext($layer['displayname'])) . "\"";
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
        //attribs with spaces in them cannot be queried apparently, TODO: figure this out
        $cleanattriblist = "";        
        foreach ($attribarray as $key => $attrib) {           
            if (strtolower($attrib) == '_geom' || strtolower($attrib) == 'geom' || strtolower($attrib) == 'the_geom' || stripos($attrib,' ') !== false) continue;
            $cleanattriblist .= ($cleanattriblist > ''? ',':'') . $attrib ;
            //$cleanattriblist .= ($cleanattriblist > ''? ',':'') . str_replace(' ','%20',$attrib) ;
        }
                
        $wfs_server                     = $siteconfig['path_geoserver'] . '/wfs?';
        //$wfs_server_getlayerfeatures    = $wfs_server."SERVICE=wfs&version=1.1.0&request=GetFeature&typeName=" . $layername . "&propertyname=" . $cleanattriblist;
        $wfs_server_getlayerfeatures    = $wfs_server."SERVICE=wfs&version=1.1.0&request=GetFeature&typeName=" . $layername . "&propertyname=*" ; //this works but generates invalid XML
        //echo $wfs_server_getlayerfeatures;
        $geoserver      = fopen($wfs_server_getlayerfeatures, "r");
        $content        = stream_get_contents($geoserver);
        fclose($geoserver);
        //fix up any invalid XML with spaces or brackets or anything else XML might not like in attribute names:
        $layerws = explode(':', $layername);

        $fixed = 0;
        $aValid = array('_');

        foreach ($attribarray as $key => $attrib) {
            if(!ctype_alnum(str_replace($aValid, '', $attrib))) {
                $attrib_clean = preg_replace( '/[^a-z0-9]/i', '_', $attrib);
                $fixed = 1;
                $content = str_replace( $layerws[0] . ':' . $attrib . '>', $layerws[0] . ':' . $attrib_clean . '>', $content);
            }
        }
        
        $caps = new WFSParser();
        $caps->SetWFSParserFeatures($layername);
        $caps->parseFeatures($content);
        $caps->free_parser();
        
        return $caps->GetFeatureList(); 
    }

    public function WriteNonGeomLayerFeaturesToDB($gislayerid, &$errormsg = NULL) {
        global $siteconfig;
        global $DEBUGGING;

        if ($DEBUGGING && $errormsg === NULL) {
            echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB - " . $gislayerid . "<br/>";
            myFlush();
        }
        $res = pg_query_params("SELECT id, layer_type, geoserver_name, datafile_path, db_table_name FROM gislayer WHERE id = $1", array($gislayerid));
        if (!$res) {
            if ($errormsg !== NULL) $errormsg = "Invalid layer id: " . $gislayerid;
            return 0;
        } //sql error
        if (!($row = pg_fetch_array($res))) {
            if ($errormsg !== NULL) $errormsg = "Layer not found";
            return 0;
        } //layer not found
        
        $feats = $this->GetLayerFeaturesNonGeom($row['geoserver_name']);
        
        pg_query_params("DELETE FROM gislayer_feature WHERE gislayer_id = $1", array($gislayerid));
        foreach ($feats as $feat) {
            $concat_attribs = '';
            $descr = '';
            foreach ($feat as $attrib => $value) {
                if (strtolower($attrib) == '_geom' || strtolower($attrib) == 'geom' || strtolower($attrib) == 'the_geom' || strtolower($attrib) == 'fid' ||strtolower($attrib) == 'poslist') continue;
                if (strtolower($attrib) == 'descriptio') $descr = $value;
                $concat_attribs .= ($concat_attribs > ''? "; " : "") . $attrib . ": " . $value;                
            }
            pg_query_params("INSERT INTO gislayer_feature (fid, gislayer_id, attributes_concat, description_text) VALUES ($1, $2, $3, $4)", array($feat['fid'], $gislayerid, $concat_attribs, $descr));
        }

        if ($row['layer_type'] == 'raster') {
            if ($DEBUGGING && $errormsg === NULL) {
                echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $gislayerid . "<br/>";
                myFlush();
            }
            if ($row['datafile_path'] > '') {
                $output = array();
                if ($DEBUGGING && $errormsg === NULL) {
                    echo "\"" . $siteconfig['path_raster2pgsql_exe'] . "\" -d -s 4326 -I -C -M -t 100x100 " . "\"" . $row['datafile_path'] . "\" public." . $row['db_table_name'] . " > " . $siteconfig['path_tmp'] . "/rast.sql" . "<br/>";
                    myFlush();
                }
                $outputlastline = exec("\"" . $siteconfig['path_raster2pgsql_exe'] . "\" -d -s 4326 -I -C -M -t 100x100 " . "\"" . $row['datafile_path'] . "\" public." . $row['db_table_name'] . " > " . $siteconfig['path_tmp'] . "/rast.sql", $output);
                //TODO: check for errors
                $output = array();
                if ($DEBUGGING && $errormsg === NULL) {
                    echo "\"" . $siteconfig['path_psql_exe'] . "\" -d arbims -f " . $siteconfig['path_tmp'] . "/rast.sql -U root" . "<br/>";
                    myFlush();
                }
                $outputlastline = exec("\"" . $siteconfig['path_psql_exe'] . "\" -d arbims -f " . $siteconfig['path_tmp'] . "/rast.sql -U root", $output);
                //TODO: check for errors
            } else {
                if ($DEBUGGING && $errormsg === NULL) {
                    echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $gislayerid . " - no datafile specified!<br/>";
                    myFlush();
                }
            }
            if ($DEBUGGING && $errormsg === NULL) {
                echo date("h:i:sa") . ": WriteNonGeomLayerFeaturesToDB: processing raster - " . $gislayerid . " - finished<br/>";
                myFlush();
            }
        }
        return -1;  
    }
    
    //push revised gislayer and gislayer_feature tables to libraries
    public function SynchGISLayerDataToLibraries($just_one_layer_id = 0, &$errormsg = NULL) {
        global $siteconfig;
        global $DEBUGGING;
		// Create connection
		$conn = new mysqli($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']);

		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
        //mysql_connect($siteconfig['media_server'], $siteconfig['media_user'], $siteconfig['media_password']) OR DIE("<p><b>DATABASE ERROR: </b>Unable to connect to database server</p>");
        $first_one = '';
        foreach ($siteconfig['media_dbs'] as $theme => $dbname) {
            if ($DEBUGGING && $errormsg === NULL) {
                echo date("h:i:sa") . ": SynchGISLayerDataToLibraries - " . $dbname . "<br/>";
                myFlush();
            }
            if ($first_one == '') {
                $first_one = $dbname;
                @mysqli_select_db($conn, $dbname) or die("<p><b>DATABASE ERROR: </b>Unable to open database $dbname</p>");

                if ($just_one_layer_id != 0) {
                    mysqli_query($conn, "DELETE FROM tblgislayer WHERE id = $1", array($just_one_layer_id));
                    $from = pg_query_params("SELECT * FROM gislayer WHERE disabled = false AND allow_identify = true AND id = $1", array($just_one_layer_id));
                } else {
                    mysqli_query($conn, "DELETE FROM tblgislayer");
                    $from = pg_query_params("SELECT * FROM gislayer WHERE disabled = false AND allow_identify = true", array());
                }
                while ($fromrow = pg_fetch_array($from)) {
                    $sql = "INSERT INTO tblgislayer (id, layer_order, displayname, geoserver_name, ";
                    $sql .= "allow_display_albertine, allow_display_mountains, allow_display_lakes, disabled) ";
                    $sql .= "VALUES (";
                    $sql .= $fromrow['id'] . ",";
                    $sql .= $fromrow['layer_order'] . ",";
                    $sql .= "'" . mysqli_real_escape_string($conn, $fromrow['displayname']) . "',";
                    $sql .= "'" . mysqli_real_escape_string($conn, $fromrow['geoserver_name']) . "',";
                    $sql .= ($fromrow['allow_display_albertine'] == 't' ? 'true' : 'false') . ",";
                    $sql .= ($fromrow['allow_display_mountains'] == 't' ? 'true' : 'false') . ",";
                    $sql .= ($fromrow['allow_display_lakes'] == 't' ? 'true' : 'false') . ",";
                    $sql .= ($fromrow['disabled'] == 't' ? 'true' : 'false') . ")";
                    //echo $sql;
                    $res = mysqli_query($conn, $sql); //TODO: error-checks
                }
                if ($just_one_layer_id != 0) {
                    mysqli_query($conn, "DELETE FROM tblgislayer_feature WHERE gislayer_id = $1", array($just_one_layer_id));
                } else {
                    mysqli_query($conn, "DELETE FROM tblgislayer_feature");
                }
                $from = pg_query_params("SELECT * FROM gislayer_feature", array());
                while ($fromrow = pg_fetch_array($from)) {
                    $sql = "INSERT INTO tblgislayer_feature (fid, gislayer_id, attributes_concat, description_text) ";
                    $sql .= "VALUES (";
                    $sql .= "'" . $fromrow['fid'] . "',";
                    $sql .= $fromrow['gislayer_id'] . ",";
                    $sql .= "'" . mysqli_real_escape_string($conn, $fromrow['attributes_concat']) . "',";
                    $sql .= "'" . mysqli_real_escape_string($conn, $fromrow['description_text']) . "')";
                    //echo $sql;
                    $res = mysqli_query($conn, $sql); //TODO: error-checks
                }
            } else {
                //simply copy from first db
                mysqli_query($conn, "DELETE FROM " . $dbname . ".tblgislayer");
                mysqli_query($conn, "DELETE FROM " . $dbname . ".tblgislayer_feature");
                mysqli_query($conn, "INSERT INTO " . $dbname . ".tblgislayer SELECT * from " . $first_one . ".tblgislayer");
                mysqli_query($conn, "INSERT INTO " . $dbname . ".tblgislayer_feature SELECT * from " . $first_one . ".tblgislayer_feature");
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

    public function GetAvailableStyles() {
        global $siteconfig;
        $wms_server                 = $siteconfig['path_geoserver'];
        $auth = $siteconfig['geoserver_login'] . ':' . $siteconfig['geoserver_password'];
        $curl_url = $wms_server . '/rest/styles.json';
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,$curl_url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'ARBIMS');
        curl_setopt($curl_handle, CURLOPT_USERPWD, $auth);
        curl_setopt($curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $styles_json = curl_exec($curl_handle);
        curl_close($curl_handle);
        $style_array = array();
        $styles = json_decode($styles_json);
        if (!is_null($styles) && !empty($styles)) {
            $max = sizeof($styles->styles->style);
            for ($i = 0; $i < $max; $i++) {
                $style_array_entry = array();
                $style_array_entry['name'] = $styles->styles->style[$i]->name;
                $style_array_entry['url'] = $styles->styles->style[$i]->href;
                $style_array[] = $style_array_entry;
            }
        }
        return $style_array;
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
                        $res2 = pg_query_params("INSERT INTO gislayer (geoserver_name, layer_type) SELECT $1, $2", array($d['Name'], $layer_type));
                        if (!$res2) { echo "Error in UpdateLayersFromGeoserver - adding layer"; exit; }
                        $new_layers = true;
                    }
                    if ($layer_type != 'raster') {
                        $res = pg_query_params("SELECT id FROM gislayer WHERE geoserver_name = $1", array($d['Name']));
                        if (!$res) {
                            echo "Error in UpdateLayersFromGeoserver - selecting layer";
                            exit;
                        }
                        while ($row = pg_fetch_array($res)) {
                            $res2 = $this->WriteNonGeomLayerFeaturesToDB($row['id']);
                            if (!$res2) {
                                echo "Error in UpdateLayersFromGeoserver - writing features to DB";
                                exit;
                            }
                        }
                    }
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