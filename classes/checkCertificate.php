<?php
chdir('../../../../../../../../');
include_once "Services/Context/classes/class.ilContext.php";
ilContext::init(ilContext::CONTEXT_SOAP_NO_AUTH);
require_once("Services/Init/classes/class.ilInitialisation.php");
ilInitialisation::initILIAS();

require 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/DigitalSignature/class.srCertificateDigitalSignature.php';
$decrypted = srCertificateDigitalSignature::decryptSignature(strtr($_GET['signature'], '-_,', '+/='));
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>ILIAS</title>
</head>
<body class="std">
<div id="drag_zmove"></div>
<div id="ilAll">
    <div class="ilias-light-login-bg"></div>
    <div class="ilMainMenu">
        <div class="container login_page">
            <div class="row">
                <nav id="ilTopNav" class="navbar navbar-default" role="navigation"></nav>
            </div>
        </div>
    </div>
    <div id="mainspacekeeper" class="container login_page">
        <div class="row" style="position: relative;">
            <div id="fixed_content" class="ilContentFixed login_page">
                <div id="mainscrolldiv" class="ilStartupFrame container">


                    <div class="ilMessageBox">


                    </div>

                    <div class="small"><p style="text-align: left;"><strong></strong><br/><span></span></p></div>


                    <div>

                    </div>
                    <div class="ilStartupSection">

                        <div id="fixed_content" class=" ilContentFixed">
                            <div id="mainscrolldiv" class="ilStartupFrame container">


                                <?php if ($decrypted) { ?>
                                    <div class="alert alert-info">
                                        <h2>CHECK CERTIFICATE SIGNATURE</h2>
                                        <h5 class="ilAccHeadingHidden"><a id="il_message_focus" name="il_message_focus">Informationsmeldung</a>
                                        </h5>
                                        <p>The decryption was successful.<br/><?php echo $decrypted; ?></p>
                                    </div>
                                <?php } else { ?>
                                    <div class="alert alert-warning">
                                        <h2>CHECK CERTIFICATE SIGNATURE</h2>
                                        <h5 class="ilAccHeadingHidden">
                                            <a id="il_message_focus" name="il_message_focus">Fehlermeldung</a>
                                        </h5>
                                        <p>The signature value could not be decrypted.</p>
                                    </div>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>


                <div id="minheight"></div>

            </div>

</body>
</html>