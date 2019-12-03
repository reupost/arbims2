<?php
/**
 * Implementation of the workflow object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */

/**
 * Class to represent an workflow in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow { /* begin function */
	/**
	 * @var integer id of workflow
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var name of the workflow
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var initial state of the workflow
	 *
	 * @access protected
	 */
	var $_initstate;

	/**
	 * @var name of the workflow state
	 *
	 * @access protected
	 */
	var $_transitions;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow($id, $name, $initstate) { /* begin function */
		$this->_id = $id;
		$this->_name = $name;
		$this->_initstate = $initstate;
		$this->_transitions = null;
		$this->_dms = null;
	} /* end function */

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflows SET name = ".$db->qstr($newName)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* end function */

	function getInitState() { return $this->_initstate; }

	function setInitState($state) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflows SET initstate = ".$state->getID()." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_initstate = $state;
		return true;
	} /* end function */

	function getTransitions() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->_transitions)
			return $this->_transitions;

		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE workflow=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$resArr[$i]["id"]] = $wkftransition;
		}

		$this->_transitions = $wkftransitions;

		return $this->_transitions;
	} /* end function */

	function getStates() { /* begin function */
		$db = $this->_dms->getDB();

		if(!$this->_transitions)
			$this->getTransitions();

		$states = array();
		foreach($this->_transitions as $transition) {
			if(!isset($states[$transition->getState()->getID()]))
				$states[$transition->getState()->getID()] = $transition->getState();
			if(!isset($states[$transition->getNextState()->getID()]))
				$states[$transition->getNextState()->getID()] = $transition->getNextState();
		}

		return $states;
	} /* end function */

	/**
	 * Get the transition by its id
	 *
	 * @param integer $id id of transition
	 * @param object transition
	 */
	function getTransition($id) { /* begin function */
		$db = $this->_dms->getDB();

		if(!$this->_transitions)
			$this->getTransitions();

		if($this->_transitions[$id])
			return $this->_transitions[$id];

		return false;
	} /* end function */

	/**
	 * Get the transitions that can be triggered while being in the given state
	 *
	 * @param object $state current workflow state
	 * @param array list of transitions
	 */
	function getNextTransitions($state) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE workflow=".$this->_id." AND state=".$state->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* end function */

	/**
	 * Get the transitions that lead to the given state
	 *
	 * @param object $state current workflow state
	 * @param array list of transitions
	 */
	function getPreviousTransitions($state) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE workflow=".$this->_id." AND nextstate=".$state->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* end function */

	/**
	 * Get all transitions from one state into another state
	 *
	 * @param object $state state to start from
	 * @param object $nextstate state after transition
	 * @param array list of transitions
	 */
	function getTransitionsByStates($state, $nextstate) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE workflow=".$this->_id." AND state=".$state->getID()." AND nextstate=".$nextstate->getID();
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkftransitions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$wkftransition = new SeedDMS_Core_Workflow_Transition($resArr[$i]["id"], $this, $this->_dms->getWorkflowState($resArr[$i]["state"]), $this->_dms->getWorkflowAction($resArr[$i]["action"]), $this->_dms->getWorkflowState($resArr[$i]["nextstate"]), $resArr[$i]["maxtime"]);
			$wkftransition->setDMS($this->_dms);
			$wkftransitions[$i] = $wkftransition;
		}

		return $wkftransitions;
	} /* end function */

	/**
	 * Remove a transition from a workflow
	 * Deprecated! User SeedDMS_Core_Workflow_Transition::remove() instead.
	 *
	 * @param object $transition
	 * @return boolean true if no error occured, otherwise false
	 */
	function removeTransition($transition) { /* begin function */
		return $transition->remove();
	} /* end function */

	/**
	 * Add new transition to workflow
	 *
	 * @param object $state 
	 * @param object $action 
	 * @param object $nextstate 
	 * @param array $users 
	 * @param array $groups 
	 * @return object instance of new transition
	 */
	function addTransition($state, $action, $nextstate, $users, $groups) { /* begin function */
		$db = $this->_dms->getDB();
		
		$db->startTransaction();
		$queryStr = "INSERT INTO tblWorkflowTransitions (workflow, state, action, nextstate) VALUES (".$this->_id.", ".$state->getID().", ".$action->getID().", ".$nextstate->getID().")";
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$transition = $this->getTransition($db->getInsertID());

		foreach($users as $user) {
			$queryStr = "INSERT INTO tblWorkflowTransitionUsers (transition, userid) VALUES (".$transition->getID().", ".$user->getID().")";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		foreach($groups as $group) {
			$queryStr = "INSERT INTO tblWorkflowTransitionGroups (transition, groupid, minusers) VALUES (".$transition->getID().", ".$group->getID().", 1)";
			if (!$db->getResult($queryStr)) {
				$db->rollbackTransaction();
				return false;
			}
		}

		$db->commitTransaction();
		return $transition;
	} /* end function */

	/**
	 * Check if workflow is currently used by any document
	 *
	 * @return boolean true if workflow is used, otherwise false
	 */
	function isUsed() { /* begin function */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblWorkflowDocumentContent WHERE workflow=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* end function */

	/**
	 * Remove the workflow and all its transitions
	 * Do not remove actions and states of the workflow
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow is currently in use
	 */
	function remove() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		$queryStr = "DELETE FROM tblWorkflowTransitions WHERE workflow = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$queryStr = "DELETE FROM tblWorkflowMandatoryWorkflow WHERE workflow = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		// Delete workflow itself
		$queryStr = "DELETE FROM tblWorkflows WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* end function */

} /* end function */

/**
 * Class to represent a workflow state in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_State { /* begin function */
	/**
	 * @var integer id of workflow state
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var name of the workflow state
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var maximum of seconds allowed in this state
	 *
	 * @access protected
	 */
	var $_maxtime;

	/**
	 * @var maximum of seconds allowed in this state
	 *
	 * @access protected
	 */
	var $_precondfunc;

	/**
	 * @var matching documentstatus when this state is reached
	 *
	 * @access protected
	 */
	var $_documentstatus;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_State($id, $name, $maxtime, $precondfunc, $documentstatus) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_maxtime = $maxtime;
		$this->_precondfunc = $precondfunc;
		$this->_documentstatus = $documentstatus;
		$this->_dms = null;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowStates SET name = ".$db->qstr($newName)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* end function */

	function getMaxTime() { return $this->_maxtime; }

	function setMaxTime($maxtime) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowStates SET maxtime = ".intval($maxtime)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxtime = $maxtime;
		return true;
	} /* end function */

	function getPreCondFunc() { return $this->_precondfunc; }

	function setPreCondFunc($precondfunc) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowStates SET precondfunc = ".$db->qstr($precondfunc)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxtime = $maxtime;
		return true;
	} /* end function */

	/**
	 * Get the document status which is set when this state is reached
	 *
	 * The document status uses the define states S_REJECTED and S_RELEASED
	 * Only those two states will update the document status
	 *
	 * @return integer document status
	 */
	function getDocumentStatus() { return $this->_documentstatus; }

	function setDocumentStatus($docstatus) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowStates SET documentstatus = ".intval($docstatus)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_documentstatus = $docstatus;
		return true;
	} /* end function */

	/**
	 * Check if workflow state is currently used by any workflow transition
	 *
	 * @return boolean true if workflow is used, otherwise false
	 */
	function isUsed() { /* begin function */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE state=".$this->_id. " OR nextstate=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* end function */

	/**
	 * Remove the workflow state
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow state is currently in use
	 */
	function remove() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		// Delete workflow state itself
		$queryStr = "DELETE FROM tblWorkflowStates WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* end function */

} /* end function */

