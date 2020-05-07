<legend><?php printMLtext('map_layer') ?></legend>
<div class="row-fluid">
    <div class="span12">        
        <a href='out.listgislayers.php' alt="<?php printMLtext('map_layers') ?>" title="<?php printMLtext('map_layers') ?>"><img src='images/arrow-left-green.png' height='16px' width='16px' border='0px' style='padding-right:4px'><?php printMLtext('map_layers') ?></a>
        <div class='help_prompt'><?php printMLtext('arbmis_gislayer') ?></div>
        <div id="gislayerfieldlist">
            <table id="gislayer">
                <thead>
                <th class='td_left'></th>
                <th class='td_middle'></th>
                <th class='td_right'></th>
                </thead>
                <tr>
                    <td><h5><?php 
                        if ($layerdata['gislayer']['displayname'] != '') {
                            printMLtext($layerdata['gislayer']['displayname']);
                        } else { //no display name set
                            echo "[" . $layerdata['gislayer']['geoserver_name'] . "]";
                        }
                        ?></h5></td>
                    <td colspan="2" style='text-align:right'>
                        <?php if ($user['siterole'] == 'admin'): ?>
                            <input type='button' value="<?php printMLtext('edit') ?>" onclick="javascript:window.location='out.gislayer_edit.php?id=<?php echo $layerdata['gislayer']['id'] ?>'"/>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td rowspan="5"><?php echo $layerdata['preview_img'] ?></td>
                    <td><b><?php printMLtext('when_added') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['dateadded'] ?></td>
                </tr>
                <tr>
                    <td style="vertical-align:top"><b><?php printMLtext('layer_meta_description') ?></b></td>
                    <td><?php echo nl2br($layerdata['gislayer']['meta_description']) ?></td>
                </tr>
                <tr>
                    <td colspan='2'><h5><?php printMLtext('layer_meta_source_title') ?>:</h5></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_source') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_source'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_sourcelink') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_sourcelink'] ?></td>
                </tr>
                <tr>
                    <td rowspan="18" style='vertical-align:top'>
                        <?php echo "<b>" . getMLtext('legend') . ":</b><br/>" . $layerdata['legend_img'] ?>
                    </td>
                    <td><b><?php printMLtext('layer_meta_sourcedate') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_sourcedate'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_citation') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_citation'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_licence') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_licence'] ?></td>
                </tr>

                <tr>
                    <td><b><?php printMLtext('layer_type') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['layer_type'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('datafile_path') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['datafile_path'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_order') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['layer_order'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_classification_1') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_classification_1'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('layer_meta_classification_2') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['meta_classification_2'] ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('display_albertine') ?></b></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_albertine'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('display_mountains') ?></b></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_mountains'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('display_lakes') ?></b></td>
                    <td><?php echo ($layerdata['gislayer']['allow_display_lakes'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('can_be_queried') ?></b></td>
                    <td><?php echo ($layerdata['gislayer']['allow_identify'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <tr>
                    <td><b><?php printMLtext('is_disabled') ?></b></td>
                    <td><?php echo ($layerdata['gislayer']['disabled'] == 't'? getMLtext('yes') : getMLtext('no') ) ?></td>
                </tr>
                <?php if ($layerdata['gislayer']['layer_type'] == 'vector'): ?>
                <tr>
                    <td><b><?php printMLtext('download')  ?></b></td>
                    <td><?php
                        if ($layerdata['gislayer']['allow_download'] == 't') {
                            if ($user['id'] != 0) { //logged in
                                echo $layerdata['download_link'] . "<img src=\"images/download_icon.jpg\" border=\"0\" width=\"16\" height=\"16\" alt=\"" . getMLtext('download') . "\">" . getMLtext('download_shapefile') . "</a>";
                            } else {
                                printMLtext('logged_in_users_only');
                            }
                        } else {
                            printMLtext('not_available');
                        }
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><b><?php printMLtext('projection') ?></b></td>
                    <td><?php echo $layerdata['gislayer']['projection'] ?></td>
                </tr>


            </table>
        </div>
        <br/>
    </div>                    
</div>
