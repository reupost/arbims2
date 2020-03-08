<?php
require_once("includes/config.php");
require_once("includes/tools.php");
require_once("includes/template.php");
require_once("includes/pager.php");
require_once("models/singledataset.php");
require_once("includes/inc.language.php");

global $siteconfig;
global $USER_SESSION;

$params = array();
$params['datasetkey'] = (isset($_CLEAN['datasetkey'])? $_CLEAN['datasetkey'] : '');
$params['region'] = (isset($_CLEAN['region'])? $_CLEAN['region'] : '');

if (!in_array($params['region'], array("albertine","mountains","lakes"))) $params['region'] = "";
    
$ds = new SingleDataset($params['datasetkey']);
$dsdata = $ds->GetAttributes();
if (count($dsdata) == 0) { //invalid id
    if ($params['region'] > "") {
        header("Location: out.listdatasets." . $params['region'] . ".php"); 
    } else {
        header("Location: out.index.php");
    }
    exit;
}
$dslist = $ds->GetViewableAttributes();

/* page template main */
$tpl = new MasterTemplate();
$tpl->set('site_head_title', getMLText('dataset')); 
$tpl->set('page_specific_head_content', 
	"<link rel='stylesheet' type='text/css' media='screen' href='css/dataset.css' />");
$tpl->set('site_user', $USER_SESSION);
$tpl->set('region',$params['region']);

/* page template body - pass page options to this as well */
$bdy = new MasterTemplate('templates/dataset.tpl.php');
$bdy->set('region',$params['region']);
$bdy->set('dsdata',$dsdata);
$bdy->set('dslist',$dslist);
$bdy->set('user',$USER_SESSION);

/* link everything together */
$tpl->set('sf_content', $bdy);

/* page display */
echo $tpl->fetch('templates/layoutnew.tpl.php');
?>