<?php

class MapController {
    private $region;
    
    public function MapController($reg) {
        $this->region = $reg;
    }
    //returns the page HTML
    public function GetMap() {
        global $siteconfig;
        global $USER_SESSION;
        global $_CLEAN;
        
        /* page options */
        $params = array();
        $params['filterlistby'] = (isset($_CLEAN['filterlistby']) ? $_CLEAN['filterlistby'] : '');
        $params['taxon'] = (isset($_CLEAN['taxon']) ? $_CLEAN['taxon'] : '');
        $params['rank'] = (isset($_CLEAN['rank']) ? $_CLEAN['rank'] : '');
        $params['taxonparent'] = (isset($_CLEAN['taxonparent']) ? $_CLEAN['taxonparent'] : '');
        $params['datasetid'] = (isset($_CLEAN['datasetid']) ? $_CLEAN['datasetid'] : '');
        $params['mapocc'] = (isset($_CLEAN['mapocc']) ? $_CLEAN['mapocc'] : '');
        foreach (array('x1','x2','y1','y2') as $numeric_param) {
            $params[$numeric_param] = (isset($_CLEAN[$numeric_param])? $_CLEAN[$numeric_param] : '');
        }
        foreach (array('x1','x2','y1','y2') as $numeric_param) {
            if (!is_numeric($params[$numeric_param]) && $params[$numeric_param]>'') $params[$numeric_param] = $params[$numeric_param] . ' [' . htmlspecialchars(getMLtext('invalid')) . ']';
        }

        /* get model for page content */
        $tbloccurrence = new TableOccurrence();
        $tbldataset = new TableDatasets();
        $maplayers = new MapLayers();

        $showing_some_occs = false;

        //advanced criteria
        //put in $setcriteria so will be saved
        $setcriteria = array();
        $adv_criteria = "";
        $searchfields = $tbloccurrence->GetSearchFields();
        foreach ($searchfields as $field) {
            if (isset($_CLEAN[$field])) {
                if ($_CLEAN[$field] != '') {
                    $showing_some_occs = true;
                    $raw_data = $_GET[$field]; //use GET since AddWhere protects against SQLinject
                    if ($raw_data == '(blank)') $raw_data = '';
                    $tbloccurrence->AddWhere($field, "=", $raw_data); 
                    $setcriteria[$field] = $raw_data; //html_entity_decode($_CLEAN[$field]);
                    $adv_criteria .= ($adv_criteria > ''? "<br/>" : "");
                    $adv_criteria .= getMLtext('occ_' . $field) . " = " . ($_CLEAN[$field] == '(blank)'? "(" . getMLtext('blank') . ")" : $_CLEAN[$field]);
                }
            }
        }
        
        //For Search Result occurrence records in KML overlay
        $tbloccurrence->AddWhere('region:' . $this->region, '=', true);
        
        if ($params['filterlistby'] > '') {
            $showing_some_occs = true;
            $tbloccurrence->AddWhere('filtercontent', '***', $params['filterlistby']);
        }
        if ($params['datasetid'] > '') {
            $showing_some_occs = true;
            $tbloccurrence->AddWhere('datasetid', '=', $params['datasetid']);
            $params['dataset_title'] = getMLtext('dataset') . ': ' . $tbldataset->GetDatasetTitle($params['datasetid']);
        }
        if ($params['taxon'] > '' && $params['rank'] > '' && $params['taxonparent'] > '') { //all must be present to filter like this
            $showing_some_occs = true;
            $tbloccurrence->AddWhere('taxon', '=', array($params['taxon'], $params['rank'], $params['taxonparent']));
            if ($params['rank'] == 'species') {
                $params['taxon_title'] = getMLtext('taxon_species') . ': <i>' . $params['taxonparent'] . ' ' . $params['taxon'] . '</i>';
            } else {
                $rankpos = array_search($params['rank'], $siteconfig['taxonranks']);
                $params['taxon_title'] = getMLtext('taxon_' . $params['rank']) . ': ' . $params['taxon'];
                if ($rankpos > 1)
                    $params['taxon_title'] .= ' (' . getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos - 1]) . ' ' . $params['taxonparent'] . ')';
            }
        }
        if ($params['mapocc'] > '') {
            $showing_some_occs = true; //even if no other criteria
        }

        $params['bounding_box'] = '';
        if ($params['x1'] > '' && $params['x2'] > '') {
            //normal case.  Where only one limit is specified it is handled in the individual cases below
            $params['bounding_box'] .= ', ' . getMLtext('longitude') . ': ' . $params['x1'] . ' - ' . $params['x2'];
        }
        if ($params['x1'] > '') {
            if (is_numeric($params['x1'])) $tbloccurrence->AddWhere('longitude', '>=', $params['x1']);
            if ($params['x2'] == '') $params['bounding_box'] .= ', ' . getMLtext('longitude') . ' > ' . $params['x1'];
        }
        if ($params['x2'] > '') {
            if (is_numeric($params['x2'])) $tbloccurrence->AddWhere('longitude', '<=', $params['x2']);
            if ($params['x1'] == '') $params['bounding_box'] .= ', ' . getMLtext('longitude') . ' < ' . $params['x2'];
        }
        if ($params['y1'] > '' && $params['y2'] > '') {
            //normal case.  Where only one limit is specified it is handled in the individual cases below
            $params['bounding_box'] .= ', ' . getMLtext('latitude') . ': ' . $params['y1'] . ' - ' . $params['y2'];
        }
        if ($params['y1'] > '') {
            if (is_numeric($params['y1'])) $tbloccurrence->AddWhere('latitude', '>=', $params['y1']);
            if ($params['y2'] == '') $params['bounding_box'] .= ', ' . getMLtext('latitude') . ' > ' . $params['y1'];
        }
        if ($params['y2'] > '') {
            if (is_numeric($params['y2'])) $tbloccurrence->AddWhere('latitude', '<=', $params['y2']);
            if ($params['y2'] == '') $params['bounding_box'] .= ', ' . getMLtext('latitude') . ' < ' . $params['y2'];
        }
        if ($params['bounding_box'] > '') {
            $showing_some_occs = true;
            $params['bounding_box'] = substr($params['bounding_box'], strlen(', '));
        }

        $params['base_url'] = $siteconfig['path_baseurl'];
        $params['geoserver'] = $siteconfig['path_geoserver'];
        $params['geoserver_wms'] = $siteconfig['path_geoserver'] . '/wms';
        $params['geoserver_wfs'] = $siteconfig['path_geoserver'] . '/wfs';

        $norecords = 0;
        if ($showing_some_occs) $norecords = $tbloccurrence->GetRecordsCount();

        if ($norecords > $siteconfig['max_occ_to_map']) $params['bounding_box'] .= ' (' . htmlspecialchars(getMLtext('too_many_to_display')) . ')';
        if ($norecords > 0 && $norecords <= $siteconfig['max_occ_to_map']) {
            $filename = uniqid() . '.kml';
            $filepath = $siteconfig['path_tmp'] . '/' . $filename;
            DeleteFilesOlderThan($siteconfig['path_tmp'], 60*60*24*2); //some housekeeping: delete temp files older than 2 days    
            $KMLfile = $tbloccurrence->GetOccurrencesKML($filepath); //writes KML file to filepath
            $KMLurl = $siteconfig['url_tmp'] . '/' . $filename;
            $params['occ_kml'] = $KMLurl;
            $params['occ_kml_icon'] = $siteconfig['url_img'] .'/icon57.png';
            $params['occ_kml_icon_sel'] = $siteconfig['url_img'] .'/icon49.png';
        }

        $maplayers->AddWhere("disabled", "=", false); 
        $maplayers->AddWhere("allow_display_" . $this->region, "=", true);         
        
        $params['js_layer_objs'] = $maplayers->GetJavascriptLayerArray();
        $params['js_layer_init'] = $maplayers->GetJavascriptLayerInit();
        $params['js_layer_list'] = $maplayers->GetJavascriptLayerList();
        $params['js_layer_list_identify'] = $maplayers->GetJavascriptLayerList(true);

        $occ_legend = $tbldataset->GetOccurrenceLegend(null, $this->region);

        /* page template main */
        $tpl = new MasterTemplate();
        $tpl->set('site_head_title', getMLText('map'));
        $tpl->set('page_specific_head_content', 
           "<link rel='stylesheet' type='text/css' media='screen' href='css/map.css?random=" . filemtime($_SERVER["DOCUMENT_ROOT"].'/css/map.css') . "' />
            <script type='text/javascript' src='js/map.js?random=" . filemtime($_SERVER["DOCUMENT_ROOT"].'/css/map.css') . "'></script>
            <script type='text/javascript' src='js/jquery-ui/jquery-ui.min.js'></script>
            <link rel='stylesheet' type='text/css' media='screen' href='js/jquery-ui/jquery-ui.css' />
            <script type='text/javascript' src='lib/openlayers/OpenLayers.js'></script>
            <script type='text/javascript' src='https://maps.googleapis.com/maps/api/js?key=" . $siteconfig['google_maps_api_key'] . "'></script>");
        $tpl->set('site_user', $USER_SESSION);
        $tpl->set('region', $this->region);

        /* page template body - pass page options to this as well */
        $bdy = new MasterTemplate('templates/map.tpl.php');
        $bdy->set('region', $this->region);
        $bdy->set('params', $params);
        $bdy->set('occ_legend', $occ_legend);
        $bdy->set('adv_criteria', $adv_criteria);
        $bdy->set('display_occurrence_selected', $siteconfig['display_occurrence_selected']);
        $bdy->set('map_occurrence_selected', $siteconfig['map_occurrence_selected']);

        /* link everything together */
        $tpl->set('sf_content', $bdy);
        
        return $tpl->fetch('templates/layoutnew.tpl.php');
    }
}

?>