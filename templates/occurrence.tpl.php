<legend><?php printMLtext('occurrence_record') ?></legend>
<?php if (isset($params['dataset_title'])) echo "<h5>" . $params['dataset_title'] . "</h5>" ?>
<?php if (isset($params['taxon_title'])) echo "<h5>" . $params['taxon_title'] . "</h5>" ?>
<div class="row-fluid">
    <div class="span12">        
        <div class='help_prompt'><?php printMLtext('arbmis_occurrence') ?></div>
        <div id="occurrencefieldlist">
            <table>                
                <thead>
                    <tr style='text-align:left;padding-top:10px;padding-bottom:10px'>
                        <th><h4><?php printMLtext('field') ?></h4></th>
                        <th><h4><?php printMLtext('value') ?></h4></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($occdata as $field) {
                        if ($field[0] == '_kingdom') { //start of calculated fields
                            echo "<tr><td colspan='2'>&nbsp;</td></tr>";
                            echo "<tr><td colspan='2'><b><u>" . getMLtext('supplementary_fields') . ":</u></b></td></tr>";
                        }
                        echo "<tr><td style='min-width:15em;'><b>";
                        echo htmlspecialchars($field[1]);
                        echo "</b></td><td>";
                        if ($field[0] == 'link') { //IPT link
                            echo "<a href='" . htmlspecialchars($field[2]) . "' title='" . getMLtext('ipt_link') . "' alt='" . getMLtext('ipt_link') . "'>" . htmlspecialchars($field[2]) . "</a>";
                        } else {
                            echo htmlspecialchars($field[2]);
                        }
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <br/>
    </div>                    
</div>
