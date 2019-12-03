<?php

require_once('includes/inc.language.php'); //for translations

class Pager {

    var $entries_tot;
    var $entries_per_page;
    var $cur_page;
    var $last_page;
    var $entries = array();
    var $entries_display = array();
    var $entries_transform = array(); //supports simple replace functionality on array of fields
    var $entries_translate = array(); //if the content of a field is actually a lookup to the language table
    var $entries_boolean = array();
    /* var $entries_showhide = array(); //for collapsible columns */
    var $bold_row = array(); //criteria for bolding a row

    public function Pager($tot = 0, $perpg = 1, $cur = 1) {
        $this->entries_tot = $tot;
        $this->entries_per_page = $perpg;
        $this->cur_page = $cur;
        $this->last_page = intval(($tot + $perpg - 1) / $perpg);
    }

    public function GetPage() {
        return $this->cur_page;
    }

    public function GetPreviousPage() {
        if ($this->cur_page > 1)
            return $this->cur_page - 1;
        return 1;
    }

    public function GetNextPage() {
        if ($this->cur_page < $this->last_page)
            return $this->cur_page + 1;
        return $this->last_page;
    }

    public function GetLastPage() {
        return $this->last_page;
    }

    public function MustPage() {
        return ($this->last_page > 1 ? 1 : 0);
    }

    public function GetTotalEntries() {
        return $this->entries_tot;
    }
    
    public function SetEntryTransform($field, $search, $replace) {
        $this->entries_transform[$field] = array($search, $replace);
    }

    public function SetEntryTranslate($field) {
        $this->entries_translate[$field] = true;
    }
    
    public function SetEntryBoolean($field) {
        $this->entries_boolean[$field] = true;
    }
    /*
    public function SetEntryShowHide($field, $initialshow = true) {
        $this->entries_showhide[$field] = $initialshow;
    }
    */
    public function SetBoldRowCondition($field, $condition, $value) {
        $this->bold_row[] = array('field' => $field, 'condition' => $condition, 'value' => $value);
    }

    private function IsBoldRow($row) {       
        if (!count($this->bold_row)) return 0; //no bold criteria
        foreach ($this->bold_row as $bold_rule) {
            $val = $row[$bold_rule['field']];
            if (is_numeric($val) && is_numeric($bold_rule['value'])) {
                $test = "return(" . $row[$bold_rule['field']] . "" . $bold_rule['condition'] . $bold_rule['value'] . ");";            
            } else {
                $test = "return('" . $row[$bold_rule['field']] . "'" . $bold_rule['condition'] . "'" . $bold_rule['value'] . "');";            
            }
            //echo $test;
            if (eval($test)) return 1; //should be bolded
        }
        return 0;
    }
    
    public function SetEntries($res) {
        unset($this->entries);
        $this->entries = array();
        foreach ($res as $row) {        
            $this->entries[] = $row;
        }
    }

    private function GetURLparams($entry, $rowdata) {
        $urlparams = '';
        if (isset($entry['linkparams'])) {
            $urlparams = '?';
            foreach ($entry['linkparams'] as $param => $valfield) {
                $urlparams .= $param . '=';
                if (substr($valfield,0,1) == "'" && substr($valfield,-1) == "'") {
                    $urlparams .= trim($valfield,"'") . '&'; //literal
                } else {
                    $urlparams .= $rowdata[$valfield] . '&';
                }
            }
            $urlparams = substr($urlparams,0,-1); //remove trailing '&'
        } 
        return $urlparams;
    }
    //link will be either a straight URL or, if not like '*.*' then a url_for the passed value
    //linkparams will be key-value pairs of url parameters (e.g. 'id' or 'user_id') if applicable    
    public function SetEntriesDisplay($arrCols) {
        unset($this->entries_display);
        $this->entries_display = array();
        foreach ($arrCols as $field => $col) {
            $this->entries_display[$field] = array();
            $this->entries_display[$field]['heading'] = $col['heading'];
            $this->entries_display[$field]['link'] = (isset($col['link']) ? $col['link'] : ''); //if its a URL            
            if (isset($col['linkparams'])) {
                //parameters and values
                $this->entries_display[$field]['linkparams'] = array();
                foreach ($col['linkparams'] as $key=>$val) {
                    $this->entries_display[$field]['linkparams'][$key] = $val;
                }
            } 
        }
    }

    public function AddEntryRow($row) {
        $this->entries[] = $row; /* unserialize */
        return count($this->entries);
    }

    public function GetEntries() {
        return $this->entries;
    }

    public function GetEntryCount() {
        return count($this->entries);
    }

