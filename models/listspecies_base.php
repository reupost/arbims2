<?php

class SpeciesController {
    private $region;
    
    public function SpeciesController($reg) {
        $this->region = $reg;
    }
    //returns the page HTML
    public function GetSpeciesList() {
        global $siteconfig;
        global $USER_SESSION;
        global $_CLEAN;

        /* page options */
        $params = array();
        $params['sortlistby'] = (isset($_CLEAN['sortlistby']) ? $_CLEAN['sortlistby'] : 'dataset_title');
        $params['filterlistby'] = (isset($_CLEAN['filterlistby']) ? $_CLEAN['filterlistby'] : '');
        $params['taxon'] = (isset($_CLEAN['taxon']) ? $_CLEAN['taxon'] : '');
        $params['rank'] = (isset($_CLEAN['rank']) ? $_CLEAN['rank'] : '');
        $params['taxonparent'] = (isset($_CLEAN['taxonparent']) ? $_CLEAN['taxonparent'] : '');

        //CONSIDER:
        //there is an issue where the number of species displayed on the species tree is an estimate of distinct genus+species
        //whereas what will be listed here will include all taxa (potentially from multiple datasets), so there may be apparently
        //duplication of names (either different datasets or different higher taxonomy).  There is no 'distinct' aggregation on
        ///this listing.
        $params['datasetid'] = (isset($_CLEAN['datasetid']) ? $_CLEAN['datasetid'] : '');
        $params['x_datasetid'] = (isset($_CLEAN['x_datasetid']) ? $_CLEAN['x_datasetid'] : '');

        $params['page'] = GetCleanInteger(isset($_CLEAN['page']) ? $_CLEAN['page'] : '1');
        $params['scrollto'] = (isset($_CLEAN['scrollto']) ? $_CLEAN['scrollto'] : '');

        //download button posts back to the same page (using the previously set criteria, so if they have entered a new filter 
        //and then click 'download this list' the new filter won't be magically applied to the download)
        $params['download'] = GetCleanInteger(isset($_CLEAN['download']) ? $_CLEAN['download'] : 0);

        $session = new SessionMsgHandler();
        $session_msg = $session->GetSessionMsgMerged($USER_SESSION['id'], "message", true);

        /* get model for page content */
        $tbldata = new TableSpecies();
        $tbldataset = new TableDatasets();
        
        $tbldata->AddWhere('region:' . $this->region, '=', true);

        if ($params['filterlistby'] > '')
            $tbldata->AddWhere('filtercontent', '***', $params['filterlistby']);
        if ($params['datasetid'] > '')
            $tbldata->AddWhere('datasetid', '=', $params['datasetid']);
        if ($params['x_datasetid'] > '')
            $tbldata->AddWhere('datasetid', '<>', $params['x_datasetid']); //note this refers to datasetid - the 'x_' prefix is a hack to allow the usual URL param x=y variable assignment since there is no x<>y pattern
        //if ($params['taxon'] > '' && $params['rank'] > '' && $params['taxonparent'] > '') { //all must be present to filter like this
        if ($params['rank'] > '') { //all must be present to filter like this
            $tbldata->AddWhere('taxon', '=', array($params['taxon'], $params['rank'], $params['taxonparent']));
        }

        if ($params['download']) {
            if (!$USER_SESSION['id']) {
                //not logged in
                $session_msg["msg"] .= getMLtext('logged_in_users_only');
                $session_msg["state"] = "error";
            } else {
                $download_ok = $tbldata->Download();
                if (!$download_ok) {
                    $session_msg["msg"] .= getMLtext('download_error');
                    $session_msg["state"] = "error";
                } else {
                    exit();
                }
            }
        }

        $norecords = $tbldata->GetRecordsCount();
        $startrecord = GetPageRecordOffset($siteconfig['display_species_per_page'], $norecords, $params['page']);
        $result = $tbldata->GetRecords($params['sortlistby'], $startrecord, $siteconfig['display_species_per_page']);

        /* put results into pager control */
        $arrSorts = array();
        $arrSorts['dataset'] = getMLtext('dataset');
        $arrSorts['scientificname'] = getMLText('scientific_name');
        $arrSorts['fulltaxonomy'] = getMLText('full_taxonomy');
        $arrSorts['vernacularname'] = getMLText('vernacular_name');

        $arrListCols = array();
        $arrListCols['dataset_title'] = array();
        $arrListCols['dataset_title']['heading'] = getMLText('dataset');
        $arrListCols['dataset_title']['link'] = 'out.dataset.php'; 
        $arrListCols['dataset_title']['linkparams'] = array('datasetid' => 'datasetid', 'region' => "'" . $this->region . "'");        
        $arrListCols['highertaxonomy'] = array();
        $arrListCols['highertaxonomy']['heading'] = getMLText('higher_taxonomy');
        $arrListCols['highertaxonomy']['link'] = '';
        /* $arrListCols['phylum'] = array();
        $arrListCols['phylum']['heading'] = getMLText('taxon_phylum');
        $arrListCols['phylum']['link'] = '';
        $arrListCols['class'] = array();
        $arrListCols['class']['heading'] = getMLText('taxon_class');
        $arrListCols['class']['link'] = '';
        $arrListCols['order'] = array();
        $arrListCols['order']['heading'] = getMLText('taxon_order');
        $arrListCols['order']['link'] = ''; */
        $arrListCols['family'] = array();
        $arrListCols['family']['heading'] = getMLText('taxon_family');
        $arrListCols['family']['link'] = '';
        /*$arrListCols['genus'] = array();
        $arrListCols['genus']['heading'] = getMLText('taxon_genus');
        $arrListCols['genus']['link'] = '';
        $arrListCols['species'] = array();
        $arrListCols['species']['heading'] = getMLText('taxon_species');
        $arrListCols['species']['link'] = ''; 
        $arrListCols['synonym_of'] = array();
        $arrListCols['synonym_of']['heading'] = getMLText('taxon_synonym_of');
        $arrListCols['synonym_of']['link'] = ''; */
        $arrListCols['displayname'] = array();
        $arrListCols['displayname']['heading'] = getMLText('scientific_name');
        $arrListCols['displayname']['link'] = ''; 
        $arrListCols['vernacularname'] = array();
        $arrListCols['vernacularname']['heading'] = getMLText('vernacular_name');
        $arrListCols['vernacularname']['link'] = '';
        $arrListCols['taxonremarks'] = array();
        $arrListCols['taxonremarks']['heading'] = getMLText('taxon_remarks');
        $arrListCols['taxonremarks']['link'] = '';
        $arrListCols['numsppoccs_' . $this->region] = array();
        $arrListCols['numsppoccs_' . $this->region]['heading'] = getMLText('species_occurrence_records');
        $arrListCols['numsppoccs_' . $this->region]['link'] = 'out.listoccurrence.' . $this->region . '.php';
        $arrListCols['numsppoccs_' . $this->region]['linkparams'] = array('taxon' => 'species', 'rank' => "'species'", 'taxonparent' => 'genus');

        $pager = new Pager($norecords, $siteconfig['display_species_per_page'], $params['page']);
        $pageform = '';
        $pageopts = '';

        $pager->Setentries($result);
        $pager->SetEntriesDisplay($arrListCols);
        /* $pager->SetEntryShowHide('synonym_of',true); //for collapsible column */

        $setcriteria = array();
        if ($params['taxon'] > '' && $params['rank'] > '' && $params['taxonparent'] > '') {
            $setcriteria['taxon'] = $params['taxon'];
            $setcriteria['rank'] = $params['rank'];
            $setcriteria['taxonparent'] = $params['taxonparent'];
            if ($params['rank'] == 'species') {
                $params['taxon_title'] = getMLtext('taxon_species') . ': <i>' . $params['taxonparent'] . ' ' . $params['taxon'] . '</i>';
            } else {
                $rankpos = array_search($params['rank'], $siteconfig['taxonranks']);
                $params['taxon_title'] = getMLtext('taxon_' . $params['rank']) . ': ' . $params['taxon'];
                if ($rankpos>1) $params['taxon_title'] .= ' (' .  getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos-1]) . ' ' . $params['taxonparent'] . ')';
            }
        }
        if ($params['datasetid'] > '') {
            $setcriteria['datasetid'] = $params['datasetid'];
            $params['dataset_title'] = getMLtext('dataset') . ': ' . $tbldataset->GetDatasetTitle($params['datasetid']);
        }
        $setcriteria['download'] = 0; //once download has happened, reset the download switch

        $pageform = $pager->ShowControlForm(url_for('out.listspecies.' . $this->region . '.php'), '', $params['page'], '', 'listanchor', $setcriteria, $params['filterlistby']);
        $pageopts = $pager->ShowPageOptions($params['filterlistby'], $arrSorts, $params['sortlistby']);

        $tpl = new MasterTemplate();
        $tpl->set('site_head_title', getMLText('species_list'));
        $tpl->set('page_specific_head_content', "<link rel='stylesheet' type='text/css' media='screen' href='css/listspecies.css?version=1.0' />
            <script type='text/javascript' src='js/listspecies.js?version=1.0'></script>");
        $tpl->set('site_user', $USER_SESSION);
        $tpl->set('session_msg', $session_msg);
        $tpl->set('region', $this->region);

        $bdy = new MasterTemplate('templates/listspecies.tpl.php');
        $bdy->set('params', $params);
        $bdy->set('region', $this->region);
        $bdy->set('pager', $pager);
        $bdy->set('pageform', $pageform);
        $bdy->set('pageopts', $pageopts);
        $bdy->set('norecords', $norecords);
        $bdy->set('maxrecords_download', $siteconfig['max_spp_to_download']);
        $bdy->set('user', $USER_SESSION);
        $bdy->set('session_msg', $session_msg);

        $tpl->set('sf_content', $bdy);

        return $tpl->fetch('templates/layoutnew.tpl.php');
    }
}
?>