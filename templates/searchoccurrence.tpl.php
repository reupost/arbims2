<legend><?php echo getMLtext('occurrence_search') . ': ' . getMLtext('region_' . $region) ?></legend>
<div class="row-fluid">
    <div class="span12">
        <div class='help_prompt'><?php printMLtext('arbmis_searchoccurrence') ?></div>        
        <form action='out.listoccurrence.<?php echo $region ?>.php' method='GET'>            
            <table>
                <thead>
                    <tr style='text-align:left;padding-top:10px;padding-bottom:10px'>
                        <th><h5><?php printMLtext('field') ?></h5></th>
                        <th><h5><?php printMLtext('reported_values_and_rec_count') ?></h5></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($valueFields as $field => $vals) {
                        echo "<tr><td style='min-width:10em;'>" . getMLtext('occ_' . $field) . "</td>";
                        echo "<td><select name='" . htmlspecialchars($field) . "' id='" . htmlspecialchars($field) . "'>";
                        echo "<option value=''>--Any value--</option>";
                        foreach ($vals as $val) {
                            if ($val["value"] == '')
                                $val["value"] = "(blank)";
                            echo "<option value='" . htmlspecialchars($val["value"]) . "'>" . htmlspecialchars($val["value"]) . " (" . ($region>''? $val["numrecs_" . $region] : $val["numrecs"]) . ")</option>";
                        }
                        echo "</select></td></tr>";
                    }
                    ?>
                    <tr>
                        <td>
                        </td>
                        <td>
                            <input type="submit" value="<?php printMLtext('search') ?>">
                        </td>
                    </tr>
                </tbody>
            </table>
            
        </form>
    </div>                    
</div>
