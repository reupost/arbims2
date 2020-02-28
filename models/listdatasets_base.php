<?php

class DatasetsController {
    private $region;
    
    public function DatasetsController($reg) {
        $this->region = $reg;
    }
    //returns the page HTML
    public function GetDatasetList() {
        global $siteconfig;
        global $USER_SESSION;
        global $_CLEAN;
        
        /* page options */
        $params = array();
        $params['sortlistby'] = (isset($_CLEAN['sortlistby'])? $_CLEAN['sortlistby'] : 'title');
        $params['filterlistby'] = (isset($_CLEAN['filterlistby'])? $_CLEAN['filterlistby'] : '');
        $params['page'] = GetCleanInteger(isset($_CLEAN['page'])? $_CLEAN['page'] : '1');

        $params['scrollto'] = (isset($_CLEAN['scrollto'])? $_CLEAN['scrollto'] : '');

        $tbldatasets = new TableDatasets();
        $tbldatasets->SetRegion($this->region); //for setting correct occ + spp record counts
        
        if ($USER_SESSION['siterole'] != 'admin') { //only admins see all datasets
            $tbldatasets->AddWhere('region:' . $this->region, '=', true);
        }
        
        if ($params['filterlistby'] > '') $tbldatasets->AddWhere('filtercontent','***', $params['filterlistby']); //special case - no comparison operator

        $norecords = $tbldatasets->GetRecordsCount();
        $startrecord = GetPageRecordOffset($siteconfig['display_datasets_per_page'], $norecords, $params['page']);
        $result = $tbldatasets->GetRecords($params['sortlistby'], $startrecord, $siteconfig['display_datasets_per_page']);

        /* put results into pager control */
        $arrSorts = array();
        $arrSorts['title'] = getMLtext('title');
        $arrSorts['date'] = getMLText('date');
        $arrSorts['creator'] = getMLText('creator');
        $arrSorts['contact'] = getMLText('contact');
        $arrSorts['records_occ'] = getMLText('occurrence_records');
        $arrSorts['records_occ_latlon'] = getMLText('occurrence_records_georeferenced');
        if ($USER_SESSION['siterole'] == 'admin') { //only admins can sort datasets by regional usage
            $arrSorts['region:albertine'] = getMLText('include_albertine');
            $arrSorts['region:mountains'] = getMLText('include_mountains');
            $arrSorts['region:lakes'] = getMLText('include_lakes');
        }

        $arrListCols = array();
        $arrListCols['title'] = array();
        $arrListCols['title']['heading'] = getMLText('title');
        $arrListCols['title']['link'] = 'out.dataset.php'; 
        $arrListCols['title']['linkparams'] = array('datasetid' => 'datasetid', 'region' => "'" . $this->region . "'");
        $arrListCols['pubdisplaydate'] = array();
        $arrListCols['pubdisplaydate']['heading'] = getMLText('published');
        $arrListCols['_creator'] = array();
        $arrListCols['_creator']['heading'] = getMLText('creator');
        $arrListCols['_creator_org'] = array();
        $arrListCols['_creator_org']['heading'] = getMLText('creator_organisation');
        $arrListCols['_contact'] = array();
        $arrListCols['_contact']['heading'] = getMLText('contact');
        $arrListCols['_contact_org'] = array();
        $arrListCols['_contact_org']['heading'] = getMLText('contact_organisation');
        $arrListCols['occrecs'] = array();
        $arrListCols['occrecs']['heading'] = getMLText('occurrence_records');
        $arrListCols['occrecs']['link'] = 'out.listoccurrence.' . $this->region . '.php'; 
        $arrListCols['occrecs']['linkparams'] = array('datasetid' => 'datasetid');
        $arrListCols['color_box'] = array();
        $arrListCols['color_box']['heading'] = "";
        if ($USER_SESSION['siterole'] == 'admin') { //only admins see all datasets
            $arrListCols['region_albertine'] = array();
            $arrListCols['region_albertine']['heading'] = getMLText('include_albertine');
            $arrListCols['region_mountains'] = array();
            $arrListCols['region_mountains']['heading'] = getMLText('include_mountains');
            $arrListCols['region_lakes'] = array();
            $arrListCols['region_lakes']['heading'] = getMLText('include_lakes');
        }

        $pager = new Pager($norecords, $siteconfig['display_datasets_per_page'], $params['page']);
        $pageform = '';	
        $pageopts = '';

        $pager->Setentries($result);
        $pager->SetEntriesDisplay($arrListCols);
        $pager->SetEntryBoolean('region_albertine');
        $pager->SetEntryBoolean('region_mountains');
        $pager->SetEntryBoolean('region_lakes');
        $pageform = $pager->ShowControlForm(url_for('out.listdatasets.' . $this->region . '.php'), '', $params['page'], '', 'listanchor');        
        $pageopts = $pager->ShowPageOptions($params['filterlistby'], $arrSorts, $params['sortlistby']);

        $session = new SessionMsgHandler();
        $session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

        $tpl = new MasterTemplate();
        $tpl->set('site_head_title', getMLText('dataset_list')); 
        $tpl->set('page_specific_head_content', 
            "<link rel='stylesheet' type='text/css' media='screen' href='css/listdatasets.css?version=1.0' />
            <script type='text/javascript' src='js/pageload.js?version=1.0'></script>");
        $tpl->set('site_user', $USER_SESSION);
        $tpl->set('session_msg', $session_msg);
        $tpl->set('region', $this->region);

        $bdy = new MasterTemplate('templates/listdatasets.tpl.php');
        $bdy->set('region', $this->region);
        $bdy->set('params',$params);
        $bdy->set('pager',$pager);
        $bdy->set('pageform',$pageform);
        $bdy->set('pageopts',$pageopts);
        $bdy->set('user',$USER_SESSION);

        $tpl->set('sf_content', $bdy);
        
        return $tpl->fetch('templates/layoutnew.tpl.php');
    }
}
?>