<legend><?php echo getMLtext('occurrence_list') . ': ' . getMLtext('region_' . $region) ?></legend>
<?php if (isset($params['dataset_title'])) echo "<h5>" . $params['dataset_title'] . "</h5>" ?>
<?php if (isset($params['taxon_title'])) echo "<h5>" . $params['taxon_title'] . "</h5>" ?>
<?php if ($params['bounding_box'] > '') echo "<h5>" . $params['bounding_box'] . "</h5>" ?>
<?php if ($occlist_criteria > '') echo "<h5>" . $occlist_criteria . "</h5>" ?>
<?php if ($adv_criteria > '') echo "<h5>" . getMLtext('advanced_search_criteria') . ":</h5><div class='well'>" . $adv_criteria . "</div>"  ?>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_listoccurrence') ?></div>
        <?php if (isset($session_msg)) {
                    if ($session_msg['state'] != 'success')
                        echo "<div class='message'>" . $session_msg['msg'] . "</div>";
                }
        ?>
        <?php echo $pageopts ?>
        <div class="page_adv_search">
            
        </div>
        <div class="page_other_functionality">
            <input type='button' value='<?php printMLtext('advanced_search') ?>' onclick="javascript:window.location='out.searchoccurrence.php?region=<?php echo $region ?>'"/>
            <input type='button' value='<?php printMLtext('search_using_map') ?>' onclick="javascript:window.location='out.map.<?php echo $region ?>.php'"/>
            <input type='button' value="<?php printMLtext('download') ?>" onclick="javascript:DownloadData();" 
                <?php if ($user['id'] != 0): ?>
                    <?php if ($norecords <= $maxrecords_download && $norecords > 0): ?>
                        <?php /* can be downloaded */ ?>
                    <?php else: ?>
                        <?php echo " disabled='disabled' alt=\"" . getMLtext('download_occurrences_too_many',array("maxrecs"=>$maxrecords_download)) . "\" title=\"" . getMLtext('download_occurrences_too_many',array("maxrecs"=>$maxrecords_download)) . "\"" ?> 
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo " disabled='disabled' alt=\"" . getMLtext('logged_in_users_only') . "\" title=\"" . getMLtext('logged_in_users_only') . "\"" ?>
                <?php endif; ?>
            />
            <input type='button' value="<?php printMLtext('map_occurrences') ?>" onclick="javascript:PassOccurrenceCriteriaToMap('<?php echo $region ?>')"
                <?php if ($norecords <= $maxrecords_map && $norecords > 0): ?>
                   <?php /* can be mapped */ ?>
                <?php else: ?>
                    <?php echo " disabled='disabled' alt=\"" . getMLtext('map_occurrences_too_many',array("maxrecs"=>$maxrecords_map)) . "\" title=\"" . getMLtext('map_occurrences_too_many',array("maxrecs"=>$maxrecords_map)) . "\"" ?>                        
                <?php endif; ?>
            />  
        </div>
        <a name="listanchor"></a>
        <?php echo $pageform ?>
        <div id="occurrencelist">
            <?php echo $pager->ShowPageItems() ?>
            <?php echo $pager->ShowBrowseControls(getMLtext('occurrence_records')); ?>
        </div>
        <br/>
    </div>                    
</div>

