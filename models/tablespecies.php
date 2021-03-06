<?php

require_once("includes/config.php");
require_once("includes/inc.language.php");
require_once("models/table_base.php");

class TableSpecies extends Table_Base {
    const TAXON_BACKBONE = 'taxonomicbackbone'; //dataset id for the taxonomic backbone
    var $sql_listing = "SELECT *, concat(kingdom, ' : ', phylum, ' : ', \"class\", ' : ', \"order\") as highertaxonomy, case when (coalesce(synonym_of,'') > '') then concat(scientificname,' = ',synonym_of) else scientificname end as displayname from vw_spp_list1";

    var $fieldmap_orderby = array(
        "scientificname" => "scientificname", //TODO: was genus, species,
        //"dataset" => "dataset_title, genus, species",
        "fulltaxonomy" => "kingdom, phylum, \"class\", \"order\", family, scientificname", //was genus, species
        "vernacularname" => "vernacularname"
    );
    var $fieldmap_filterby = array(
        //"region:albertine" => "_regions[1]",
        //"region:mountains" => "_regions[2]",
        //"region:lakes" => "_regions[3]",
        "taxon" => "",  //special case
        "rank" => "taxonrank",
        "filtercontent" => "to_tsvector('english', lower(coalesce(kingdom,'') || ' ' || coalesce(phylum,'') || ' ' || coalesce(\"class\",'') || ' ' || coalesce(\"order\",'') || ' ' || coalesce(family,'') || ' ' || coalesce(genus,'') || ' ' || coalesce(species,'') || ' ' || coalesce(scientificname,'') || ' ' || coalesce(vernacularname,'') || ' ' || coalesce(taxonremarks,'') )) @@ plainto_tsquery('***')",
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
        'acceptedscientificname',
        'taxonkey',
        'acceptedtaxonkey',
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
        'species',
        'taxonomicstatus',
        'verbatimtaxonrank',
        'scientificnameauthorship',
        'vernacularname',
        'nomenclaturalcode',
        'taxonremarks',
        'nomenclaturalstatus',
        'taxonrank'
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
                $taxon_epithet = $value[0]; //ucfirst(strtolower($value[0]));
                $parent_epithet = ucfirst(strtolower($value[2]));
                $rank = strtolower($value[1]);
                $rankpos = array_search($rank, $siteconfig['taxonranks']);
                if (!$rankpos) return; //invalid OR *root*
                //if ($rank == 'species') $taxon_epithet = strtolower($taxon_epithet);
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
    //because of case-sensitive joins, taxon and occurrence tables are assumed to be preprocessed to standardise to initial capitals
    //region: region (to only get species from datasets applicable to the region) or '' for aggregate list
    private function GetChildrenOf($region, $taxon_epithet, $rank) {
        global $siteconfig;
        //$taxon_epithet = ucfirst(strtolower($taxon_epithet));
        $rank = strtolower($rank);
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
        $parentranks = "";
        for ($i = 1; $i <= $rankpos; $i++) {
            $parentranks .= ($parentranks != ""? ", " : "") . "'" . strtoupper($siteconfig['taxonranks'][$i]) . "'";
        }
        $sql = "SELECT tax.numspecies, occ._" . $siteconfig['taxonranks'][$rankpos + 1] . ", occ.numoccs, tax." . $childrank . " FROM ";
        $sql .= "(SELECT ";
        if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
        $sql .= $childrank . ", count(*) as numspecies FROM taxon ";

        if ($parentranks != "") {
            $sql .= "WHERE NOT taxonrank IN (" . $parentranks . ") ";
        }
        $sql .= "GROUP BY ";
        if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
        $sql .= $childrank . " ";
        if ($rank != '*root*') {
            $sql .= "HAVING ";
            $sql .= "\"" . $rank . "\" = '" . pg_escape_string($taxon_epithet) . "' ";
        }
        $sql .= ") tax ";
        $sql .= "LEFT JOIN ";
        $sql .= "(SELECT ";
        if ($rank != '*root*') $sql .= "_" . $rank . ", ";
        $sql .= "_" . $siteconfig['taxonranks'][$rankpos + 1] . ", count(*) as numoccs FROM occurrence_processed op JOIN dataset d ON op.datasetkey = d.datasetkey ";

        $sql .= "GROUP BY ";
        if ($rank != '*root*') $sql .= "_" . $rank . ", ";
        $sql .= "_" . $siteconfig['taxonranks'][$rankpos + 1] . " ";
        if ($rank != '*root*') $sql .= "HAVING _" . $rank . " = '" . pg_escape_string($taxon_epithet) . "' ";
        $sql .= ") occ ";
        $sql .= "ON ";
        if ($rank != '*root*') $sql .= "tax.\"" . $rank . "\" = occ._" . $rank . " AND ";
        $sql .= "tax.\"" . $siteconfig['taxonranks'][$rankpos + 1] . "\" = occ._" . $siteconfig['taxonranks'][$rankpos + 1] . " ";
        $sql .= "ORDER BY tax." . $childrank . ", occ._" . $siteconfig['taxonranks'][$rankpos + 1] . " ASC";
        //if ($rank=='kingdom' )
        //    echo $sql;
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
    function GetSpeciesTreeCount ($taxon_epithet, $rank) {
        if ($rank == 'species') return 1;
        
        $sql = "SELECT count(*) as numspecies FROM ( ";
        $sql .= "SELECT ";
        if ($rank != '*root*') $sql .= "\"" . $rank . "\", ";
        $sql .= "genus, species from taxon ";
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

    //can now use ajax or load everything up-front
    //decide: cache totals (update whenever dataset changes), or calc on the fly
    //recursive function to get accordion text for all entries below a particular entry
    function GetAccordionBelow($region = '', $taxon_epithet = '', $rank = '*root*', $ajax = false, $current_link='') {
        global $siteconfig;
        //$taxon_epithet = ucfirst(strtolower($taxon_epithet));
        $rank = strtolower($rank);

        $rankpos = array_search($rank, $siteconfig['taxonranks']);
        if ($rankpos === false || $rank == 'species')
            return ''; //at end of chain or invalid rank

        $child_count = 0;
        $accordion = '';
        $childrank = $siteconfig['taxonranks'][$rankpos + 1];
        $res = $this->GetChildrenOf($region, $taxon_epithet, $rank);
        foreach ($res as $row) {
            $child_count++;
            $child_link = $current_link . '_' . $child_count; //for navigation
            if (substr($child_link,0,4) != 'link') $child_link = 'link' . $child_link;
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

            $numspecies = $row[0]; //$this->GetSpeciesTreeCount($child, $siteconfig['taxonranks'][$rankpos + 1]);
            if ($rank != '*root*') $accordion .= "<ul style='display:block'>"; // "<div class='inner'><ul>";
            //$accordion .= "<li><h5>" . ucfirst(getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos + 1])) . ": " . $displayname;
            $accordion .= "<li><a href='out.speciestree." . $region . ".php#" . $child_link . "' class='trigger'>" . ucfirst(getMLtext('taxon_' . $siteconfig['taxonranks'][$rankpos + 1])) . ": " . $displayname . "</a>";
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
            if (!$ajax) {
                $accordion .= $this->GetAccordionBelow($region, $child, $siteconfig['taxonranks'][$rankpos + 1], $child_link);
            }
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
                echo $row[$field] . "\t";
            }
            echo "\r\n";
        }
        return -1;
    }

    
}

?>