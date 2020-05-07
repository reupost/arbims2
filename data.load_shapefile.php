<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");

//return array of [save result boolean, geoserver_name or failure message as needed]
function AddLayerToGeoserver($file_upload, $layer_title) {
    global $siteconfig;
    $valid_extensions = array('zip'); // valid extensions
    $path = $siteconfig['path_user_shapefiles']; // upload directory

    if (!$file_upload) {
        return array(false, getMLtext('load_map_shape_load_shp_error_invalid_file'));
    }
    $file_name = $file_upload['name'];
    $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($ext != 'zip') {
        return array(false, getMLtext("load_map_shape_load_shp_error_not_zip"));
    }

//$HTTP_POST_FILES
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
    foreach (glob($path . '/' . $random_digit . '/*.shp') as $file_shp) {
        $files_shp[] = $file_shp;

    }
    if (count($files_shp) == 0) {
        return array(false, getMLtext("load_map_shape_load_shp_error_no_shp"));
    }
    $files_prj = array();
    foreach (glob($path . '/' . $random_digit . '/*.prj') as $file_prj) {
        $files_prj[] = $file_prj;
    }
    if (count($files_prj) == 0) {
        return array(false, getMLtext("load_map_shape_load_shp_error_no_prj"));
    }

    $wgs84_folder = $path . '/' . $random_digit . '/wgs84';
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
    $curl_cmd = 'curl -v -u ' . $siteconfig['geoserver_login'] . ':' . $siteconfig['geoserver_password'] . ' -XPOST -H "Content-type: text/xml" --data "<featureType><name>' . $layer_name . '</name><nativeName>' . $layer_name . '</nativeName><title>' . $layer_title . '</title></featureType>" "' . $post_url . '"';
    //echo $curl_cmd; exit;
    exec($curl_cmd, $output);
    //error checks?
    return array(true, 'cite:' . $layer_name);
}

/*
curl -v -u admin:geoserver -XPOST -H "Content-type: text/xml" --data "<featureType><name>type_4_dissolvetest</name><nativeName>type_4_dissolve</nativeName><title>XXX type 4</title></featureType>" "http://localhost/geoserver/rest/workspaces/cite/datastores/arcos/featuretypes"
works fine
curl -v -u admin:geoserver -XPUT -H "Content-type: text/plain" -d "file://F:/ARBIMS/ARBIMS_data/GIS/type_4_dissolve.shp" http://localhost/geoserver/rest/workspaces/cite/datastores/arcos/external.shp
works fine
*/
?>