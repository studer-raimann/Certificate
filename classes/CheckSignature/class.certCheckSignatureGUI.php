<?php
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');
require_once('class.certCheckSignatureFormGUI.php');

/**
 * GUI-Class certCheckSignatureGUI
 *
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_IsCalledBy certCheckSignatureGUI: ilRouterGUI
 */
class certCheckSignatureGUI
{

    function __construct()
    {
        global $ilCtrl, $tpl, $lng;
        /**
         * @var $tpl    ilTemplate
         * @var $ilCtrl ilCtrl
         * @var $ilTabs ilTabsGUI
         */
        $this->tpl = $tpl;
        $this->pl = new ilCertificatePlugin();
        $this->ctrl = $ilCtrl;
        $this->lng = $lng;
    }


    /**
     * @return bool
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            case 'showForm':
            default:
                $this->showForm();
                break;
            case 'decryptSignature':
                $this->decryptSignature();
                break;
        }

        return true;
    }

    public function showForm()
    {

        $form = new certCheckSignatureFormGUI();
        $this->tpl->setContent($form->getHTML());
    }

    public function decryptSignature()
    {

        $form = new certCheckSignatureFormGUI();
        if (!$form->checkInput()) {
            ilUtil::sendFailure($this->pl->txt('decrypt_failed'), true);
        }
        $public_key = openssl_get_publickey('file://' . ilCertificateConfig::getX('signature_publickey'));
        openssl_public_decrypt(base64_decode($form->getInput('signature')), $decrypted, $public_key);

        if ($decrypted) {
            ilUtil::sendInfo($this->pl->txt('decrypt_successful') . '<br/>' . $decrypted, true);
        } else {
            ilUtil::sendFailure($this->pl->txt('decrypt_failed'), true);
        }
    }
}