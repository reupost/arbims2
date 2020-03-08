<legend><?php echo getMLtext('dataset_list') . ': ' . getMLtext('region_' . $region) ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_listdatasets') ?></div>
        <?php echo $pageopts ?>

        <a name="listanchor"></a>
        <?php echo $pageform ?>
        <div id="datasetlist">
            <?php echo $pager->ShowPageItems() ?>
            <?php echo $pager->ShowBrowseControls(getMLtext('datasets')); ?>
        </div>
        <br/>
    </div>
</div>

