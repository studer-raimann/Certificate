<?php

/**
 * Form-Class srCertificateTypePlaceholderFormGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateTypePlaceholderFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac;
	/**
	 * @var
	 */
	protected $parent_gui;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var srCertificatePlaceholder
	 */
	protected $placeholder;


	/**
	 * @param                          $parent_gui
	 * @param srCertificatePlaceholder $placeholder
	 */
	function __construct($parent_gui, srCertificatePlaceholder $placeholder) {
		global $DIC;
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->placeholder = $placeholder;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->ctrl = $DIC->ctrl();
		$this->rbac = $DIC->rbac()->review();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->lng = $DIC->language();
		$this->lng->loadLanguageModule('meta');
		$this->user = $DIC->user();
		$this->initForm();
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		if ($this->placeholder->getId()) {
			$this->placeholder->update();
		} else {
			$this->placeholder->create();
			if ($this->getInput('update_definitions')) {
				$type = $this->placeholder->getCertificateType();
				$definitions = srCertificateDefinition::where(array( 'type_id' => $type->getId() ))->get();
				/** @var $def srCertificateDefinition */
				foreach ($definitions as $def) {
					$phv = srCertificatePlaceholderValue::where(array(
						'definition_id' => $def->getId(),
						'placeholder_id' => $this->placeholder->getId()
					))->first();
					if (!is_null($phv)) {
						continue; // Extra check... should never be the case
					}
					$phv = new srCertificatePlaceholderValue();
					$phv->setDefinitionId($def->getId());
					$phv->setPlaceholderId($this->placeholder->getId());
					foreach ($type->getLanguages() as $lang_code) {
						$phv->setValue($this->getInput("default_value_{$lang_code}"), $lang_code);
					}
					$phv->create();
				}
			}
		}

		return true;
	}


	/**
	 * @return bool
	 */
	protected function fillObject() {
		$this->setValuesByPost();
		if (!$this->checkInput()) {
			return false;
		}
		try {
			$this->placeholder->setIdentifier($this->getInput('identifier'));
			$this->placeholder->setMaxCharactersValue($this->getInput('max_characters'));
			$this->placeholder->setIsMandatory((int)$this->getInput('mandatory'));
			$this->placeholder->setEditableIn($this->getInput('editable_in'));
			$type = $this->placeholder->getCertificateType();
			foreach ($type->getLanguages() as $lang_code) {
				$this->placeholder->setLabel($this->getInput("label_{$lang_code}"), $lang_code);
				$this->placeholder->setDefaultValue($this->getInput("default_value_{$lang_code}"), $lang_code);
			}
		} catch (ilException $e) {
			ilUtil::sendFailure($e->getMessage());

			return false;
		}

		return true;
	}


	/**
	 * Init form
	 */
	protected function initForm() {
		$type = $this->placeholder->getCertificateType();
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$title = ($this->placeholder->getId()) ? $this->pl->txt('edit_placeholder') : $this->pl->txt('add_placeholder');
		$this->setTitle($title);

		$item = new ilTextInputGUI($this->pl->txt('identifier'), 'identifier');
		$item->setRequired(true);
		$item->setValue($this->placeholder->getIdentifier());
		$item->setInfo(sprintf($this->pl->txt('identifier_info'), srCertificatePlaceholder::REGEX_VALID_IDENTIFIER));
		$this->addItem($item);

		$item = new ilNumberInputGUI($this->pl->txt('max_characters'), 'max_characters');
		$item->setRequired(true);
		$item->setSize(2);
		$item->setInfo($this->pl->txt('max_characters_info'));
		$item->setValue($this->placeholder->getMaxCharactersValue());
		$this->addItem($item);

		$item = new ilCheckboxInputGUI($this->pl->txt('mandatory'), 'mandatory');
		if ($this->placeholder->getIsMandatory()) {
			$item->setChecked(true);
		}
		$this->addItem($item);

		$item = new ilMultiSelectInputGUI($this->pl->txt('editable_in'), 'editable_in');
		$options = array();
		foreach ($type->getAllAvailableObjectTypes() as $obj_type) {
			$options[$obj_type] = $obj_type;
		}
		$item->setOptions($options);
		$item->setValue($this->placeholder->getEditableIn());
		$this->addItem($item);

		$item = new ilHiddenInputGUI('placeholder_id');
		$item->setValue($this->placeholder->getId());
		$this->addItem($item);

		if (!$this->placeholder->getId()) {
			$item = new ilCheckboxInputGUI($this->pl->txt('update_cert_definitions'), 'update_definitions');
			$item->setInfo($this->pl->txt('update_cert_definitions_info'));
			$this->addItem($item);
		}

		foreach ($type->getLanguages() as $lang_code) {
			$this->addLanguageInput($lang_code);
		}

		$command = $this->placeholder->getId() ? srCertificateTypeGUI::CMD_UPDATE_PLACEHOLDER : srCertificateTypeGUI::CMD_CREATE_PLACEHOLDER;
		$this->addCommandButton($command, $this->pl->txt('save'));
	}


	protected function addLanguageInput($lang_code) {
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->lng->txt("meta_l_{$lang_code}"));
		$this->addItem($section);
		$item = new ilTextInputGUI($this->pl->txt('label'), "label_{$lang_code}");
		$item->setValue($this->placeholder->getLabel($lang_code));
		$item->setRequired(true);
		$this->addItem($item);
		$item = new ilTextInputGUI($this->pl->txt('default_value'), "default_value_{$lang_code}");
		$item->setValue($this->placeholder->getDefaultValue($lang_code));
		$this->addItem($item);
	}
}
