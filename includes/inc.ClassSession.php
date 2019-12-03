<?php
/**
 * Implementation of a simple session management.
 *
 * Store sessions
 * into the database. A session holds the currently logged in user,
 * the theme and the language.
 */

/**
 * Class to represent a session
 *
 * This class provides some very basic methods to load, save and delete
 * sessions. It does not set or retrieve a cookie. This is up to the
 * application. The class basically provides access to the session database
 * table.
 */

class ARBMIS_Session {
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
        $resArr = pg_query_params("SELECT * FROM tblSessions WHERE id = $1", array($id));
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) == 0)
			return false;
		$this->id = $id;
		$this->data = array('userid'=>$resArr[0]['userID'], 'lang'=>$resArr[0]['language'], 'id'=>$resArr[0]['id']);		
		return $resArr[0];
	} /* end function */

	/**
	 * Create a new session and saving the given data into the database
	 *
	 * @param array $data data saved in session (the only fields supported
	 *        are userid, language)
	 * @return string/boolean id of session of false in case of an error
	 */
	function create($data) { /* begin function */
		$id = "" . rand() . time() . rand() . "";
		$id = md5($id);
		$queryStr = "INSERT INTO tblSessions (id, userID, language) ".
		  "VALUES ('".$id."', ".$data['userid'].", '".$data['lang']."')";
        $resArr = pg_query_params($queryStr, array());
		if (is_bool($resArr) && $resArr == false)
			return false;
		$this->id = $id;
		$this->data = $data;
		$this->data['id'] = $id;
		return $id;
	} /* end function */

	/**
	 * Delete sessions older than a given time from the database
	 *
	 * @param integer $sec maximum number of seconds a session may live
	 * @return boolean true if successful otherwise false
	 */
    //TODO re-enable this
	//function deleteByTime($sec) { /* begin function */
	//	$queryStr = "DELETE FROM tblSessions WHERE " . time() . " - lastAccess > ".$sec;
	//	if (!$this->db->getResult($queryStr)) {
	//		return false;
	//	}
	//	return true;
	//} /* end function */

	/**
	 * Delete session by its id
	 *
	 * @param string $id id of session
	 * @return boolean true if successful otherwise false
	 */
	function delete($id) { /* begin function */		
        $res = pg_query_params("DELETE FROM tblSessions WHERE id = $1", array($id));
		if (is_bool($res) && $res == false)
			return false;
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
            $res = pg_query_params("UPDATE tblSessions SET userID = $1 WHERE id = $2", array($userid, $this->id));
			if (is_bool($res) && $res == false)
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
            $res = pg_query_params("UPDATE tblSessions SET language = $1 WHERE id = $2", array($lang, $this->id));
			if (is_bool($res) && $res == false)
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

	
}
?>
