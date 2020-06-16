<legend><?php printMLtext('admin_tools') ?></legend>
<div class="row-fluid">
    <div class="span12">
        <?php printMLtext('arbmis_admin') ?>
        <table style='border-spacing: 10px; border-collapse: separate;'>
            <colgroup>
                <col width='50%'/>
                <col width='50%'/>
            </colgroup>            
            <tr>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('users') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:15px'><img src='images/users-128x128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_users') ?></p>
                                    <a href='out.listusers.php' alt="<?php printMLtext('user_list') ?>" title="<?php printMLtext('user_list') ?>"><?php printMLtext('user_list') ?></a>
                                </td>
                            </tr>
                        </table>
                    
                </td>
                <td class='well'>

                    <table border='0'>
                        <tr>
                            <td colspan='2'><h4><?php printMLtext('gbif') ?></h4></td>
                        </tr>
                        <tr>
                            <td style='padding-right:15px'><img src='images/GBIF-2015.png' height='128px' width='193px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_admin_gbif') ?></p>
                                <a href='out.gbif_synch.php' alt="<?php printMLtext('synchronise_with_gbif') ?>" title="<?php printMLtext('synchronise_with_gbif') ?>"><?php printMLtext('synchronise_with_gbif') ?></a>
                            </td>
                        </tr>
                    </table>
                    
                </td>
            </tr>
            <tr>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('mailing_list') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:15px'><img src='images/mail_grey_128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_mail') ?></p>
                                    <a href='op.send_bulletin.php?key=ArbMisbuLLetiN' alt="<?php printMLtext('mailing_list_send') ?>" title="<?php printMLtext('mailing_list_send') ?>"><?php printMLtext('mailing_list_send') ?></a>
                                </td>
                            </tr>
                        </table>
                    
                </td>                
                <td class='well'>
                    
                        <table border='0'>
                            <tr>
                                <td colspan='2'><h4><?php printMLtext('spatial_layers') ?></h4></td>
                            </tr>
                            <tr>
                                <td style='padding-right:15px'><img src='images/geoserver-128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                                <td>
                                    <p><?php printMLtext('arbmis_admin_geoserver') ?></p>
                                    <a href='geoserver/' alt="<?php printMLtext('geoserver') ?>" title="<?php printMLtext('geoserver') ?>"><?php printMLtext('geoserver') ?></a><br/><br/>
                                    <a href='out.listgislayers.php' alt="<?php printMLtext('list_spatial_layers') ?>" title="<?php printMLtext('list_spatial_layers') ?>"><?php printMLtext('list_spatial_layers') ?></a>
                                </td>
                            </tr>
                        </table>
                   
                </td>
            </tr>
            <tr>
                <td class='well'>

                    <table border='0'>
                        <tr>
                            <td colspan='2'><h4><?php printMLtext('logs') ?></h4></td>
                        </tr>
                        <tr>
                            <td style='padding-right:15px'><img src='images/logs_128.png' height='128px' width='128px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('logs_download_header') ?></p>
                                <a href='out.listdownloads.php' alt="<?php printMLtext('logs_download') ?>" title="<?php printMLtext('logs_download') ?>"><?php printMLtext('logs_download') ?></a>
                            </td>
                        </tr>
                    </table>

                </td>
                <td class='well'>

                    <table border='0'>
                        <tr>
                            <td colspan='2'><h4><?php printMLtext('ipt') ?></h4></td>
                        </tr>
                        <tr>
                            <td style='padding-right:15px'><img src='images/ipt.jpg' height='128px' width='159px' border='0' alt='pic'/></td>
                            <td>
                                <p><?php printMLtext('arbmis_admin_ipt') ?></p>
                                <a href='ipt/' alt="<?php printMLtext('ipt_link') ?>" title="<?php printMLtext('ipt_link') ?>"><?php printMLtext('ipt_link') ?></a><br/><br/>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

        </table>
    </div>                    
</div>