/**
 * Class to represent a workflow action in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_Action { /* begin function */
	/**
	 * @var integer id of workflow action
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var name of the workflow action
	 *
	 * @access protected
	 */
	var $_name;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_Action($id, $name) {
		$this->_id = $id;
		$this->_name = $name;
		$this->_dms = null;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getName() { return $this->_name; }

	function setName($newName) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowActions SET name = ".$db->qstr($newName)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $newName;
		return true;
	} /* end function */

	/**
	 * Check if workflow action is currently used by any workflow transition
	 *
	 * @return boolean true if workflow action is used, otherwise false
	 */
	function isUsed() { /* begin function */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE action=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0)
			return false;
		return true;
	} /* end function */

	/**
	 * Remove the workflow action
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow action is currently in use
	 */
	function remove() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		$db->startTransaction();

		// Delete workflow state itself
		$queryStr = "DELETE FROM tblWorkflowActions WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* end function */

} /* end function */

/**
 * Class to represent a workflow transition in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_Transition { /* begin function */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var workflow this transition belongs to
	 *
	 * @access protected
	 */
	var $_workflow;

	/**
	 * @var state of the workflow transition
	 *
	 * @access protected
	 */
	var $_state;

	/**
	 * @var next state of the workflow transition
	 *
	 * @access protected
	 */
	var $_nextstate;

	/**
	 * @var action of the workflow transition
	 *
	 * @access protected
	 */
	var $_action;

	/**
	 * @var maximum of seconds allowed until this transition must be triggered
	 *
	 * @access protected
	 */
	var $_maxtime;

	/**
	 * @var list of users allowed to trigger this transaction
	 *
	 * @access protected
	 */
	var $_users;

	/**
	 * @var list of groups allowed to trigger this transaction
	 *
	 * @access protected
	 */
	var $_groups;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_Transition($id, $workflow, $state, $action, $nextstate, $maxtime) {
		$this->_id = $id;
		$this->_workflow = $workflow;
		$this->_state = $state;
		$this->_action = $action;
		$this->_nextstate = $nextstate;
		$this->_maxtime = $maxtime;
		$this->_dms = null;
	}

	function setDMS($dms) {
		$this->_dms = $dms;
	}

	function getID() { return $this->_id; }

	function getWorkflow() { return $this->_workflow; }

	function setWorkflow($newWorkflow) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowTransitions SET workflow = ".$newWorkflow->getID()." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_workflow = $newWorkflow;
		return true;
	} /* end function */

	function getState() { return $this->_state; }

	function setState($newState) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowTransitions SET state = ".$newState->getID()." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_state = $newState;
		return true;
	} /* end function */

	function getNextState() { return $this->_nextstate; }

	function setNextState($newNextState) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowTransitions SET nextstate = ".$newNextState->getID()." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_nextstate = $newNextState;
		return true;
	} /* end function */

	function getAction() { return $this->_action; }

	function setAction($newAction) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowTransitions SET action = ".$newAction->getID()." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_action = $newAction;
		return true;
	} /* end function */

	function getMaxTime() { return $this->_maxtime; }

	function setMaxTime($maxtime) { /* begin function */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblWorkflowTransitions SET maxtime = ".intval($maxtime)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxtime = $maxtime;
		return true;
	} /* end function */

	/**
	 * Get all users allowed to trigger this transition
	 *
	 * @return array list of users
	 */
	function getUsers() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->_users)
			return $this->_users;

		$queryStr = "SELECT * FROM tblWorkflowTransitionUsers WHERE transition=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$users = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$user = new SeedDMS_Core_Workflow_Transition_User($resArr[$i]['id'], $this, $this->_dms->getUser($resArr[$i]['userid']));
			$user->setDMS($this->_dms);
			$users[$i] = $user;
		}

		$this->_users = $users;

		return $this->_users;
	} /* end function */

	/**
	 * Get all users allowed to trigger this transition
	 *
	 * @return array list of users
	 */
	function getGroups() { /* begin function */
		$db = $this->_dms->getDB();

		if($this->_groups)
			return $this->_groups;

		$queryStr = "SELECT * FROM tblWorkflowTransitionGroups WHERE transition=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;

		$groups = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$group = new SeedDMS_Core_Workflow_Transition_Group($resArr[$i]['id'], $this, $this->_dms->getGroup($resArr[$i]['groupid']), $resArr[$i]['minusers']);
			$group->setDMS($this->_dms);
			$groups[$i] = $group;
		}

		$this->_groups = $groups;

		return $this->_groups;
	} /* end function */

	/**
	 * Remove the workflow transition
	 *
	 * @return boolean true on success or false in case of an error
	 *         false is also returned if the workflow action is currently in use
	 */
	function remove() { /* begin function */
		$db = $this->_dms->getDB();

		$db->startTransaction();

		// Delete workflow transition itself
		$queryStr = "DELETE FROM tblWorkflowTransitions WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) {
			$db->rollbackTransaction();
			return false;
		}

		$db->commitTransaction();

		return true;
	} /* end function */

} /* end function */

