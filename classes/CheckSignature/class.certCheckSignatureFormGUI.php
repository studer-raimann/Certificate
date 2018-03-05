<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');

/**
 * Form-Class certCheckSignatureFormGUI
 *
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 */
class certCheckSignatureFormGUI extends ilPropertyFormGUI
{

    function __construct()
    {
        global $tpl, $ilCtrl, $lng;
        /**
         * @var $ilCtrl ilCtrl
         * @var $ilTabs ilTabsGUI
         */
        $this->ctrl = $ilCtrl;
        $this->pl = new ilCertificatePlugin();

        $this->lng = $lng;
        $this->initForm();
    }

    protected function initForm()
    {
        $this->setFormAction($this->ctrl->getFormActionByClass(certCheckSignatureGUI::class, 'decryptSignature'));
        $te = new ilFormSectionHeaderGUI();
        $te->setTitle($this->pl->txt('signature_check'));
        $this->addItem($te);

        // signature
        $signature = new ilTextInputGUI($this->pl->txt('signature'), 'signature');
        $this->addItem($signature);

        $this->addCommandButton('decryptSignature', $this->lng->txt('send'));
    }
}

?>