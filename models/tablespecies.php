<?php

require_once("includes/config.php");
require_once("includes/inc.language.php");
require_once("models/table_base.php");

class TableSpecies extends Table_Base {
    const TAXON_BACKBONE = 'taxonomicbackbone'; //dataset id for the taxonomic backbone
    var $sql_listing = "SELECT *, concat(kingdom, ' : ', phylum, ' : ', \"class\", ' : ', \"order\") as highertaxonomy, case when (coalesce(synonym_of,'') > '') then concat(scientificname,' = ',synonym_of) else scientificname end as displayname from vw_spp_list1";

    var $fieldmap_orderby = array(
        "scientificname" => "genus, species, scientificname",
        "dataset" => "dataset_title, genus, species",        
        "fulltaxonomy" => "kingdom, phylum, \"class\", \"order\", family, genus, species",       
        "vernacularname" => "vernacularname"
    );
    var $fieldmap_filterby = array(
        "datasetid" => "datasetid",        
        "region:albertine" => "_regions[1]",
        "region:mountains" => "_regions[2]",
        "region:lakes" => "_regions[3]",
        "taxon" => "",  //special case      
        "filtercontent" => "to_tsvector('english', lower(coalesce(kingdom,'') || ' ' || coalesce(phylum,'') || ' ' || coalesce(\"class\",'') || ' ' || coalesce(\"order\",'') || ' ' || coalesce(family,'') || ' ' || coalesce(genus,'') || ' ' || coalesce(species,'') || ' ' || coalesce(scientificname,'') || ' ' || coalesce(vernacularname,'') || ' ' || coalesce(taxonremarks,'') || ' ' || coalesce(dataset_title,''))) @@ plainto_tsquery('***')",
    );    
    
    var $sql_listing_download = "SELECT t.* FROM taxon t JOIN (***) v ON t._id = v._id";
    
    var $DwCFieldOrdering = array(                        
        'modified',
        'language',
        'rights',
        'rightsholder',
        'accessrights',
        'bibliographiccitation',
        'references',       
        'datasetid',
        'datasetname',
        'informationwithheld',
        'taxonid',
        'scientificnameid',
        'acceptednameusageid',
        'parentnameusageid',
        'originalnameusageid',
        'nameaccordingtoid',
        'namepublishedinid',
        'taxonconceptid',
        'scientificname',
        'acceptednameusage',
        'parentnameusage',
        'originalnameusage',
        'nameaccordingto',
        'namepublishedin',
        'namepublishedinyear',
        'higherclassification',
        'kingdom',
        'phylum',
        'class',
        'order',
        'family',
        'genus',
        'subgenus',
        'specificepithet',
        'infraspecificepithet',
        'taxonomicstatus',
        'verbatimtaxonrank',
        'scientificnameauthorship',
        'vernacularname',
        'nomenclaturalcode',
        'taxonremarks',
        'nomenclaturalstatus',
        'taxonrank',
        '_datasetid'
    );

