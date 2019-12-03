<legend><?php printMLtext('map_layers') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_listgislayers') ?></div>
        <?php echo $pageopts ?>
        <div class="page_other_functionality">
            <?php if ($user['siterole'] == 'admin'): ?>
                <form action="op.refresh_from_geoserver.php" >
                    <input type='submit' value="<?php printMLtext('synchronise_with_geoserver') ?>" />
                </form>
            <?php endif; ?>
        </div>
        <a name="listanchor"></a>
        <?php echo $pageform ?>
        <div id="layerslist">
            <?php echo $pager->ShowPageItems() ?>
            <?php echo $pager->ShowBrowseControls(getMLtext('map_layers')); ?>
        </div>
        <br/>
    </div>                    
</div>

