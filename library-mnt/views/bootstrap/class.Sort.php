<?php
/**
 * Implementation of Sort view
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
 * Class which outputs the html page for Settings view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Sort extends SeedDMS_Bootstrap_Style {

	function show() { /* begin function */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("sort_by"));

?>

  <form action="../op/op.Sort.php" method="post" enctype="multipart/form-data" name="form0" >
  <input type="hidden" name="action" value="saveSettings" />
<?php
if(!is_writeable($settings->_configFilePath)) {
	print "<div class=\"alert alert-warning\">";
	echo "<p>".getMLText("settings_notwritable")."</p>";
	print "</div>";
}
?>

	<div class="tab-content">
	  <div class="tab-pane active" id="site">
<?php		$this->contentContainerStart(); ?>
    <table class="table-condensed">
      <tr title="<?php printMLText("settings_sortUsersInList_desc");?>">
        <td><?php printMLText("settings_sortUsersInList");?>:</td>
        <td>
          <SELECT name="sortUsersInList">
            <OPTION VALUE="" <?php if ($settings->_sortUsersInList=='') echo "SELECTED" ?> ><?php printMLText("settings_sortUsersInList_val_login");?></OPTION>
            <OPTION VALUE="fullname" <?php if ($settings->_sortUsersInList=='fullname') echo "SELECTED" ?> ><?php printMLText("settings_sortUsersInList_val_fullname");?></OPTION>
          </SELECT>
      </tr>
      <tr title="<?php printMLText("settings_sortFoldersDefault_desc");?>">
        <td><?php printMLText("settings_sortFoldersDefault");?>:</td>
        <td>
          <SELECT name="sortFoldersDefault">
            <OPTION VALUE="u" <?php if ($settings->_sortFoldersDefault=='') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_unsorted");?></OPTION>
            <OPTION VALUE="s" <?php if ($settings->_sortFoldersDefault=='s') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_sequence");?></OPTION>
            <OPTION VALUE="n" <?php if ($settings->_sortFoldersDefault=='n') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_name");?></OPTION>
          </SELECT>
      </tr>
    </table>
<?php		$this->contentContainerEnd(); ?>
  
  </div>
  </div>
<?php
if(is_writeable($settings->_configFilePath)) {
?>
  <button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save")?></button>
<?php
}
?>
	</form>


<?php
		$this->htmlEndPage();
	} /* end function */
}
?>