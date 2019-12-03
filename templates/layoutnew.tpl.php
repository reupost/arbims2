<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        
        <!-- <meta http-equiv="cache-control" content="max-age=0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
        <meta http-equiv="pragma" content="no-cache" /> -->
        
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        
        <link href="css/bootstrap.css?version=1.1" rel="stylesheet" />
        <link href="css/application.css?version=1.0" rel="stylesheet"/>
        <link href="css/bootstrap-responsive.css" rel="stylesheet"/>
        <link href="css/font-awesome/css/font-awesome.css" rel="stylesheet"/>        
        <link href="js/chosen/css/chosen.css" rel="stylesheet"/>
        <link href="js/jqtree/jqtree.css" rel="stylesheet"/>
        <link href="css/main.css" rel="stylesheet"/>
        
        <script type="text/javascript" src="js/jquery/jquery.min.js"></script>        
        <script type="text/javascript" src="js/jquery.passwordstrength.js"></script>
        <script type="text/javascript" src="js/noty/jquery.noty.js"></script>
        <script type="text/javascript" src="js/noty/layouts/topRight.js"></script>
        <script type="text/javascript" src="js/noty/themes/default.js"></script>   
        <script type="text/javascript" src="js/bootstrap.min.js"></script>        
        <script type="text/javascript" src="js/chosen/js/chosen.jquery.min.js"></script>         
        
        <link rel="shortcut icon" href="css/favicon.ico?v=new" type="image/x-icon"/>
        
        <title><?php echo 'ARBIMS' ?></title>
        <?php echo (isset($page_specific_head_content) ? $page_specific_head_content : '') ?>
    </head>
    <body>
        <?php if (isset($session_msg)): ?>
        <?php if (isset($session_msg['state'])) { 
                echo GetMessagePopupJS($session_msg['msg'], $session_msg['state']);             
            } else { 
                echo GetMessagePopupJS($session_msg['msg']); 
            } ?>
        <?php endif; ?>        
        <div class='banner-top
            <?php if (isset($region)) {
                    switch($region) {
                        case "albertine"    :   echo " banner-top-albertine"; break;
                        case "lakes"        :   echo " banner-top-lakes"; break;
                        case "mountains"    :   echo " banner-top-mountains"; break;
                        default             :   echo " banner-top-default"; break;
                    }
                } else {
                    echo " banner-top-default"; 
                }
            ?>
             '>
        </div>
        <div class="navbar navbar-inverse navbar-fixed-top">            
            <div class="navbar-inner">
                <div class="container">
                    <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-col1">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </a>                    
                    <a class="brand" href="out.index.php"><?php printMLText('arcos_biodiversity_management_information_system') ?></a>
                    <div class="nav-collapse nav-col1">
                        <?php if ($site_user['id'] != 0): ?>
                        <ul id="main-menu-admin"class="nav pull-right">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo getMLtext('signed_in_as') . " '" . htmlspecialchars($site_user['username']) . "' " ?><i class="icon-caret-down"></i></a>
                                <ul class="dropdown-menu" role="menu">                                    
                                    <li><a href="out.myaccount.php"><?php printMLText('my_account') ?></a></li>
                                    <li class="divider"></li>
                                    <li class="dropdown-submenu">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php printMLText('language') ?></a>
                                        <ul class="dropdown-menu" role="menu">
                                            <li class="active"><a href="op.setlang.php?lang=en_GB&referer=<?php echo rawurlencode(GetCurrentURL()) ?>"><?php printMLText('en_GB') ?></a></li>
                                            <li><a href="op.setlang.php?lang=fr_FR&referer=<?php echo rawurlencode(GetCurrentURL()) ?>"><?php printMLText('fr_FR') ?></a></li>
                                        </ul>
                                    </li>
                                    <li class="divider"></li>
                                    <?php if ($site_user['siterole'] == 'admin'): ?>
                                        <li><a href="out.admin.php"><?php printMLText('admin_tools') ?></a></li>
                                    <?php endif; ?>
                                    <li><a href="op.logout.php"><?php printMLText('sign_out') ?></a></li>
                                </ul>
                            </li>
                        </ul>
                        <?php else: ?>
                        <ul id="main-menu-admin"class="nav pull-right">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo getMLText('language') . " " ?><i class="icon-caret-down"></i></a>
                                <ul class="dropdown-menu" role="menu">
                                    <li class="active"><a href="op.setlang.php?lang=en_GB&referer=<?php echo rawurlencode(GetCurrentURL()) ?>"><?php printMLText('en_GB') ?></a></li>
                                    <li><a href="op.setlang.php?lang=fr_FR&referer=<?php echo rawurlencode(GetCurrentURL()) ?>"><?php printMLText('fr_FR') ?></a></li>
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo getMLText('sign_in') . " " ?><i class="icon-caret-down"></i></a>                                
                                <ul class="dropdown-menu" role="menu">
                                    <li><a href="out.login.php"><?php printMLText('sign_in') ?></a></li>
                                    <li><a href="out.register.php"><?php printMLText('register') ?></a></li>
                                </ul>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="container">
                    <div class="nav-collapse nav-col1">
                        <ul id="main-menu-general" class="nav pull-left">
                            <li id="first"><a href="out.index.php"><?php printMLText('home') ?></a></li>
                            <li><a href="out.biodiversitydata.php"><?php printMLText('biodiversity_data') ?></a></li>                            
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php printMLText('maps') ?> <i class="icon-caret-down"></i></a>
                                <ul class="dropdown-menu" role="menu">                                    
                                    <li><a href="out.map.albertine.php"><?php printMLText('region_albertine') ?></a></li>
                                    <li><a href="out.map.mountains.php"><?php printMLText('region_mountains') ?></a></li>
                                    <li><a href="out.map.lakes.php"><?php printMLText('region_lakes') ?></a></li>                                    
                                </ul>
                            </li>
                            <li><a href="out.libraries.php"><?php printMLText('libraries') ?></a></li>
                            <li><a href="out.help.php"><?php printMLText('help') ?></a></li>
                            <li><a href="http://www.arcosnetwork.org/" target="_new"><?php printMLText('main_site') ?></a></li>
                            <li><a href="out.partners.php"><?php printMLText('partners') ?></a></li>
                        </ul>                        
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row-fluid">
                <?php echo $sf_content->fetch() ?>
            </div>
        </div>                           
        <div class="row-fluid" style="padding-top: 20px;">
            <div class="span12">
                <div class="acknowledgments">
                    <?php printMLtext('arbmis_acknowledgements') ?>
                    <br/>
                    <a href='http://www.arcosnetwork.org' target='_new' alt='ARCOS'><img src='images/logo_arcos.jpg' width='56px' height='60px' class='sponsors_logos' alt='ARCOS logo' title='Albertine Rift conservation Society'/></a>
                    <a href='http://www.cepf.net' target='_new' alt='CEP'><img src='images/logo_cep.jpg' width='170px' height='60px' class='sponsors_logos' alt='CEP logo' title='Critical Ecosystem Partnership Fund'/></a>
                    <a href='http://jrsbiodiversity.org/' target='_new' alt='JRS'><img src='images/logo_jrs.jpg' width='259px' height='60px' class='sponsors_logos' alt='JRS logo' title='JRS Biodiversity Foundation'/></a>
                    <a href='http://www.macfound.org' target='_new' alt='MAF'><img src='images/logo_maf.jpg' width='170px' height='60px' class='sponsors_logos' alt='MAF logo' title='MacArthur Foundation'/></a>
                    <a href='http://www.sdc.admin.ch' target='_new' alt='SDC'><img src='images/logo_sdc.jpg' border='0' width='129px' height='60px' class='sponsors_logos' alt='SDC logo' title='Swiss Agency for Development and Cooperation'/></a>
                </div>
                <div class="alert alert-info">
                    <div class="footNote">
                        <div class="footNoteText footNoteTextLeft"><?php printMLText('terms_and_conditions') ?></div>
                        <div class="footNoteText footNoteTextRight">ARBIMS (C) <a href='www.arcosnetwork.org' target='_new'>ARCOS</a> 2014.  Portal designed by <a href='www.reubenroberts.co.za' target='_new'>Reuben Roberts</a> 2014.</div>
                    </div>
                </div>
            </div>
        </div>            
    </body>
</html>
