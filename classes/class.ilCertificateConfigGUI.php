<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ilCertificateConfigGUI
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilCertificateConfigGUI extends ilPluginConfigGUI
{

    const CMD_CANCEL = 'cancel';
    const CMD_CONFIGURE = 'configure';
    const CMD_SAVE = 'save';

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var ilTemplate
     */
    protected $tpl;
    /**
     * @var ilToolbarGUI
     */
    protected $toolbar;

    /**
     * ilCertificateConfigGUI constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->pl = ilCertificatePlugin::getInstance();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
    }

    /**
     * @param $cmd
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case self::CMD_CONFIGURE:
            case self::CMD_SAVE:
                $this->$cmd();
                break;
        }
    }

    /**
     * Configure screen
     */
    public function configure()
    {
        $this->initToolbarButton();

        $form = new ilCertificateConfigFormGUI($this);
        $form->fillForm();
        $ftpl = $this->pl->getTemplate('default/tpl.config_form.html');
        $ftpl->setVariable("FORM", $form->getHTML());
        $ftpl->setVariable("TXT_USE_PLACEHOLDERS", $this->pl->txt('txt_use_placeholders'));
        foreach (srCertificateStandardPlaceholders::getStandardPlaceholders() as $placeholder => $text) {
            $ftpl->setCurrentBlock("placeholder");
            $ftpl->setVariable("PLACEHOLDER", $placeholder);
            $ftpl->setVariable("TXT_PLACEHOLDER", $text);
            $ftpl->parseCurrentBlock();
        }
        $this->tpl->setContent($ftpl->get());
    }

    /**
     * Save config
     */
    public function save()
    {
        $form = new ilCertificateConfigFormGUI($this);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_save_config'), true);
            $this->ctrl->redirect($this, self::CMD_CONFIGURE);
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
        }
    }

    /**
     *
     */
    protected function initToolbarButton()
    {

    }
}
