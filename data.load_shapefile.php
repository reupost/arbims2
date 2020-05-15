<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");

function AddShapeOrRasterToGeoserver($file_upload, $layer_title, &$layer_type) {
    global $siteconfig;
    $path = $siteconfig['path_user_shapefiles']; // upload directory

    if (!$file_upload) {
        return array(false, getMLtext('load_map_shape_load_shp_error_invalid_file'));
    }

    $file_name = $file_upload['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext != 'zip') {
        return array(false, getMLtext("load_map_shape_load_shp_error_not_zip"));
    }

    $tmp = $file_upload['tmp_name'];
    $random_digit = rand(0000, 9999);
    $new_file_name = $random_digit . ".zip";
    mkdir($path . '/' . $random_digit, 0777, true);

    $path_full = $path . '/' . $random_digit . '/' . $new_file_name;
    if (!move_uploaded_file($tmp, $path_full)) {
        return array(false, getMLtext("load_map_shape_load_shp_error_copy"));
    }

    $zip = new ZipArchive;
    $res = $zip->open($path_full);
    if ($res !== TRUE) {
        return array(false, getMLtext("load_map_shape_load_shp_error_bad_zip"));
    }

    $zip->extractTo($path . '/' . $random_digit . '/');
    $zip->close();

    $files_shp = array();
    $files_tif = array();
    foreach (glob($path . '/' . $random_digit . '/*.shp') as $file_shp) {
        $files_shp[] = $file_shp;
    }
    if (count($files_shp) == 0) {
        foreach (glob($path . '/' . $random_digit . '/*.tif') as $file_tif) {
            $files_tif[] = $file_tif;
        }
        if (count($files_tif) == 0) {
            return array(false, getMLtext("load_map_shape_load_shp_error_no_shp_no_tif"));
        } else {
            $layer_type = 'raster';
            return AddRasterToGeoserver($path . '/' . $random_digit, $layer_title);
        }
    } else {
        $layer_type = 'vector';
        return AddLayerToGeoserver($path . '/' . $random_digit, $layer_title);
    }
}

//return array of [save result boolean, geoserver_name or failure message as needed]
function AddLayerToGeoserver($file_path, $layer_title) {
    global $siteconfig;

//$HTTP_POST_FILES

    $files_shp = array();
    foreach (glob($file_path . '/*.shp') as $file_shp) {
        $files_shp[] = $file_shp;
    }
    if (count($files_shp) == 0) {
        return array(false, getMLtext("load_map_shape_load_shp_error_no_shp"));
    }
    $files_prj = array();
    foreach (glob($file_path . '/*.prj') as $file_prj) {
        $files_prj[] = $file_prj;
    }
    if (count($files_prj) == 0) {
        return array(false, getMLtext("load_map_shape_load_shp_error_no_prj"));
    }

    $wgs84_folder = $file_path . '/wgs84';
    $layer_name = pathinfo($files_shp[0], PATHINFO_FILENAME);
//reproject to WGS 84
    $ogr_processing = "\"" . $siteconfig['path_ogr2ogr_exe'] . "\" \"" . $wgs84_folder . "\" \"" . $files_shp[0] . "\" -t_srs EPSG:4326";
    exec($ogr_processing, $output);
//check for errors by simply trying to open the new shapefile
    $files_shp_wgs84 = array();
    foreach (glob($wgs84_folder . '/*.shp') as $file_shp) {
        $files_shp_wgs84[] = $file_shp;
    }
    if (count($files_shp_wgs84) == 0) {
        return array(false, getMLtext("load_map_shape_load_shp_error_bad_prj"));
    }
//save into geoserver data dir
    $files_to_copy = scandir($wgs84_folder);
    foreach ($files_to_copy as $file) {
        if ($file[0] != '.') {
            if (!copy($wgs84_folder . '/' . $file, $siteconfig['path_layer_shapefile_dir'] . '/' . $file)) {
                echo getMLtext("load_map_shape_load_shp_error_copy") . ': ' . $wgs84_folder . '/' . $file . ' to ' . $siteconfig['path_layer_shapefile_dir'] . '/' . $file;
                exit;
                return array(false, getMLtext("load_map_shape_load_shp_error_copy") . ': ' . $wgs84_folder . '/' . $file . ' to ' . $siteconfig['path_layer_shapefile_dir'] . '/' . $file);
            }
        }
    }

//now create layer in geoserver
    $RELATIVE_URL_FOR_LAYER_CREATION = "/rest/workspaces/cite/datastores/arcos/featuretypes";
    $post_url = $siteconfig['path_geoserver'] . $RELATIVE_URL_FOR_LAYER_CREATION;
    $curl_cmd = 'curl -v -u ' . $siteconfig['geoserver_login'] . ':' . $siteconfig['geoserver_password'] . ' -XPOST -H "Content-type: text/xml" --data ' .
        '"<featureType><name>' . $layer_name . '</name><nativeName>' . $layer_name . '</nativeName><title>' . $layer_title . '</title></featureType>" "' .
        $post_url . '"';
    exec($curl_cmd, $output);
    //error checks?
    return array(true, 'cite:' . $layer_name);
}