    public function ShowBrowseControls($items_description) {
        /* TODO: internationalisation */
        $html = '';
        if ($this->MustPage()) {
            $html .= '<div class="pagination">';
            $html .= '<a href="javascript:BrowsePage(1);">';
            $html .= '<img src="images/first.png" alt="' . getMLText('first_page') . '" title="' . getMLText('first_page') . '" />';
            $html .= '</a>';
            $html .= '<a href="javascript:BrowsePage(' . $this->GetPreviousPage() . ');">';
            $html .= '<img src="images/previous.png" alt="' . getMLText('previous_page') . '" title="' . getMLText('previous_page') . '" />';
            $html .= '</a>';
            $html .= '<a href="javascript:BrowsePage(' . $this->GetNextPage() . ');">';
            $html .= '<img src="images/next.png" alt="' . getMLText('next_page') . '" title="' . getMLText('next_page') . '" />';
            $html .= '</a>';
            $html .= '<a href="javascript:BrowsePage(' . $this->GetLastPage() . ');">';
            $html .= '<img src="images/last.png" alt="' . getMLText('last_page') . '" title="' . getMLText('last_page') . '" />';
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '<div class="pagination_desc">';
        $html .= '<strong>' . $this->GetTotalEntries() . '</strong> ' . $items_description;
        if ($this->MustPage()) {
            $html .= '- page <strong>' . $this->GetPage() . '/' . $this->GetLastPage() . '</strong>';
        }
        $html .= '</div>';
        return $html;
    }

    //For ShowControlForm and ShowPageOptions, reason they are separated is due to problem of nested forms.  
    //Should embed control form at top of page, to keep it out of any other e.g. editing forms where paged list of content is also displayed (e.g. project_edit)
    //Then display html controls using ShowPageOptions at normal place in page: 'submit' uses javascript to set values of control form and submit it,
    //instead of having them submitted by natural DOM context    
    //setcriteria is array of key-value pairs that user cannot modify that are submitted with the form
    public function ShowControlForm($pageurl, $pageid = '', $listpage = 1, $pageanchor = '', $anchor = '', $setcriteria = '', $lastfilter = '') {
        $html = "<script type='text/javascript'>";

        $html .= "function BrowsePage(pg) {";
        $html .= "document.getElementById('filterlistby').value = document.getElementById('xfilterlistby').value;";
        $html .= "document.getElementById('sortlistby').value = document.getElementById('xsortlistby')[document.getElementById('xsortlistby').selectedIndex].value;";
        $html .= "document.getElementById('page').value = pg;";
        $html .= "downloadlink = document.getElementById('download');";
        $html .= "if (downloadlink) downloadlink.value = 0;";
        if ($pageanchor != '')
            $html .= "document.getElementById('scrollto').value = '" . $pageanchor . "';";
        $html .= "document.getElementById('frm_browse').submit();";
        $html .= "}";

        $html .= "function TrapReturnKeyPress(evt) {";
        $html .= "if (evt.keyCode == 13) {";
        $html .= "BrowsePage(" . $listpage . ");";
        $html .= "}";
        $html .= "return;";
        $html .= "}";

        $html .= "function ResetSearch() {";
        $html .= "document.getElementById('filterlistby').value='';";
        $html .= "document.getElementById('sortlistby').value='';";
        $html .= "document.getElementById('page').value=1;";
        $html .= "document.getElementById('scrollto').value = '';";
        $html .= "document.getElementById('frm_browse').submit();";
        $html .= "}";
        
        /* $html .= "function show_hide_column(col_no, do_show) {";
        $html .= "var tbl = document.getElementById('pager_table');";
        $html .= "var col = tbl.getElementsByTagName('col')[col_no];";
        $html .= "if (col) {";
        $html .= 'col.style.visibility=do_show?"":"collapse";';
        $html .= "}}"; */
        //above does not work in Chrome
        /* $html .= "var stl = 'none';";
        $html .= "if (do_show) stl = 'block';";        
        $html .= "var tbl  = document.getElementById('pager_table');";
        $html .= "var rows = tbl.getElementsByTagName('tr');";
        $html .= "for (var row=1; row<rows.length;row++) {";
        $html .= "var cels = rows[row].getElementsByTagName('td');";
        $html .= "cels[col_no].style.display=stl;";
        $html .= "}}";    
        $html .= "function toggle_colwidth(col_no) {";
        $html .= "var is_collapse = false;";
        $html .= "var tbl = document.getElementById('pager_table');";
        $html .= "var rows = tbl.getElementsByTagName('tr');";
        $html .= "for (var row=1; row<rows.length;row++) {";
        $html .= "var cels = rows[row].getElementsByTagName('td');";
        $html .= "if (cels[col_no].className.indexOf('collapsedcolumn') > -1) {";
        $html .= "cels[col_no].className=cels[col_no].className.replace( /(?:^|\s)collapsedcolumn(?!\S)/g , '' );";
        $html .= "} else {";
        $html .= "cels[col_no].className+=' collapsedcolumn';";
        $html .= "is_collapse = true }";
        $html .= "}";
        $html .= "var rowhead = tbl.getElementsByTagName('th');";
        $html .= "if (!is_collapse) {"; 
        $html .= "rowhead[col_no].className=rowhead[col_no].className.replace( /(?:^|\s)collapsedcolumn(?!\S)/g , '' );";
        $html .= "} else {";
        $html .= "rowhead[col_no].className+= ' collapsedcolumn';";
        $html .= "}";
        $html .= "var button = document.getElementById('collapsebutton' + col_no.toString());";
        $html .= "if (is_collapse) { button.value='+'; } else { button.value='-'; }";
        $html .= "}"; 
         * above does not work in IE / Firefox, would need to wrap td content in 2 divs as per http://stackoverflow.com/questions/7569436/css-constrain-a-table-with-long-cell-contents-to-page-width/7570613#7570613 possibly
        */
        $html .= "</script>";

        $html .= "<form action='" . $pageurl . "' method='get' id='frm_browse' name='frm_browse' >";
        if ($pageid != '')
            $html .= "<input type='hidden' id='id' name='id' value='" . $pageid . "' />";
        $html .= "<input type='hidden' id='filterlistby' name='filterlistby' value='" . $lastfilter . "' />";
        $html .= "<input type='hidden' id='sortlistby' name='sortlistby' value='' />";
        $html .= "<input type='hidden' name='page' id='page' value='" . $listpage . "' />";
        $html .= "<input type='hidden' name='scrollto' id='scrollto' value='" . $anchor . "' />";
        if (is_array($setcriteria)) {
            foreach ($setcriteria as $key => $val) {
                $html .= "<input type='hidden' name='" . $key . "' id='" . $key . "' value='" . htmlspecialchars($val) . "' />";
            }
        }
        $html .= "</form>";
        return $html;
    }

    public function ShowPageOptions($filter, $arrSorts, $activesort) {
        $html = getMLtext('filter') . " ";
        $html .= "<input type='text' id='xfilterlistby' name='xfilterlistby' maxlength='30' size='30' value='" . $filter . "' style='margin-bottom:0px' onKeyPress='javascript:TrapReturnKeyPress(event)'/> ";
        $html .= " &nbsp;" . getMLtext('sort_by') . " ";
        $html .= "<select id='xsortlistby' name='xsortlistby' style='margin-bottom:0px'>";
        foreach ($arrSorts as $value => $descr) {
            $html .= "<option value='" . htmlspecialchars($value) . "'" . ($value == $activesort ? " selected" : "") . ">" . htmlspecialchars($descr) . "</option>";
        }
        $html .= "</select> ";
        $html .= " &nbsp;<input type='button' style='min-width:3em' value='" . getMLtext('go') . "' onclick=\"javascript:BrowsePage(" . $this->GetPage() . "); \" />";
        $html .= " <input type='button' value='" . getMLtext('reset') . "' onclick=\"javascript:ResetSearch();\" />";
        return $html;
    }

    public function ShowPageItems() {
        /* $html = "<!--[if IE]>
<style>
    table
    {
        table-layout: fixed;
        width: 100px;
    }
</style>
<![endif]-->"; */
        $html = "<table id='pager_table' class='pagelist'>";
        //RR added for show/hide column function
        /* foreach ($this->entries_display as $field => $opts) {
            $html .= "<col/>";            
        } */
        $html .= "<thead>";
        $html .= "<tr>";
        $col = 1;
        foreach ($this->entries_display as $field => $opts) {
            $html .= "<th class='colhead" . $col . "'>";
            /* if (isset($this->entries_showhide[$field])) {                
                $html .= "<input id='collapsebutton" . ($col-1) . "' class='collapsebutton' type='button' value='-' onclick='javascript:toggle_colwidth(" . ($col-1) . ")' /> "; 
            } */
            $html .= $opts['heading'] . "</th>";
            $col++;
        }
        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";
        foreach ($this->GetEntries() as $i => $row) {
            $html .= "<tr class='" . (fmod($i, 2) ? 'even' : 'odd') . "'>";
            $col = 1;
            $bold_row = $this->IsBoldRow($row);
            foreach ($this->entries_display as $field => $opts) {
                $html .= "<td class='col" . $col . "'>";
                if ($opts['link'] > '') {
                    $html .= "<a href='";
                    if (stripos($opts['link'], '.') !== false) { //std. url
                        $html .= $opts['link'];                        
                    } else {
                        $html .= $opts['link'] . ".php";
                    }
                    $html .= $this->GetURLparams($opts, $row);                        
                    $html .= "'>";
                }
                if (isset($this->entries_transform[$field])) 
                    $row[$field] = str_replace($this->entries_transform[$field][0], $this->entries_transform[$field][1], $row[$field]);
                if (isset($this->entries_translate[$field]))
                    $row[$field] = getMLtext($row[$field]);
                if (isset($this->entries_boolean[$field])) {                
                    if ($row[$field] == 't' || $row[$field] == 1) {
                        $row[$field] = getMLtext('yes');
                    } else {
                        $row[$field] = getMLtext('no');
                    }
                }
                if ($bold_row) $html .= "<b>";
                $html .= $row[$field];
                if ($bold_row) $html .= "</b>";
                if ($opts['link'] > '') $html .= "</a>";
                $html .= "</td>";
                $col++;
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= "</table>";
        return $html;
    }

}

?>