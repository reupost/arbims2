<?php
$siteconfig = array();
 global $siteconfig;
 global $USER_SESSION;

///////////////////////////////////////////////////
// SITE INFORMATION
$siteconfig['site_version'] = '0.2';
$siteconfig['site_year'] = '2019';
$siteconfig['site_title'] = 'ARBIMS';
$siteconfig['site_descr'] = 'ARCOS Biodiversity Information Management System';
$siteconfig['site_keywords'] = "environmental, impact, assessment, gbif, checklist, rwanda, national, biodiversity, arcos";     
 
$siteconfig['copyright_org'] = 'Reuben Roberts';
$siteconfig['copyright_web'] = 'http://reubenroberts.co.za';
$siteconfig['copyright_link'] = "(c) <a href='" . $siteconfig['copyright_web'] . "' target='_top'>" . $siteconfig['copyright_org'] . "</a> " . $siteconfig['site_year'];  
  
$siteconfig['admin_name'] = 'Reuben Roberts';
$siteconfig['admin_email'] = 'reupost@gmail.com'; 

// EMAIL INFORMATION
$siteconfig['email_from'] = "From: ".$siteconfig['admin_name']." " . $siteconfig['admin_email'] ."\r\n";
$siteconfig['email_from_team'] = "ARBIMS Team";
$siteconfig['email_reply_to'] = "Reply-To: arbims@arcos.org.rw\r\n";
$siteconfig['email_html_enc'] = "Content-type: text/html; charset=iso-8859-1\r\n";	
$siteconfig['email_return_to'] = "Return-Path: arbims@arcos.org.rw\r\n";
	
$siteconfig['display_date_format'] = "d-m-Y";
$siteconfig['display_users_per_page'] = 25;
$siteconfig['display_datasets_per_page'] = 25;
$siteconfig['display_occurrence_per_page'] = 100;
$siteconfig['display_species_per_page'] = 50;
$siteconfig['display_gislayers_per_page'] = 25;

				
//site paths
$siteconfig['path_basefolder'] = 'F:/ARBIMS/web';
$siteconfig['path_baseurl'] = 'http://localhost';
$siteconfig['path_templates'] = $siteconfig['path_baseurl'] . '/templates';
$siteconfig['path_lib'] = $siteconfig['path_baseurl'] . '/lib';
$siteconfig['path_ipt'] =  'http://arbims.arcosnetwork.org/ipt'; // $siteconfig['path_baseurl'] . '/ipt';
$siteconfig['path_geoserver'] = $siteconfig['path_baseurl'] . '/geoserver'; //cite
$siteconfig['path_controllers'] = $siteconfig['path_baseurl'] . '/controllers';
	
$siteconfig['path_java_exe'] = 'C:\Program Files\Java\jdk1.8.0_231\jre\bin\java.exe';
$siteconfig['path_psql_exe'] = 'F:\_Programs\PostGreSQL\pg10\bin\psql.exe'; //since Apache seems not to always have access to the PATH env. variable

$siteconfig['path_datasets_files'] = 'uploads/datasets/files';
$siteconfig['path_datasets'] = 'uploads/datasets';
$siteconfig['path_datasets_spp'] = 'uploads/datasets/spp';
$siteconfig['path_user_images'] = 'uploads/users';
$siteconfig['path_tmp'] = $siteconfig['path_basefolder'] . '/tmp';
$siteconfig['url_tmp'] = $siteconfig['path_baseurl'] . '/tmp';
$siteconfig['url_img'] = $siteconfig['path_baseurl'] . '/images';

$siteconfig['mail_log'] = 'F:/ARBIMS/ARBMIS_logs/bulletin.log';
	
$siteconfig['taxonranks'] = array('*root*', 'kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'species');

//image dimensions
$siteconfig['max_imgfile_x'] = 300; //pixels width
$siteconfig['max_imgfile_y'] = 300; //pixels height

$siteconfig['max_occ_to_map'] = 1000; //maximum no. of (filtered) occurrence records to put on map
$siteconfig['max_occ_to_download'] = 10000; //max records to download
$siteconfig['max_spp_to_download'] = 10000; //max spp records to download
	
//CAPTCHA
$siteconfig['recaptcha_private_key'] = '6Le5m_ISAAAAAK_nGi2_AMJSu9ODwIPn34tiR5WJ';
$siteconfig['recaptcha_public_key'] = '6Le5m_ISAAAAAIRTE-_X1iVnB0E51hyJ55pF4iAw '; //for *.arcosnetwork.org

$siteconfig['media_server'] = 'localhost';
$siteconfig['media_user'] = 'root';
$siteconfig['media_password'] = 'root';
$siteconfig['media_dbs'] = array("land" => "library_land", "lake" => "library_lake", "mnt" => "library_mnt", "eia" => "library_eia");

$siteconfig['dwc_db'] = 'arbims'; //for main database: postgreSQL
$siteconfig['dwc_server'] = 'localhost';
$siteconfig['dwc_user'] = 'postgres';
$siteconfig['limbo_user'] = 'limbouser'; //limited user with rights to limbo schema in db
$siteconfig['dwc_password'] = '';
$siteconfig['dwc_port'] = '5432'; 
$siteconfig['schema_dwc'] = 'public'; 
$siteconfig['schema_limbo'] = 'limbo'; //used for importing raw SQL from DwCA's
$siteconfig['special_layers'] = array('cite:occurrence','cite:occurrence_overview',
                    'cite:occurrence_albertine','cite:occurrence_overview_albertine',
                    'cite:occurrence_mountains','cite:occurrence_overview_mountains',
                    'cite:occurrence_lakes','cite:occurrence_overview_lakes'); //special GIS layers, not user-configurable

$siteconfig['dwc_db_conn'] = pg_connect("host=" . $siteconfig['dwc_server'] . " port=" . $siteconfig['dwc_port'] . " dbname=" . $siteconfig['dwc_db'] . " user=" . $siteconfig['dwc_user']);

/* session */
session_start();
if (!isset($_SESSION['USER_SESSION'])) {
    $USER_SESSION = array();    
    $USER_SESSION['username'] = ''; //not logged in
    $USER_SESSION['id'] = 0;
    $USER_SESSION['siterole'] = 'guest';
    $USER_SESSION['email'] = '';
    //session_register("USER_SESSION"); deprecated
    $_SESSION["USER_SESSION"] = $USER_SESSION;
} else {   
   $USER_SESSION = $_SESSION['USER_SESSION'];   
}
$USER_SESSION['language'] = (isset($_COOKIE['arbmis_lang'])? $_COOKIE['arbmis_lang'] : 'en_GB');
if ($USER_SESSION['language'] != 'en_GB' && $USER_SESSION['language'] != 'fr_FR') $USER_SESSION['language'] = 'en_GB'; //in case of bad cookie

require_once($siteconfig['path_basefolder'] . "/includes/tools.php");

$_CLEAN = Sanitize($_GET);
?>