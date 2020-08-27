<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use srag\DIC\Certificate\DICTrait;

/**
 * GUI-Class certCheckSignatureGUI
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_IsCalledBy certCheckSignatureGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class certCheckSignatureGUI
{
    use DICTrait;
    const CMD_DECRYPT_SIGNATURE = 'decryptSignature';
    const CMD_SHOW_FORM = 'showForm';

    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilCertificatePlugin
     */
    protected $pl;
    /**
     * @var ilCtrl
     */
    protected $ctrl;

    function __construct()
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->pl = ilCertificatePlugin::getInstance();
        $this->ctrl = $DIC->ctrl();
    }

    /**
     * @return bool
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd(self::CMD_SHOW_FORM);
        if (self::version()->is6()) {
            $this->tpl->loadStandardTemplate();
        } else {
        $this->tpl->getStandardTemplate();
        }
        switch ($cmd) {
            case self::CMD_SHOW_FORM:
            default:
                $this->showForm();
                break;
            case self::CMD_DECRYPT_SIGNATURE:
                $this->decryptSignature();
                break;
        }
        if (self::version()->is6()) {
            $this->tpl->printToStdout();
        } else {
        $this->tpl->show();
        }
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

        $signature = $form->getInput('signature');
        $decrypted = srCertificateDigitalSignature::decryptSignature($signature);

        if ($decrypted) {
            ilUtil::sendInfo($this->pl->txt('decrypt_successful') . '<br/>' . $decrypted, true);
        } else {
            ilUtil::sendFailure($this->pl->txt('decrypt_failed'), true);
        }
    }
}
