<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Form-Class srCertificateTypeSignatureFormGUI
 *
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
     * @var
     */
    protected $lng;

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
     * @param srCertificateTypeGUI $parent_gui
     * @param srCertificateSignature $signature
     * @param srCertificateType $type
     */
    function __construct($parent_gui, srCertificateSignature $signature, srCertificateType $type)
    {
        global $ilCtrl, $lng;
        $this->parent_gui = $parent_gui;
        $this->type = $type;
        $this->signature = $signature;
        $this->ctrl = $ilCtrl;
        $this->pl = ilCertificatePlugin::getInstance();
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');
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

        if($this->signature->getId()){

            $signature_file = (array)$this->getInput('signature_file');
            if($signature_file["name"]){
                if(!$this->type->storeSignatureFile($signature_file, $this->signature)){
                    return false;
                } else {
                    $this->signature->setSuffix(pathinfo($signature_file["name"], PATHINFO_EXTENSION));
                }
            }
            $this->signature->store();
        }else{
            $signature_file = (array)$this->getInput('signature_file');
            $this->signature->setSuffix(pathinfo($signature_file["name"], PATHINFO_EXTENSION));
            $this->signature->store();
            if(!$this->type->storeSignatureFile($signature_file, $this->signature)){
                $this->signature->delete();
                return false;
            }
        }

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
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));

        $item = new ilTextInputGUI($this->pl->txt('first_name'), 'first_name');
        $item->setValue($this->signature->getFirstName());
        $item->setRequired(true);
        $this->addItem($item);

        $item = new ilTextInputGUI($this->pl->txt('last_name'), 'last_name');
        $item->setValue($this->signature->getLastName());
        $item->setRequired(true);
        $this->addItem($item);

        $item = new ilFileInputGUI($this->pl->txt('signature_file'), 'signature_file');
        $item->setSuffixes(array('jpeg', 'jpg', 'gif', 'bmp', 'png', 'svg'));
        $signature_file = $this->signature->getFilePath(true);
        if (is_file($signature_file)) {
            $item->setValue($signature_file);
        }
        $item->setFilename($signature_file);
        $item->setInfo($this->pl->txt('signature_file_info'));
        $item->setRequired(!is_file($signature_file));
        $this->addItem($item);

        $command = $this->signature->getId() ? 'updateSignature' : 'createSignature';
        $this->addCommandButton($command, $this->lng->txt('save'));
    }


}

?>