    public function AddWhere($fieldalias, $evaluate, $value) {
        global $siteconfig;
        /* TODO: check for illegal characters */
        if (array_key_exists($fieldalias, $this->fieldmap_filterby)) {
            $field = $this->fieldmap_filterby[$fieldalias];
            if ($fieldalias == "filtercontent") { //special case
                $value = html_entity_decode($value, ENT_QUOTES);
                $value = str_replace("'","",$value); //HACK               
                $value = strtolower(str_replace(' ', ' & ', trim($value))); //words must be separated by &   
                $value = pg_escape_string($value);
                if ($value > '') {
                    $this->whereclause[] = str_replace("***", $value, $field);
                }
                
            } elseif ($fieldalias == "taxon") {
                $taxon_epithet = ucfirst(strtolower($value[0]));
                $parent_epithet = ucfirst(strtolower($value[2]));
                $rank = strtolower($value[1]);
                $rankpos = array_search($rank, $siteconfig['taxonranks']);
                if (!$rankpos) return; //invalid OR *root*
                if ($rank == 'species') $taxon_epithet = strtolower($taxon_epithet);
                if ($rank == 'kingdom') { //no higher rank to search
                    $this->whereclause[] = "\"" . $rank . "\" = '" . $taxon_epithet . "'";
                } else {
                    $this->whereclause[] = "\"" . $rank . "\" = '" . $taxon_epithet . "' AND \"" . $siteconfig['taxonranks'][$rankpos-1] . "\" = '" . $parent_epithet . "'";
                }
            } else {   
            if (is_string($value)) {
                    //make case-insensitive
                    $this->whereclause[] = "lower(" . $field . ") " . $evaluate . " lower('" . pg_escape_string($value) . "')";
                } else {
                    if (($value === true) || ($value === false)) {
                        $this->whereclause[] = $field . " " . $evaluate . " " . ($value === true? "true" : "false") . "";
                    } else { //genuine number
                        $this->whereclause[] = $field . " " . $evaluate . " " . $value . "";
                    }
                }
            }
        }
    }
            
        
    //return a dataset of the child elements for a particular taxon, and its eventual number of species and related occurrence records
    //can select from the taxonomic backbone, other taxon datasets, or both
    //because of case-sensistive joins, taxon and occurrence tables are assumed to be preprocessed to standardise to initial capitals
    //includeOccTaxa = the dataset may also contain occurrence-derived taxa (taxa mentioned in the occurrence table that
    //are not present in the taxon table): these are listed at the end with NULL values for numspecies
    //region: region (to only get species from datasets applicable to the region) or '' for aggregate list
    private function GetChildrenOf($region, $taxon_epithet, $rank, $taxonomicBackbone = true, $otherTaxonData = false, $includeOccTaxa = false) {
        global $siteconfig;
        $taxon_epithet = ucfirst(strtolower($taxon_epithet));
        $rank = strtolower($rank);
        if (!($taxonomicBackbone || $otherTaxonData))
            return array(); //bad call: no datasets selected
        $rankpos = array_search($rank, $siteconfig['taxonranks']);
        if ($rankpos === false || $rank == 'species')
            return array(); //bad call: invalid taxon rank or species (which have no children)

            
        //to determine no. of occurrence records, we join on rank and child rank.
        //this is ok, because the taxonomic hierarchy elements for occurrence_processed records have been filled in
        //and the chance of different parts of the taxonomic tree having both rank + child rank the same is miniscule
        //(i.e. although there might be two genus 'x' in different parts of the hierarchy, there won't be
        //two genus 'x' each with parent family 'y' in the tree
        
        //TODO: what about region filter on occurrence records?
        
        if ($rank == 'genus') {
            $childrank = "species, _species_with_synof"; //to include additional field
        } else {
            $childrank = '"' . $siteconfig['taxonranks'][$rankpos + 1] . '"';
        }
        $sql = "SELECT tax.numspecies, occ._" . $siteconfig['taxonranks'][$rankpos + 1] . ", occ.numoccs, tax." . $childrank . " FROM ";
        if ($taxonomicBackbone && $otherTaxonData) {
            //no dataset criterion, include all
            $sql .= "(SELECT ";
            if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
            $sql .= $childrank . ", count(*) as numspecies FROM taxon ";
            switch ($region) {
                case "albertine"    : $sql .= "WHERE _regions[1] = true "; break;
                case "mountains"    : $sql .= "WHERE _regions[2] = true "; break;
                case "lakes"        : $sql .= "WHERE _regions[3] = true "; break;
            }
            $sql .= "GROUP BY ";
            if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
            $sql .= $childrank . " ";
            if ($rank != '*root*') $sql .= "HAVING ";
        } else {
            $sql .= "(SELECT (_datasetid = '" . self::TAXON_BACKBONE . "') isBackbone, ";
            if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
            $sql .= $childrank . ", count(*) as numspecies FROM taxon ";            
            switch ($region) {
                case "albertine"    : $sql .= "WHERE _regions[1] = true "; break;
                case "mountains"    : $sql .= "WHERE _regions[2] = true "; break;
                case "lakes"        : $sql .= "WHERE _regions[3] = true "; break;
            }
            $sql .= "GROUP BY (_datasetid = '" . self::TAXON_BACKBONE . "'), ";
            if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
            $sql .= $childrank . "\" HAVING ";
            if ($taxonomicBackbone) 
                $sql .= "(_datasetid = '" . self::TAXON_BACKBONE . "') = true ";
            if ($otherTaxonData) 
                $sql .= "(_datasetid = '" . self::TAXON_BACKBONE . "') = false ";
            if ($rank != '*root*') $sql .= "AND ";
        }
        if ($rank != '*root*') $sql .= "\"" . $rank . "\" = '" . pg_escape_string($taxon_epithet) . "' ";
           
        $sql .= ") tax ";
        if ($includeOccTaxa) {
            $sql .= "FULL OUTER JOIN ";
        } else {
            $sql .= "LEFT JOIN ";
        }
        $sql .= "(SELECT ";
        if ($rank != '*root*') $sql .= "_" . $rank . ", ";
        $sql .= "_" . $siteconfig['taxonranks'][$rankpos + 1] . ", count(*) as numoccs FROM occurrence_processed op JOIN dataset d ON op._datasetid = d.datasetid ";
        switch ($region) {
            case "albertine"    : $sql .= "WHERE d._regions[1] = true "; break;
            case "mountains"    : $sql .= "WHERE d._regions[2] = true "; break;
            case "lakes"        : $sql .= "WHERE d._regions[3] = true "; break;
        }
        $sql .= "GROUP BY ";
        if ($rank != '*root*') $sql .= "_" . $rank . ", ";
        $sql .= "_" . $siteconfig['taxonranks'][$rankpos + 1] . " ";
        if ($rank != '*root*') $sql .= "HAVING _" . $rank . " = '" . pg_escape_string($taxon_epithet) . "' ";
        $sql .= ") occ ";
        $sql .= "ON ";
        if ($rank != '*root*') $sql .= "tax.\"" . $rank . "\" = occ._" . $rank . " AND ";
        $sql .= "tax.\"" . $siteconfig['taxonranks'][$rankpos + 1] . "\" = occ._" . $siteconfig['taxonranks'][$rankpos + 1] . " ";
        $sql .= "ORDER BY tax." . $childrank . ", occ._" . $siteconfig['taxonranks'][$rankpos + 1] . " ASC";
        //if ($rank=='genus' && $taxon_epithet=='Cercopithecus') echo $sql;
        $res = pg_query_params($sql, array());
        if (!$res) return array(); //error
        $resarr = array();
        while ($row = pg_fetch_array($res)) {
            $resarr[] = $row;
        }
        return $resarr;
    }

