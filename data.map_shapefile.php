<?php
require_once("includes/config.php");
require_once("includes/inc.language.php");

$valid_extensions = array('zip'); // valid extensions
$path = $siteconfig['path_user_shapefiles']; // upload directory

if (!$_FILES['map-load-area-shp']) {
    echo "Error: no valid file uploaded";
    exit;
}
$file_name = $_FILES['map-load-area-shp']['name'];
$ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
if ($ext != 'zip') {
    // printMLtext('dataset_occurrence_key')
    echo "Error: shapefile must be uploaded as .zip file";
    exit;
}

//$HTTP_POST_FILES
$tmp = $_FILES['map-load-area-shp']['tmp_name'];
$random_digit = rand(0000, 9999);
$new_file_name = $random_digit . ".zip";
mkdir($path . '/' . $random_digit, 0777, true);

$path_full = $path . '/' . $random_digit . '/' . $new_file_name;

if (!move_uploaded_file($tmp, $path_full)) {
    echo "Error: could not copy file";
    exit;
}

$zip = new ZipArchive;
$res = $zip->open($path_full);
if ($res !== TRUE) {
    echo 'Error: ZIP file extraction error';
    exit;
}

$zip->extractTo($path . '/' . $random_digit . '/');
$zip->close();
$files_shp = array();
foreach (glob($path . '/' . $random_digit . '/*.shp') as $file_shp) {
    $files_shp[] = $file_shp;
}
if (count($files_shp) == 0) {
    echo "Error: no .shp file found";
    exit;
}
$files_prj = array();
foreach (glob($path . '/' . $random_digit . '/*.prj') as $file_prj) {
    $files_prj[] = $file_prj;
}
if (count($files_prj) == 0) {
    echo "Error: no .prj file found";
    exit;
}


$wgs84_folder = $path . '/' . $random_digit . '/wgs84';
$wkt_folder = $path . '/' . $random_digit . '/wkt';

//reproject to WGS 84
$ogr_processing = "\"" . $siteconfig['path_ogr2ogr_exe'] . "\" \"" . $wgs84_folder . "\" \"" . $files_shp[0] . "\" -t_srs EPSG:4326";
exec($ogr_processing, $output);
//check for errors by simply trying to open the new shapefile
$files_shp_wgs84 = array();
foreach (glob($wgs84_folder . '/*.shp') as $file_shp) {
    $files_shp_wgs84[] = $file_shp;
}
if (count($files_shp_wgs84) == 0) {
    echo "Error: could not reproject shapefile to WGS84";
    exit;
}
//save as WKT
$ogr_processing = "\"" . $siteconfig['path_ogr2ogr_exe'] . "\"  -f CSV \"" . $wkt_folder . "\" \"" . $files_shp_wgs84[0] . "\" -lco GEOMETRY=AS_WKT";
exec($ogr_processing, $output);
//errors will result in no or badly formed WKT file below
$output_csv = $wkt_folder . '/' . basename($files_shp[0], '.shp') . '.csv';

$row = 1;
if (($handle = fopen($output_csv, "r")) === FALSE) {
    echo 'Error: failed to convert shapefile to WKT';
    exit;
}
while ((($data = fgetcsv($handle, 0, ",")) !== FALSE) && ($row < 3)) { //only interested in first real row (i.e. line 2)
    if ($row == 2) {
        $num = count($data);
        if ($num != 3) {
            echo "Error: invalid WKT (not a polygon)";
            exit;
        }
        echo $data[0]; //WKT is first column
        fclose($handle);
        exit;
        //success
    }
    $row++;
}
fclose($handle);
?>