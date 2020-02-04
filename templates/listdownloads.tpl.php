<legend><?php echo getMLtext('download_list') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <a href='out.admin.php' alt="<?php printMLText('admin_tools') ?>" title="<?php printMLText('admin_tools') ?>"><img src='images/arrow-left-green.png' height='16px' width='16px' border='0px' style='padding-right:4px;padding-bottom:10px'><?php printMLText('admin_tools') ?></a>

        <div class='help_prompt'><?php printMLtext('arbmis_listdownloads') ?></div>
        <?php echo $pageopts ?>
        <a name="listanchor"></a>
        <?php echo $pageform ?>
        <div id="downloadlist">
            <?php echo $pager->ShowPageItems() ?>
            <?php echo $pager->ShowBrowseControls(getMLtext('downloads')); ?>
        </div>
        <br/>
    </div>
</div>