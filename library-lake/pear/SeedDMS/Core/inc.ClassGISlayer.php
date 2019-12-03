<?php
/**
 * Implementation of map GIS layers in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Reuben Roberts <reupost@gmail.com>
 * @copyright  Copyright (C) 2014 Reuben Roberts
 * @version    Release: 4.3.8
 */

/**
 * Class to represent a map layer in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Reuben Roberts <reupost@gmail.com>
 * @copyright  Copyright (C) 2014 Reuben Roberts
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_GISlayer {
	/**
	 * @var integer $_id id of gis layer
	 * @access protected
	 */
	protected $_id;

	/**
	 * @var string $_name name of gis layer
	 * @access protected
	 */
	protected $_name;

	/**
	 * @var object $_dms reference to dms this gis layer belongs to
	 * @access protected
	 */
	protected $_dms;

	function SeedDMS_Core_GISlayer($id, $name) { /* begin function */
		$this->_id = $id;
		$this->_name = $name;
		$this->_dms = null;
	} /* end function */

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* begin function */
		//not implemented: updated via external process
		return true;
	} /* end function */

	function isUsed() { /* begin function */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblDocuments WHERE linkedgislayer=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* end function */

	function getGISlayers() { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblgislayer";
		return $db->getResultArray($queryStr);
	} /* end function */

	function addGISlayer($layer) { /* begin function */
        //not implemented: updated via external process
		return true;
	} /* end function */

	function remove() { /* begin function */
		//not implemented: updated via external process
		return true;
	} /* end function */

	function getDocumentsByGISlayer() { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblDocuments where linkedgislayer=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		foreach ($resArr as $row) {
			array_push($documents, $this->_dms->getDocument($row["id"]));
		}
		return $documents;
	} /* end function */
    
}

class SeedDMS_Core_GISfeature {
	/**
	 * @var string $_fid id of gis layer feature
	 * @access protected
	 */
	protected $_fid;

    /**
	 * @var integer $_gislayer_id id of parent gis layer
	 * @access protected
	 */
	protected $_gislayer_id; 
    
	/**
	 * @var string $_attributes_concat = attribute key value pairs separated by ;
	 * @access protected
	 */
	protected $_attributes_concat;
    
    /**
	 * @var string $_description_text = description attribute value, if set
	 * @access protected
	 */
	protected $_description_text;

	/**
	 * @var object $_dms reference to dms this gis layer belongs to
	 * @access protected
	 */
	protected $_dms;

	function SeedDMS_Core_GISfeature($fid, $gislayer_id, $attributes_concat, $description_text) { /* begin function */
		$this->_fid = $fid;
		$this->_gislayer_id = $gislayer_id;
        $this->_attributes_concat = $attributes_concat;
        $this->_description_text = $description_text;
		$this->_dms = null;
	} /* end function */

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	function getFID() { return $this->_fid; }

    function getDisplayText() {
        if ($this->_description_text > '') return $this->_description_text;
        return (substr($this->_attributes_concat,0,100)); // this text can be very long
    }
    
	function getGISlayerID() { return $this->_gislayer_id; }

	function setGISlayerID($newGISlayerID) { /* begin function */
		//not implemented: updated via external process
		return true;
	} /* end function */

	function getGISfeatures($gislayer_id = 0) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblgislayer_feature";
        if ($gislayer_id > 0) $queryStr .= " WHERE gislayer_id = " . $gislayer_id;
		return $db->getResultArray($queryStr);
	} /* end function */

	function addGISfeature($feature) { /* begin function */
        //not implemented: updated via external process
		return true;
	} /* end function */

	function remove() { /* begin function */
		//not implemented: updated via external process
		return true;
	} /* end function */

	function getDocumentsByGISfeature() { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblDocuments where (linkedgislayer=".$this->_gislayerid . " AND linkedgisfeature = '" . $this->_fid . "')";
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$documents = array();
		foreach ($resArr as $row) {
			array_push($documents, $this->_dms->getDocument($row["id"]));
		}
		return $documents;
	} /* end function */
    
}
?>
