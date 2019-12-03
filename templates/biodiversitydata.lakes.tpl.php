<legend><?php echo getMLtext('biodiversity_data') . ": " . getMLtext('region_lakes') ?></legend>
<div class="row-fluid">
    <div class="span12">   
        <div id="intro_box">
            <div id="intro">
                <?php if ($user['siterole'] == 'admin'): ?>
                    <div style='float:right'>
                        <form action="out.edit_text.php">
                            <input type='hidden' name='key' id='key' value='lakes' />
                            <input type='submit' value="<?php printMLtext('edit') ?>" />
                        </form>
                    </div>
                <?php endif; ?>
                <div><?php echo $user_text ?></div>
            </div>
            <div id="image_set">
                <img src='images/lakes1.jpg' width='400px' height='200px' alt='pic' title='pic' />
                <p><?php printMLtext('image_caption_lakes1') ?></p>                
                <img src='images/lakes2.jpg' width='400px' height='200px' alt='pic' title='pic' />                   
                <p><?php printMLtext('image_caption_lakes2') ?></p>
                <img src='images/lakes3.jpg' width='400px' height='200px' alt='pic' title='pic' />  
                <p><?php printMLtext('image_caption_lakes3') ?></p>
            </div>
        </div>
        <div class="span12">
        <table style='border-spacing: 10px; border-collapse: separate;'>
            <colgroup>
                <col width='33%'/>
                <col width='33%'/>
                <col width='33%'/>
            </colgroup>             
            <tr>   
                <td colspan = '3'>
                    <h3><?php printMLtext('region_lakes') ?></h3>
                </td>                
            </tr>
            <tr>
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><a href='out.speciestree.lakes.php' alt="<?php printMLtext('taxonomic_tree') ?>" title="<?php printMLtext('taxonomic_tree') ?>"><h4><?php printMLtext('species') ?></h4></a></td>
                        </tr>
                        <tr>
                            <td><img src='images/red_colobus_monkey.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_species') ?></p>
                                <a href='out.speciestree.lakes.php' alt="<?php printMLtext('taxonomic_tree') ?>" title="<?php printMLtext('taxonomic_tree') ?>"><?php printMLtext('taxonomic_tree') ?></a>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><a href='out.listdatasets.lakes.php' alt="<?php printMLtext('datasets') ?>" title="<?php printMLtext('datasets') ?>"><h4><?php printMLtext('datasets') ?></h4></a></td>
                        </tr>
                        <tr>
                            <td><img src='images/Coffea_arabica.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_datasets') ?></p>
                                <a href='out.listdatasets.lakes.php' alt="<?php printMLtext('datasets') ?>" title="<?php printMLtext('datasets') ?>"><?php printMLtext('datasets') ?></a>
                            </td>
                        </tr>
                    </table>
                </td>               
                <td class='welltd'>
                    <table border='0'>
                        <tr>
                            <td colspan='2'><a href='out.listoccurrence.lakes.php' alt="<?php printMLtext('records') ?>" title="<?php printMLtext('records') ?>"><h4><?php printMLtext('records') ?></h4></a></td>
                        </tr>
                        <tr>
                            <td><img src='images/shrike.jpg' height='100px' width='140px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_intro_records') ?></p>
                                <a href='out.listoccurrence.lakes.php' alt="<?php printMLtext('records') ?>" title="<?php printMLtext('records') ?>"><?php printMLtext('records') ?></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        </div>
    </div>                  
</div>
