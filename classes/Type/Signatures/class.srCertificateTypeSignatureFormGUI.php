<?php

/**
 * Form-Class srCertificateTypeSignatureFormGUI
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateTypeSignatureFormGUI extends ilPropertyFormGUI
{

    /**
     * @var srCertificateType
     */
    protected $type;
    /**
     * @var ilCertificatePlugin
     */
    protected $pl;
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var
     */
    protected $parent_gui;
    /**
     * @var srCertificateSignature
     */
    protected $signature;

    /**
     * @param srCertificateTypeGUI   $parent_gui
     * @param srCertificateSignature $signature
     * @param srCertificateType      $type
     */
    function __construct($parent_gui, srCertificateSignature $signature, srCertificateType $type)
    {
        parent::__construct();
        global $DIC;
        $this->parent_gui = $parent_gui;
        $this->type = $type;
        $this->signature = $signature;
        $this->ctrl = $DIC->ctrl();
        $this->pl = ilCertificatePlugin::getInstance();
        $this->initForm();
    }

    /**
     * @return bool
     */
    public function saveObject()
    {
        if (!$this->fillObject()) {
            return false;
        }

        $this->signature->save();
        $file_data = (array) $this->getInput('signature_file');
        if (count($file_data) && isset($file_data['name']) && $file_data['name']) {
            if (!$this->signature->storeSignatureFile($file_data)) {
                return false;
            }
        }
        $this->signature->save();

        return true;
    }

    /**
     * @return bool
     */
    protected function fillObject()
    {
        $this->setValuesByPost();
        if (!$this->checkInput()) {
            return false;
        }
        $this->signature->setLastName($this->getInput('last_name'));
        $this->signature->setFirstName($this->getInput('first_name'));

        return true;
    }

    /**
     * Init form
     */
    protected function initForm()
    {
        $title = $this->signature->getId() ? $this->pl->txt('edit_signature') : $this->pl->txt('add_new_signature');
        $this->setTitle($title);
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));

        $item = new ilTextInputGUI($this->pl->txt('first_name'), 'first_name');
        $item->setValue($this->signature->getFirstName());
        $item->setRequired(true);
        $this->addItem($item);

        $item = new ilTextInputGUI($this->pl->txt('last_name'), 'last_name');
        $item->setValue($this->signature->getLastName());
        $item->setRequired(true);
        $this->addItem($item);

        // If the signature is a rasterized image, we display it base64 encoded
        $is_vector = (in_array(strtolower($this->signature->getSuffix()), array('svg')));
        if ($is_vector) {
            $item = new ilFileInputGUI($this->pl->txt('signature_file'), 'signature_file');
        } else {
            $item = new ilImageFileInputGUI($this->pl->txt('signature_file'), 'signature_file');
        }
        $item->setSuffixes(array('jpeg', 'jpg', 'gif', 'bmp', 'png', 'svg'));
        $signature_file = $this->signature->getFilePath(true);
        if (is_file($signature_file) && !$is_vector) {
            $item->setValue($signature_file);
            $base64 = base64_encode(file_get_contents($signature_file));
            $suffix = $this->signature->getSuffix();
            $item->setImage("data:image/{$suffix};base64,{$base64}");
        }
        $item->setFilename($signature_file);
        $item->setInfo($this->pl->txt('signature_file_info'));
        $item->setRequired(!is_file($signature_file));
        $item->setValue($this->signature->getFilePath(true));
        $this->addItem($item);

        $command = $this->signature->getId() ? srCertificateTypeGUI::CMD_UPDATE_SIGNATURE : srCertificateTypeGUI::CMD_CREATE_SIGNATURE;
        $this->addCommandButton($command, $this->pl->txt('save'));
    }
}
