<?php

class Table_Base {
    var $sql_listing = "";
    var $whereclause = array();
    var $fieldmap_orderby = array();
    
    public function ClearWhere() {
        $this->whereclause = array();
    }

    protected function GetWhereClause() {
        $sql = "";
        if (count($this->whereclause) > 0) {
            for ($i = 0; $i < count($this->whereclause); $i++) {
                if ($i == 0) {
                    $sql .= " WHERE (";
                } else {
                    $sql .= " AND ";
                }
                $sql .= $this->whereclause[$i];
            }
            $sql .= ")";
        }
        return $sql;
    }
    
    public function AddWhere($fieldalias, $evaluate, $value) {
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
            } else {
                //table contextualised fields, and array fields, need special treatment
                if ((strpos($field, ".") > 0) || (strpos($field, "[") > 0)) {
                    $quotedfield = "\"" . str_replace(".", "\".\"", $field);
                    if (strpos($field, "[") > 0) {
                        $quotedfield = str_replace("[", "\"[", $quotedfield);
                    } else {
                        $quotedfield = $quotedfield . "\"";
                    }
                } else {
                    $quotedfield = "\"" . $field . "\"";
                }
                if (is_numeric($value) || is_bool($value)) {
                    if (is_bool($value)) {
                        $this->whereclause[] = "" . $quotedfield . " " . $evaluate . " " . ($value === true? "true" : "false") . "";
                    } else { //genuine number
                        $this->whereclause[] = "" . $quotedfield . " " . $evaluate . " " . $value . "";
                    }
                } else {
                    //make case-insensitive
                    $this->whereclause[] = "lower(" . $quotedfield . ") " . $evaluate . " lower('" . pg_escape_string($value) . "')";
                }  
            }
        }
    }
    
    protected function GetSQLlisting($orderby = '', $start = 0, $num = 0) {
        $sql = $this->sql_listing;
		
		$sql .= $this->GetWhereClause();		
		if (!array_key_exists($orderby, $this->fieldmap_orderby)) { 
            $sql .= " ORDER BY " . reset($this->fieldmap_orderby); 
        } else {
            $sql .= " ORDER BY " . $this->fieldmap_orderby[$orderby];
        }
		        
		if ($start != 0 || $num != 0) $sql .= " LIMIT " . $num . " OFFSET " . $start;

        return $sql;
    }
    
    public function GetRecords($orderby = '', $start = 0, $num = 0) {				
        $sql = $this->GetSQLlisting($orderby, $start, $num);
		$result = pg_query_params($sql, array());
        if (!$result) { echo "Error in GetRecords"; exit; }
        $res = array();
        while ($row = pg_fetch_array($result, null, PGSQL_ASSOC)) {
            $res[] = $row;
        }
		return $res;
	}
    
    public function GetRecordsCount() {
		$norecords = 0;
        $sql = $this->GetSQLlisting();
        //echo $sql;
		$sql = "SELECT count(*) as norecords FROM (" . $sql . ") as subquery_list"; /* since ordering etc doesn't affect number of prjs returned */
		//echo $sql;
		$result = pg_query_params($sql, array());
		if ($result) {
			$row = pg_fetch_array($result);
			$norecords = $row['norecords'];			
		}
		return $norecords;
	}
}
?>