/**
 * Class to represent a user allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_Transition_User { /* begin function */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object reference to the transtion this user belongs to
	 *
	 * @access protected
	 */
	var $_transition;

	/**
	 * @var object user allowed to trigger a transition
	 *
	 * @access protected
	 */
	var $_user;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 _Core_Workflow_Transition_Group
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_Transition_User($id, $transition, $user) {
		$this->_id = $id;
		$this->_transition = $transition;
		$this->_user = $user;
	}

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	/**
	 * Get the transtion itself
	 *
	 * @return object group
	 */
	function getTransition() { /* begin function */
		return $this->_transition;
	} /* end function */

	/**
	 * Get the user who is allowed to trigger the transition
	 *
	 * @return object user
	 */
	function getUser() { /* begin function */
		return $this->_user;
	} /* end function */
} /* end function */

/**
 * Class to represent a group allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_Transition_Group { /* begin function */
	/**
	 * @var integer id of workflow transition
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object reference to the transtion this group belongs to
	 *
	 * @access protected
	 */
	var $_transition;
	
	/**
	 * @var integer number of users how must trigger the transition
	 *
	 * @access protected
	 */
	var $_numOfUsers;

	/**
	 * @var object group of users
	 *
	 * @access protected
	 */
	var $_group;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_Transition_Group($id, $transition, $group, $numOfUsers) { /* begin function */
		$this->_id = $id;
		$this->_transition = $transition;
		$this->_group = $group;
		$this->_numOfUsers = $numOfUsers;
	} /* end function */

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	/**
	 * Get the transtion itself
	 *
	 * @return object group
	 */
	function getTransition() { /* begin function */
		return $this->_transition;
	} /* end function */

	/**
	 * Get the group whose user are allowed to trigger the transition
	 *
	 * @return object group
	 */
	function getGroup() { /* begin function */
		return $this->_group;
	} /* end function */

	/**
	 * Returns the number of users of this group needed to trigger the transition
	 *
	 * @return integer number of users
	 */
	function getNumOfUsers() { /* begin function */
		return $this->_numOfUsers;
	} /* end function */

} /* end function */