    //only needed if includeOccTaxa is specified
    //counts the number of distinct species under a particular taxon (using genus and species)
    //note: if looking at species level then can get wrong counts (multiple genera with same specific epithet)
    function GetSpeciesTreeCount ($region, $taxon_epithet, $rank, $taxonomicBackbone = true, $otherTaxonData = false, $includeOccTaxa = false) {
        if ($rank == 'species') return 1;
        
        $sql = "SELECT count(*) as numspecies FROM ( ";
        if ($includeOccTaxa) {
            $sql .= "SELECT ";
            if ($rank != '*root*') $sql .= "_" . $rank . ", ";
            $sql .= "_genus, _species FROM occurrence_processed ";
            $sql .= "GROUP BY ";
            if ($rank != '*root*') $sql .= "_" . $rank . ", ";
            $sql .= "_genus, _species ";
            if ($rank != '*root*') $sql .= "HAVING _" . $rank . " = '" . $taxon_epithet . "' ";
            $sql .= "UNION DISTINCT ";
        }
        $sql .= "SELECT ";
        if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
        $sql .= "genus, species from taxon ";
        if ($taxonomicBackbone && $otherTaxonData) {
            //include all entries
            switch ($region) {
                case "albertine"    : $sql .= "WHERE _regions[1] = true "; break;
                case "mountains"    : $sql .= "WHERE _regions[2] = true "; break;
                case "lakes"        : $sql .= "WHERE _regions[3] = true "; break;
            }
        } else {            
            $sql .= "WHERE ";
            if ($taxonomicBackbone) 
                $sql .= "_datasetid = '" . self::TAXON_BACKBONE . "' ";
            if ($otherTaxonData) 
                $sql .= "_datasetid <> '" . self::TAXON_BACKBONE . "' ";
            switch ($region) {
                case "albertine"    : $sql .= "AND _regions[1] = true "; break;
                case "mountains"    : $sql .= "AND _regions[2] = true "; break;
                case "lakes"        : $sql .= "AND _regions[3] = true "; break;
            }
        }
        $sql .= "GROUP BY ";
        if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
        $sql .= "genus, species ";
        
        if ($rank != '*root*') $sql .= "HAVING \"" . $rank . "\" = '" . $taxon_epithet . "'";
        $sql .= ") distinctspp;";
        //if ($rank=='species' && $taxon_epithet=='daurica') echo $sql;
        $res = pg_query_params($sql, array());
        while ($row = pg_fetch_array($res)) { //should only be one row
            $numspecies = $row['numspecies'];
        }
        return $numspecies;
    }
    //decide:
    //use ajax or load everything up-front
    //cache totals (update whenever dataset changes), or calc on the fly
    //recursive function to get accordion text for all entries below a particular entry
    function GetAccordionBelow($region = '', $taxon_epithet = '', $rank = '*root*', $taxonomicBackbone = true, $otherTaxonData = false, $includeOccTaxa = false, $current_link='') {
        global $siteconfig;
        $taxon_epithet = ucfirst(strtolower($taxon_epithet));
        $rank = strtolower($rank);
        if (!($taxonomicBackbone || $otherTaxonData))
            return ''; //bad call: no datasets allowed
        $rankpos = array_search($rank, $siteconfig['taxonranks']);
        if ($rankpos === false || $rank == 'species')
            return ''; //at end of chain or invalid rank

        $child_count = 0;
        $accordion = '';
        $rankpos = array_search($rank, $siteconfig['taxonranks']);
        $childrank = $siteconfig['taxonranks'][$rankpos + 1];
        $res = $this->GetChildrenOf($region, $taxon_epithet, $rank, $taxonomicBackbone, $otherTaxonData, $includeOccTaxa);
        foreach ($res as $row) {        
            $child_count++;
            $child_link = $current_link . '_' . $child_count; //for navigation
            $child = (empty($row[3]) ? $row[1] : $row[3]);
            if ($childrank == 'species') {
                //extra field returned for synonym of                
                $displayname = htmlentities($row[4]);                
            }
            if ($childrank != 'species' || empty($displayname)) {            
                $displayname = (empty($row[3]) ? (empty($row[1]) ? "(unnamed)" : htmlentities($row[1])) . " *" : htmlentities($row[3]));
            }
            $taxonparams = "taxon=" . htmlentities($child) . "&rank=" . $siteconfig['taxonranks'][$rankpos + 1] . "&taxonparent=" . ($rank=='*root*'? '--' : htmlentities($taxon_epithet));
            $taxondatasetparam = "";
            if ($taxonomicBackbone && $otherTaxonData) {
                //include all datasets
            } else {
                if ($taxonomicBackbone) 
                    $taxondatasetparam = "&datasetid=" . self::TAXON_BACKBONE;
                if ($otherTaxonData) 
                    $taxondatasetparam .= "&x_datasetid=" . self::TAXON_BACKBONE;       
            }
            $numspecies = $this->GetSpeciesTreeCount($region, $child, $siteconfig['taxonranks'][$rankpos + 1], $taxonomicBackbone, $otherTaxonData, $includeOccTaxa);
            if ($rank != '*root*') $accordion .= "<ul>"; // "<div class='inner'><ul>";
            //$accordion .= "<li><h5>" . ucfirst(getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos + 1])) . ": " . $displayname;
            $accordion .= "<li><a href='out.speciestree." . $region . ".php#link" . $child_link . "'>" . ucfirst(getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos + 1])) . ": " . $displayname . "</a>";
            $accordion .= "<span class='species_links'>";
            if ($siteconfig['taxonranks'][$rankpos + 1] == 'species') {
                $accordion .= " &nbsp; ";
            } else {
                $accordion .= getMLtext('species_distinct') . ": <a href='out.listspecies." . $region . ".php?" . $taxonparams . $taxondatasetparam . "' title='" . getMLtext('view_species') . "' alt='" . getMLtext('view_species') . "'>" . $numspecies . "</a>, ";
            }
            $accordion .= getMLtext('occurrence_records') . ": ";
            if (empty($row[2])) {
                $accordion .= "0";
            } else {
                $accordion .= "<a href='out.listoccurrence." . $region . ".php?" . $taxonparams . "' title='" . getMLtext('view_occurrences') . "' alt='" . getMLtext('view_occurrences') . "'>" . $row[2] . "</a>";            
            } 
            //$accordion .= "</span></h5>";
            $accordion .= "</span>";
            $accordion .= $this->GetAccordionBelow($region, $child, $siteconfig['taxonranks'][$rankpos + 1], $taxonomicBackbone, $otherTaxonData, $includeOccTaxa, $child_link);
            $accordion .= "</li>";
            if ($rank != '*root*') $accordion .= "</ul>"; //"</ul></div>";
        }

        return $accordion;
    }
    
    public function Download() {
        global $siteconfig;
        
        //download is of original DwC data, not the summary view
        $sql = str_replace("***", $this->GetSQLlisting(), $this->sql_listing_download);
        $res = pg_query_params($sql, array());
        if (!$res) return 0; //SQL error
        
        header('Content-Description: File Transfer');
        header('Content-Encoding: UTF-8');
        /* header('Content-Transfer-Encoding: binary'); */
        /* header('Content-type: text/csv; charset=UTF-8'); */
        header("Content-type: application/vnd.ms-excel; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"arbmis_taxa.csv\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "sep=\t\r\n"; //HACK: for Excel to recognise the tab delimiter.  Doesn't work for Mac Excel though
        //print column headers
        foreach($this->DwCFieldOrdering as $field) {
            echo getMLtext('occ_' . $field) . "\t";
        }
        echo "\r\n";
        //print data
        while ($row = pg_fetch_array($res)) {
            foreach($this->DwCFieldOrdering as $field) {
                if ($field != '_datasetid') {
                    echo $row[$field] . "\t";
                } else {
                    //URL to dataset
                    echo $siteconfig['path_ipt'] . "/resource.do?r=" . $row[$field] . "\t";
                }
            }
            echo "\r\n";
        }
        return -1;
    }

    
}

?>