/*
shapefile:
curl -v -u admin:geoserver -XPOST -H "Content-type: text/xml" --data "<featureType><name>type_4_dissolvetest</name><nativeName>type_4_dissolve</nativeName><title>XXX type 4</title></featureType>" "http://localhost/geoserver/rest/workspaces/cite/datastores/arcos/featuretypes"
works fine

(curl -v -u admin:geoserver -XPUT -H "Content-type: text/plain" -d "file://F:/ARBIMS/ARBIMS_data/GIS/type_4_dissolve.shp" http://localhost/geoserver/rest/workspaces/cite/datastores/arcos/external.shp
works fine)

*/

//return array of [save result boolean, geoserver_name or failure message as needed]
function AddRasterToGeoserver($file_path, $layer_title) {
    global $siteconfig;

    $files_tif = array();
    foreach (glob($file_path . '/*.tif') as $file_tif) {
        $files_tif[] = $file_tif;
        $file_name = pathinfo($file_tif, PATHINFO_FILENAME);
        if (!copy($file_tif, $siteconfig['path_layer_shapefile_dir'] . '/' . $file_name . '.tif')) {
            echo getMLtext("load_map_shape_load_tif_error_copy") . ': ' . $file_tif . ' to ' . $siteconfig['path_layer_shapefile_dir'] . '/' . $file_name . '.tif';
            exit;
            return array(false, getMLtext("load_map_shape_load_tif_error_copy") . ': ' . $file_tif . ' to ' . $siteconfig['path_layer_shapefile_dir'] . '/' . $file_name . '.tif');
        }
    }
    $layer_name = pathinfo($files_tif[0], PATHINFO_FILENAME);

//now create layer in geoserver
    //first create store
    $RELATIVE_URL_FOR_LAYER_CREATION = "/rest/workspaces/cite/coveragestores";
    $post_url = $siteconfig['path_geoserver'] . $RELATIVE_URL_FOR_LAYER_CREATION;
    $curl_cmd = 'curl -v -u ' . $siteconfig['geoserver_login'] . ':' . $siteconfig['geoserver_password'] . ' -XPOST -H "Content-type: text/xml" --data ' .
        '"<coverageStore><name>' . $layer_name . '</name><workspace>cite</workspace><enabled>true</enabled><type>GeoTIFF</type><url>file://' . $siteconfig['path_layer_shapefile_dir'] . '/' . $layer_name . '.tif</url></coverageStore>" "' .
        $post_url . '?configure=all"';

    exec($curl_cmd, $output);
    //error checks?

    //then create layer
    $curl_cmd = 'curl -v -u ' . $siteconfig['geoserver_login'] . ':' . $siteconfig['geoserver_password'] . ' -XPOST -H "Content-type: text/xml" --data ' .
        '"<coverage><name>' . $layer_name . '</name><nativeName>' . $layer_name . '</nativeName><title>' . $layer_title . '</title></coverage>" "' .
        $post_url . '/' . $layer_name . '/coverages"';
    exec($curl_cmd, $output);
    //error checks?

    return array(true, 'cite:' . $layer_name);
}

/*
raster:
create store:
curl -v -u admin:geoserver -XPOST -H "Content-type: text/xml" -d "<coverageStore><name>prec6_as_geotiff</name><workspace>cite</workspace><enabled>true</enabled><type>GeoTIFF</type><url>file://F:/ARBIMS/ARBIMS_data/GIS/prec6_as_geotiff/prec6_as_geotiff.tif</url></coverageStore>" "http://localhost/geoserver/rest/workspaces/cite/coveragestores?configure=all"
create layer:
curl -v -u admin:geoserver -XPOST -H "Content-type: text/xml" -d "<coverage><name>prec6_as_geotiff</name><nativeName>prec6_as_geotiff</nativeName><title>prec6_as_geotiff test</title></coverage>" "http://localhost/geoserver/rest/workspaces/cite/coveragestores/prec6_as_geotiff/coverages"

-amend add/edit layer to allow for this
-include processing to create postgresql table
-reproject? test with diff. projection
*/
?>