/**
 * Class to represent a group allowed to trigger a workflow transition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: 4.3.8
 */
class SeedDMS_Core_Workflow_Log { /* begin function */
	/**
	 * @var integer id of workflow log
	 *
	 * @access protected
	 */
	var $_id;

	/**
	 * @var object document this log entry belongs to
	 *
	 * @access protected
	 */
	var $_document;

	/**
	 * @var integer version of document this log entry belongs to
	 *
	 * @access protected
	 */
	var $_version;

	/**
	 * @var object workflow
	 *
	 * @access protected
	 */
	var $_workflow;

	/**
	 * @var object user initiating this log entry
	 *
	 * @access protected
	 */
	var $_user;

	/**
	 * @var object transition
	 *
	 * @access protected
	 */
	var $_transition;

	/**
	 * @var string date
	 *
	 * @access protected
	 */
	var $_date;

	/**
	 * @var string comment
	 *
	 * @access protected
	 */
	var $_comment;

	/**
	 * @var object reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	var $_dms;

	function SeedDMS_Core_Workflow_Log($id, $document, $version, $workflow, $user, $transition, $date, $comment) {
		$this->_id = $id;
		$this->_document = $document;
		$this->_version = $version;
		$this->_workflow = $workflow;
		$this->_user = $user;
		$this->_transition = $transition;
		$this->_date = $date;
		$this->_comment = $comment;
		$this->_dms = null;
	}

	function setDMS($dms) { /* begin function */
		$this->_dms = $dms;
	} /* end function */

	function getTransition() { /* begin function */
		return $this->_transition;
	} /* end function */

	function getUser() { /* begin function */
		return $this->_user;
	} /* end function */

	function getComment() { /* begin function */
		return $this->_comment;
	} /* end function */

	function getDate() { /* begin function */
		return $this->_date;
	} /* end function */

} /* end function */
?>
