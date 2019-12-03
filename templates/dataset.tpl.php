<legend><?php echo getMLtext('dataset') . ': ' . htmlspecialchars($dsdata['title']) ?></legend>
<div class="row-fluid">
    <div class="span12">     
        <a href='<?php if ($region > "") { echo "out.listdatasets." . $region . ".php"; } else { echo "out.index.php"; } ?>'
            alt="<?php printMLtext('dataset_list') ?>" title="<?php printMLtext('dataset_list') ?>"><img src='images/arrow-left-green.png' height='16px' width='16px' border='0px' style='padding-right:4px;padding-bottom:10px'><?php printMLtext('dataset_list') ?></a>
        <div class='help_prompt'><?php printMLtext('arbmis_dataset') ?></div>
        <div id="datasetfieldlist">
            <table id="dataset">
                <tbody>
                    <?php                    
                    if ($user['siterole'] == 'admin') {
                        echo "<tr><td></td><td>";
                        echo "<input type='button' value=\"" . getMLtext('edit') . "\" onclick=\"javascript:window.location='out.dataset_edit.php?datasetid=" . $dsdata['datasetid'] . "&region=" . $region . "'\"/>";
                        echo "</td></tr>";                        
                    }
                    $firstregion = 1;
                    foreach ($dslist as $field) {                        
                        echo "<tr>";
                        if (substr($field, 0, strlen('region_')) == 'region_') {
                            if ($firstregion) {
                                echo "<td colspan='2'>&nbsp;</td></tr>";
                                echo "<tr><td colspan='2'><b>" . getMLtext('regional_specificity') . ":</b></td></tr>";
                                echo "<tr>";
                                $firstregion = 0;
                            }
                            echo "<td><b>" . getMLtext($field) . "</b></td>";
                        } else {
                            echo "<td><b>" . getMLtext('dataset_' . $field) . "</b></td>";
                        }
                        echo "<td>";
                        if (in_array($field, array('link', 'dwca', 'eml'))) { //IPT link
                            if ($dsdata[$field] > '') {
                                echo "<a href='" . $dsdata[$field] . "' title='" . getMLtext('dataset_' . $field) . "' alt='" . getMLtext('dataset_' . $field) . "'>" . htmlspecialchars($dsdata[$field]) . "</a>";
                            }
                        } else {
                            if ($field == 'color') {
                                if ($dsdata['_has_occurrence'] == 't') {
                                    echo "<div class='color_show' style='background-color:" . $dsdata['color'] . ";'></div>";                                    
                                } else {
                                    printMLtext('color_only_for_occurrence_datasets');
                                }
                            } elseif (substr($field, 0, strlen('region_')) == 'region_') {
                                if ($dsdata[$field] == 't') {
                                    printMLtext('yes');
                                } else {
                                    printMLtext('no');
                                }
                            } else {
                                echo htmlspecialchars($dsdata[$field]);
                            }
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <br/>
    </div>                    
</div>
