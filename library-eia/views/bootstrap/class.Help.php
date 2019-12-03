<?php
/**
 * Implementation of Help view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Help view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Help extends SeedDMS_Bootstrap_Style {

	function show() { /* begin function */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$this->htmlStartPage(getMLText("help"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("help"), "");

		$this->contentContainerStart();

		//RR mod to allow for shared language path
		if ($settings->_sharedLangPath != "") {
			include $settings->_sharedLangPath . "/" . $settings->_language . "/lang.inc";
			readfile($settings->_sharedLangPath . "/" . $this->params['session']->getLanguage()."/help.htm");
		} else {
			readfile("../languages/".$this->params['session']->getLanguage()."/help.htm");
		}
		

		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* end function */
}
?>
