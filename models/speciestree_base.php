<?php

class SpeciesTreeController {
    private $region;
    
    public function SpeciesTreeController($reg) {
        $this->region = $reg;
    }
    //returns the page HTML
    public function GetSpeciesTree() {        
        global $USER_SESSION;
        
        /* page options */
        $params = array();

        /* get model for page content */
        $tblspecies = new TableSpecies();

        $pageform = '';
        $pageopts = '';        

        $txt = $tblspecies->GetAccordionBelow($this->region, '', '*root*', true, true, false);

        /* page template main */
        $tpl = new MasterTemplate();
        $tpl->set('site_head_title', getMLText('species_explorer'));
        $tpl->set('page_specific_head_content', "<link rel='stylesheet' type='text/css' media='screen' href='css/species.css?version=1.0' />
            <script src='js/datepicker/js/bootstrap-datepicker.js'></script>    
            <script type='text/javascript' src='js/jqtree/tree.jquery.js'></script>
            <script type='text/javascript' src='js/jquery.nestedAccordion.js'></script>
            <script type='text/javascript' src='js/speciesaccordion.js'></script>");
        $tpl->set('site_user', $USER_SESSION);
        $tpl->set('region', $this->region);

        /* page template body - pass page options to this as well */
        $bdy = new MasterTemplate('templates/species.tpl.php');
        $bdy->set('params', $params);
        $bdy->set('region', $this->region);
        $bdy->set('txt', $txt);
        $bdy->set('pageform', $pageform);
        $bdy->set('pageopts', $pageopts);

        /* link everything together */
        $tpl->set('sf_content', $bdy);

        /* page display */
        return $tpl->fetch('templates/layoutnew.tpl.php');
    }
}
?>