<?php
/**
 * Implementation of DashBoard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann,
 *             2014 Reuben Roberts
 * @version    Release: @package_version@
 */
/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for DashBoard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann,
 *             2014 Reuben Roberts
 * @version    Release: @package_version@
 */
class SeedDMS_View_DashBoard extends SeedDMS_Bootstrap_Style {

    function show() { /* begin function */
        $dms = $this->params['dms'];
        $user = $this->params['user'];
        $rootfolder = $this->params['rootfolder'];
        $cachedir = $this->params['cachedir'];
        $previewwidth = $this->params['previewWidthList'];
        $mostDownloaded = $this->params['mostDownloaded'];
        $mostRecent = $this->params['mostRecent'];
        $recommended = $this->params['recommended'];
        $mostRated = $this->params['mostRated'];

        $this->htmlStartPage(getMLText("dashboard"));

        $this->globalNavigation($rootfolder);

        $this->contentStart();

        $this->contentHeading(getMLText('dashboard_heading_lake'));
        ?>
        <div class="row-fluid">
            <div class="span12">
                <?php //$this->contentHeading('The Albertine Rift Biodiversity Media Library'); 
                ?>
                <div class="well">
                    <?php echo getMLText('dashboard_description'); ?>              
                </div>
            </div>
            <div>
                <div class="row-fluid">
                    <div class="span3">
                        <?php $this->contentHeading(getMLText('recommended')); ?>
                        <div class="well">
                            <table class="table">                                
                                <tbody>					
                                    <?php
                                    $previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth);
                                    foreach ($recommended as $doc) {
                                        $document = $dms->getDocument($doc['documentid']);
                                        if ($document->getAccessMode($user) < M_READ)
                                            continue; //remove docs. which user cannot see                                        
                                        if($latestContent = $document->getLatestContent()) 
                                            $previewer->createPreview($latestContent);								                      
                                        echo "<tr>";
                                        printf("<td style='min-width:20px;text-align:center'><a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>", $doc['documentid']);
                                        if ($previewer->hasPreview($latestContent)) {
                                            print "<img class=\"mimeicon\" width=\"".$previewwidth."\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        } else {
                                            print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        }
                                        /*    if (substr($lc->getMimeType(), 0, 6) == "image/") {
                                            printf("<img class='mimeicon' width='%d' src='../op/op.Preview.php?documentid=%d&version=%d&width=%d' title='%s'>", $previewwidth, $doc['documentid'], $doc['latestversion'], $previewwidth, htmlspecialchars($lc->getMimeType()));
                                        } else {
                                            printf("<img class='mimeicon' src='%s' title='%s'>", $this->getMimeIcon($lc->getFileType()), htmlspecialchars($lc->getMimeType()));
                                        } */
                                        echo "</a><br/>";
                                        printf("<a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>%s</a></td>", $doc['documentid'], $doc['name']);
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>	  
                    </div>

                    <div class="span3">
                        <?php $this->contentHeading(getMLText('rated_highest')); ?>
                        <div class="well">
                            <table class="table">
                                <tbody>					
                                    <?php
                                    foreach ($mostRated as $doc) {
                                        $document = $dms->getDocument($doc['documentid']);
                                        if ($document->getAccessMode($user) < M_READ)
                                            continue; //remove docs. which user cannot see
                                        if($latestContent = $document->getLatestContent()) 
                                            $previewer->createPreview($latestContent);								                      
                                        echo "<tr>";
                                        printf("<td style='min-width:20px;text-align:center'><a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>", $doc['documentid']);
                                        if ($previewer->hasPreview($latestContent)) {
                                            print "<img class=\"mimeicon\" width=\"".$previewwidth."\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        } else {
                                            print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        }                                        
                                        echo "</a><br/>";
                                        printf("<a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>%s (%d)</a></td>", $doc['documentid'], $doc['name'], $doc['docrating']);
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="span3">
                        <?php $this->contentHeading(getMLText('most_downloaded')); ?>
                        <div class="well">
                            <table class="table">
                                <tbody>					
                                    <?php
                                    foreach ($mostDownloaded as $doc) {
                                        $document = $dms->getDocument($doc['documentid']);
                                        if ($document->getAccessMode($user) < M_READ)
                                            continue; //remove docs. which user cannot see
                                        if($latestContent = $document->getLatestContent()) 
                                            $previewer->createPreview($latestContent);								                      
                                        echo "<tr>";
                                        printf("<td style='min-width:20px;text-align:center'><a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>", $doc['documentid']);
                                        if ($previewer->hasPreview($latestContent)) {
                                            print "<img class=\"mimeicon\" width=\"".$previewwidth."\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        } else {
                                            print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        }
                                        echo "</a><br/>";
                                        printf("<a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>%s (%d)</a></td>", $doc['documentid'], $doc['name'], $doc['numdownloads']);
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="span3">
                        <?php $this->contentHeading(getMLText('most_recent')); ?>
                        <div class="well">
                            <table class="table">
                                <tbody>					
                                    <?php
                                    foreach ($mostRecent as $doc) {
                                        $document = $dms->getDocument($doc['documentid']);
                                        if ($document->getAccessMode($user) < M_READ)
                                            continue; //remove docs. which user cannot see
                                        if($latestContent = $document->getLatestContent()) 
                                            $previewer->createPreview($latestContent);								                      
                                        echo "<tr>";
                                        printf("<td style='min-width:20px;text-align:center'><a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>", $doc['documentid']);
                                        if ($previewer->hasPreview($latestContent)) {
                                            print "<img class=\"mimeicon\" width=\"".$previewwidth."\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=".$previewwidth."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        } else {
                                            print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
                                        }
                                        echo "</a><br/>";
                                        printf("<a href='../out/out.ViewDocument.php?documentid=%d&showtree=1'>%s (%s)</a></td>", $doc['documentid'], $doc['name'], getReadableDate($doc['date']));
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

        <?php
        //RR fixed short-tag

        $this->htmlEndPage();
    }

/* end function */
}
?>
