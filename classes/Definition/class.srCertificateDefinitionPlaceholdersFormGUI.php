<?php

/**
 * Form-Class srCertificateDefinitionFormGUI
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateDefinitionPlaceholdersFormGUI extends ilPropertyFormGUI
{

    /**
     * Placeholders with more characters are displayed as textarea
     */
    const MAX_CHARACTERS_TEXT = 128;
    /**
     * @var int
     */
    protected $ref_id = 0;
    /**
     * @var srCertificateDefinition
     */
    protected $definition;
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
    /**
     * @var
     */
    protected $parent_gui;
    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @param                         $parent_gui
     * @param srCertificateDefinition $definition
     */
    function __construct($parent_gui, $definition)
    {
        parent::__construct();
        global $DIC;
        $this->parent_gui = $parent_gui;
        $this->definition = $definition;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->pl = ilCertificatePlugin::getInstance();
        $this->user = $DIC->user();
        $this->initForm();
    }

    public function saveObject()
    {
        if (!$this->fillObject()) {
            return false;
        }
        $this->definition->update();

        return true;
    }

    protected function fillObject()
    {
        $this->setValuesByPost();
        if (!$this->checkInput()) {
            return false;
        }

        /** @var srCertificatePlaceholderValue $pl_value */
        foreach ($this->definition->getPlaceholderValues() as $pl_value) {
            if (!$pl_value->isEditable()) {
                continue;
            }
            foreach ($this->definition->getType()->getLanguages() as $lang) {
                $value = $this->getInput("placeholder_" . $pl_value->getId() . "_" . $lang);
                $pl_value->setValue($value, $lang);
            }
        }
        $this->definition->setSignatureId($this->getInput('signature'));

        return true;
    }

    protected function initForm()
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $this->setTitle($this->pl->txt('certificate_placeholders'));
        // Each placeholder can define values for each language defined in the type
        $languages = $this->definition->getType()->getLanguages();
        $label_lang = in_array($this->user->getLanguage(),
            $languages) ? $this->user->getLanguage() : $this->definition->getDefaultLanguage();

        /** @var $placeholder_value srCertificatePlaceholderValue */
        foreach ($this->definition->getPlaceholderValues() as $placeholder_value) {
            $section = new ilFormSectionHeaderGUI();
            $section->setTitle($placeholder_value->getPlaceholder()->getLabel($label_lang));
            $this->addItem($section);
            foreach ($languages as $lang) {
                $this->addItem($this->getInputField($placeholder_value, $lang));
            }
        }

        if ($signatures = $this->definition->getType()->getSignatures()) {
            $section = new ilFormSectionHeaderGUI();
            $section->setTitle($this->pl->txt('signature'));
            $this->addItem($section);
            $select_input = new ilSelectInputGUI($this->pl->txt('signature'), 'signature');
            $options = array(0 => '');
            foreach ($signatures as $signature) {
                $options[$signature->getId()] = $signature->getFirstName() . ' ' . $signature->getLastName();
            }
            $select_input->setOptions($options);
            $select_input->setValue($this->definition->getSignatureId());
            $this->addItem($select_input);
        }

        $this->addCommandButton(srCertificateDefinitionGUI::CMD_UPDATE_PLACEHOLDERS, $this->pl->txt('save'));

        return;
    }

    /**
     * @param srCertificatePlaceholderValue $placeholder_value
     * @param                               $lang
     * @return ilTextInputGUI
     */
    protected function getInputField($placeholder_value, $lang)
    {
        //        $label = $placeholder_value->getPlaceholder()->getLabel("en");
        $placeholder = $placeholder_value->getPlaceholder();
        //$postvar = "placeholder[" . $placeholder_value->getId(). "][" . $lang . "]";
        $postvar = "placeholder_" . $placeholder_value->getId() . "_" . $lang;
        $label = mb_strtoupper($lang);
        if ($placeholder->getMaxCharactersValue() > self::MAX_CHARACTERS_TEXT) {
            $input = new ilTextAreaInputGUI($label, $postvar);
        } else {
            $input = new ilTextInputGUI($label, $postvar);
            $input->setMaxLength($placeholder->getMaxCharactersValue());
        }
        //        $input->setInfo("[[" . mb_strtoupper($placeholder_value->getPlaceholder()->getIdentifier()) . "]]");
        $input->setDisabled(!$placeholder_value->isEditable());
        $input->setValue($placeholder_value->getValue($lang));
        $input->setRequired($placeholder->getIsMandatory());

        return $input;
    }
}
