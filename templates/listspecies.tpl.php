<legend><?php echo getMLtext('species_list') . ': ' . getMLtext('region_' . $region) ?></legend>
<?php if (isset($params['dataset_title'])) echo "<h5>" . $params['dataset_title'] . "</h5>" ?>
<?php if (isset($params['taxon_title'])) echo "<h5>" . $params['taxon_title'] . "</h5>" ?>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_listspecies') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <?php echo $pageopts ?>
        <div class="page_other_functionality">            
            <input type='button' value="<?php printMLtext('download') ?>" onclick="javascript:DownloadData();" 
                <?php if ($user['id'] != 0): ?>
                    <?php if ($norecords <= $maxrecords_download && $norecords > 0): ?>
                        <?php /* can be downloaded */ ?>
                    <?php else: ?>
                        <?php echo " disabled='disabled' alt=\"" . getMLtext('download_species_too_many',array("maxrecs"=>$maxrecords_download)) . "\" title=\"" . getMLtext('download_species_too_many',array("maxrecs"=>$maxrecords_download)) . "\"" ?> 
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo " disabled='disabled' alt=\"" . getMLtext('logged_in_users_only') . "\" title=\"" . getMLtext('logged_in_users_only') . "\"" ?>
                <?php endif; ?>
            />
        </div>
        <a name="listanchor"></a>
        <?php echo $pageform ?>
        <div id="specieslist">
            <?php echo $pager->ShowPageItems() ?>
            <?php echo $pager->ShowBrowseControls(getMLtext('taxon_records')); ?>
        </div>
        <br/>
    </div>                    
</div>

