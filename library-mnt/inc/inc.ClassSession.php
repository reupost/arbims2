<?php
/**
 * Implementation of a simple session management.
 *
 * SeedDMS uses its own simple session management, storing sessions
 * into the database. A session holds the currently logged in user,
 * the theme and the language.
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent a session
 *
 * This class provides some very basic methods to load, save and delete
 * sessions. It does not set or retrieve a cockie. This is up to the
 * application. The class basically provides access to the session database
 * table.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  2011 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Session {
	/**
	 * @var object $db reference to database object. This must be an instance
	 *      of {@link SeedDMS_Core_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var array $data session data
	 * @access protected
	 */
	protected $data;

	/**
	 * @var string $id session id
	 * @access protected
	 */
	protected $id;

	/**
	 * Create a new instance of the session handler
	 *
	 * @param object $db object to access the underlying database
	 * @return object instance of SeedDMS_Session
	 */
	function __construct($db) { /* begin function */
		$this->db = $db;
		$this->id = false;
	} /* end function */

	/**
	 * Load session by its id from database
	 *
	 * @param string $id id of session
	 * @return boolean true if successful otherwise false
	 */
	function load($id) { /* begin function */
		$queryStr = "SELECT * FROM tblSessions WHERE id = ".$this->db->qstr($id);
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) == 0)
			return false;
		$queryStr = "UPDATE tblSessions SET lastAccess = " . time() . " WHERE id = " . $this->db->qstr($id);
		if (!$this->db->getResult($queryStr))
			return false;
		$this->id = $id;
		$this->data = array('userid'=>$resArr[0]['userID'], 'theme'=>$resArr[0]['theme'], 'lang'=>$resArr[0]['language'], 'id'=>$resArr[0]['id'], 'lastaccess'=>$resArr[0]['lastAccess'], 'su'=>$resArr[0]['su']);
		if($resArr[0]['clipboard'])
			$this->data['clipboard'] = json_decode($resArr[0]['clipboard'], true);
		else
			$this->data['clipboard'] = array('docs'=>array(), 'folders'=>array());
		if($resArr[0]['splashmsg'])
			$this->data['splashmsg'] = json_decode($resArr[0]['splashmsg'], true);
		else
			$this->data['splashmsg'] = array();
		return $resArr[0];
	} /* end function */

	/**
	 * Create a new session and saving the given data into the database
	 *
	 * @param array $data data saved in session (the only fields supported
	 *        are userid, theme, language, su)
	 * @return string/boolean id of session of false in case of an error
	 */
	function create($data) { /* begin function */
		$id = "" . rand() . time() . rand() . "";
		$id = md5($id);
		$lastaccess = time();
		$queryStr = "INSERT INTO tblSessions (id, userID, lastAccess, theme, language, su) ".
		  "VALUES ('".$id."', ".$data['userid'].", ".$lastaccess.", '".$data['theme']."', '".$data['lang']."', 0)";
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		$this->id = $id;
		$this->data = $data;
		$this->data['id'] = $id;
		$this->data['lastaccess'] = $lastaccess;
		$this->data['su'] = 0;
		$this->data['clipboard'] = array('docs'=>array(), 'folders'=>array());
		$this->data['clipboard'] = array('type'=>'', 'msg'=>'');
		$this->data['splashmsg'] = array();
		return $id;
	} /* end function */

	/**
	 * Delete sessions older than a given time from the database
	 *
	 * @param integer $sec maximum number of seconds a session may live
	 * @return boolean true if successful otherwise false
	 */
	function deleteByTime($sec) { /* begin function */
		$queryStr = "DELETE FROM tblSessions WHERE " . time() . " - lastAccess > ".$sec;
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		return true;
	} /* end function */

	/**
	 * Delete session by its id
	 *
	 * @param string $id id of session
	 * @return boolean true if successful otherwise false
	 */
	function delete($id) { /* begin function */
		$queryStr = "DELETE FROM tblSessions WHERE id = " . $this->db->qstr($id);
		if (!$this->db->getResult($queryStr)) {
			return false;
		}
		$this->id = false;
		return true;
	} /* end function */

	/**
	 * Get session id
	 *
	 * @return string session id
	 */
	function getId() { /* begin function */
		return $this->id;
	} /* end function */

	/**
	 * Set user of session
	 *
	 * @param integer $userid id of user
	 */
	function setUser($userid) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET userID = " . $this->db->qstr($userid) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['userid'] = $userid;	
		}
		return true;
	} /* end function */

	/**
	 * Set language of session
	 *
	 * @param string $lang language
	 */
	function setLanguage($lang) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET language = " . $this->db->qstr($lang) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['lang'] = $lang;	
		}
		return true;
	} /* end function */

	/**
	 * Get language of session
	 *
	 * @return string language
	 */
	function getLanguage() { /* begin function */
		return $this->data['lang'];
	} /* end function */

	/**
	 * Substitute user of session
	 *
	 * @param integer $su user id
	 */
	function setSu($su) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET su = " . (int) $su . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['su'] = (int) $su;	
		}
		return true;
	} /* end function */

	/**
	 * Reset substitute user of session
	 *
	 * @param integer $su user id
	 */
	function resetSu() { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET su = 0 WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['su'] = 0;	
		}
		return true;
	} /* end function */

	/**
	 * Get substituted user id of session
	 *
	 * @return integer substituted user id
	 */
	function getSu() { /* begin function */
		return $this->data['su'];
	} /* end function */

	/**
	 * Set clipboard of session
	 *
	 * @param array $clipboard list of folders and documents
	 */
	function setClipboard($clipboard) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET clipboard = " . $this->db->qstr(json_encode($clipboard)) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['clipboard'] = $clipboard;	
		}
		return true;
	} /* end function */

	/**
	 * Get clipboard of session
	 *
	 * @return array list of clipboard entries
	 */
	function getClipboard() { /* begin function */
		return (array) $this->data['clipboard'];
	} /* end function */

	/**
	 * Add to clipboard of session
	 *
	 * @param object $object Document or folder
	 */
	function addToClipboard($object) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			if(get_class($object) == 'SeedDMS_Core_Document') {
				if(!in_array($object->getID(), $this->data['clipboard']['docs']))
					array_push($this->data['clipboard']['docs'], $object->getID());
			} elseif(get_class($object) == 'SeedDMS_Core_Folder') {
				if(!in_array($object->getID(), $this->data['clipboard']['folders']))
					array_push($this->data['clipboard']['folders'], $object->getID());
			}
			$queryStr = "UPDATE tblSessions SET clipboard = " . $this->db->qstr(json_encode($this->data['clipboard'])) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
		}
		return true;
	} /* end function */

	/**
	 * Remove from clipboard
	 *
	 * @param object $object Document or folder to remove
	 */
	function removeFromClipboard($object) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			if(get_class($object) == 'SeedDMS_Core_Document') {
				$key = array_search($object->getID(), $this->data['clipboard']['docs']);
				if($key !== false)
					unset($this->data['clipboard']['docs'][$key]);
			} elseif(get_class($object) == 'SeedDMS_Core_Folder') {
				$key = array_search($object->getID(), $this->data['clipboard']['folders']);
				if($key !== false)
					unset($this->data['clipboard']['folders'][$key]);
			}
			$queryStr = "UPDATE tblSessions SET clipboard = " . $this->db->qstr(json_encode($this->data['clipboard'])) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
		}
		return true;
	} /* end function */

	/**
	 * Clear clipboard
	 *
	 */
	function clearClipboard() { /* begin function */
		$this->data['clipboard']['docs'] = array();
		$this->data['clipboard']['folders'] = array();
		$queryStr = "UPDATE tblSessions SET clipboard = " . $this->db->qstr(json_encode($this->data['clipboard'])) . " WHERE id = " . $this->db->qstr($this->id);
		if (!$this->db->getResult($queryStr))
			return false;
		return true;
	} /* end function */

	/**
	 * Set splash message of session
	 *
	 * @param array $msg contains 'typ' and 'msg'
	 */
	function setSplashMsg($msg) { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET splashmsg = " . $this->db->qstr(json_encode($msg)) . " WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['splashmsg'] = $msg;	
		}
		return true;
	} /* end function */

	/**
	 * Set splash message of session
	 *
	 * @param array $msg contains 'typ' and 'msg'
	 */
	function clearSplashMsg() { /* begin function */
		/* id is only set if load() was called before */
		if($this->id) {
			$queryStr = "UPDATE tblSessions SET splashmsg = '' WHERE id = " . $this->db->qstr($this->id);
			if (!$this->db->getResult($queryStr))
				return false;
			$this->data['splashmsg'] = '';	
		}
		return true;
	} /* end function */

	/**
	 * Get splash message of session
	 *
	 * @return array last splash message
	 */
	function getSplashMsg() { /* begin function */
		return (array) $this->data['splashmsg'];
	} /* end function */

}
?>
