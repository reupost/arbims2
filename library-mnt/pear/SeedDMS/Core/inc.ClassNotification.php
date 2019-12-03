<?php
/**
 * Implementation of a notification object
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010 Uwe Steinmann
 * @version    Release: 4.3.8
 */

/**
 * Class to represent a notification
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Notification { /* begin function */
	/**
	 * @var integer id of target (document or folder)
	 *
	 * @access protected
	 */
	protected $_target;

	/**
	 * @var integer document or folder
	 *
	 * @access protected
	 */
	protected $_targettype;

	/**
	 * @var integer id of user to notify
	 *
	 * @access protected
	 */
	protected $_userid;

	/**
	 * @var integer id of group to notify
	 *
	 * @access protected
	 */
	protected $_groupid;

	/**
	 * @var object reference to the dms instance this user belongs to
	 *
	 * @access protected
	 */
	protected $_dms;

	function SeedDMS_Core_Notification($target, $targettype, $userid, $groupid) { /* begin function */
		$this->_target = $target;
		$this->_targettype = $targettype;
		$this->_userid = $userid;
		$this->_groupid = $groupid;
	} /* end function */

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	function getTarget() { return $this->_target; }

	function getTargetType() { return $this->_targettype; }

	function getUser() { return $this->_dms->getUser($this->_userid); }

	function getGroup() { return $this->_dms->getGroup($this->_groupid); }
} /* end function */
?>
