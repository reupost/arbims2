<?php
/**
 * Implementation of access restricitions
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to check certain access restrictions
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_AccessOperation {
	/**
	 * @var object $obj object being accessed
	 * @access protected
	 */
	private $obj;

	/**
	 * @var object $user user requesting the access
	 * @access protected
	 */
	private $user;

	/**
	 * @var object $settings SeedDMS Settings
	 * @access protected
	 */
	private $settings;

	function __construct($obj, $user, $settings) { /* begin function */
		$this->obj = $obj;
		$this->user = $user;
		$this->settings = $settings;
	} /* end function */

	/**
	 * Check if removal of version is allowed
	 *
	 * This check can only be done for documents. Removal of versions is
	 * only allowed if this is turned on in the settings and there are
	 * at least 2 versions avaiable. Everybody with write access on the
	 * document may delete versions. The admin may even delete a version
	 * even if is disallowed in the settings.
	 */
	function mayRemoveVersion() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$versions = $this->obj->getContent();
			if ((($this->settings->_enableVersionDeletion && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin() ) && (count($versions) > 1)) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if document status may be overwritten
	 *
	 * This check can only be done for documents. Overwriting the document
	 * status is
	 * only allowed if this is turned on in the settings and the current
	 * status is either 'releaѕed' or 'obsoleted'.
	 * The admin may even modify the status
	 * even if is disallowed in the settings.
	 */
	function mayOverwriteStatus() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE )) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if reviewers/approvers may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * reviewers/approvers is only allowed if version modification is turned on
	 * in the settings and the document is in 'draft review' status.  The
	 * admin may even set reviewers/approvers if is disallowed in the
	 * settings.
	 */
	function maySetReviewersApprovers() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && ($status["status"]==S_DRAFT_REV)) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if workflow may be edited
	 *
	 * This check can only be done for documents. Overwriting the document
	 * workflow is only allowed if version modification is turned on
	 * in the settings and the document is in it's initial status.  The
	 * admin may even set the workflow if is disallowed in the
	 * settings.
	 */
	function maySetWorkflow() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$workflow = $latestContent->getWorkflow();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) == M_ALL)) || $this->user->isAdmin()) && (!$workflow || ($workflow->getInitState()->getID() == $latestContent->getWorkflowState()->getID()))) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if expiration date may be set
	 *
	 * This check can only be done for documents. Setting the documents
	 * expiration date is only allowed if the document has not been obsoleted.
	 */
	function maySetExpires() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->obj->getAccessMode($this->user) == M_ALL) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if comment may be edited
	 *
	 * This check can only be done for documents. Setting the documents
	 * comment date is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditComment() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) >= M_READWRITE)) || $this->user->isAdmin()) && ($status["status"]!=S_OBSOLETE)) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if attributes may be edited
	 *
	 * Setting the object attributes
	 * is only allowed if version modification is turned on in
	 * the settings and the document has not been obsoleted.
	 * The admin may set the comment even if is
	 * disallowed in the settings.
	 */
	function mayEditAttributes() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			$workflow = $latestContent->getWorkflow();
			if ((($this->settings->_enableVersionModification && ($this->obj->getAccessMode($this->user) >= M_READWRITE)) || $this->user->isAdmin()) && ($status["status"]==S_DRAFT_REV || ($workflow && $workflow->getInitState()->getID() == $latestContent->getWorkflowState()->getID()))) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if document content may be reviewed
	 *
	 * Reviewing a document content is only allowed if the document was not
	 * obsoleted. There are other requirements which are not taken into
	 * account here.
	 */
	function mayReview() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ($status["status"]!=S_OBSOLETE) {
				return true;
			}
		}
		return false;
	} /* end function */

	/**
	 * Check if document content may be approved
	 *
	 * Approving a document content is only allowed if the document was not
	 * obsoleted. There are other requirements which are not taken into
	 * account here.
	 */
	function mayApprove() { /* begin function */
		if(get_class($this->obj) == 'SeedDMS_Core_Document') {
			$latestContent = $this->obj->getLatestContent();
			$status = $latestContent->getStatus();
			if ($status["status"]!=S_OBSOLETE) {
				return true;
			}
		}
		return false;
	} /* end function */
    
    /**
     * Check if document can be rated
     * 
     * Only by non-guest users; re-rating a document overwrites an existing users' rating of that doc (i.e. can only have one rating for a doc once)
     * 
     */
    //RR added
    function mayRate() {
        if(get_class($this->obj) == 'SeedDMS_Core_Document') {
            if ($this->user->isGuest()) return false;
            return true;
        }
        return false;
    }
}
?>
