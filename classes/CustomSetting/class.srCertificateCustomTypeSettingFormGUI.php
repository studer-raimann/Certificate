<?php

require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.srCertificateCustomDefinitionSetting.php');

/**
 * Class srCertificateCustomSettingFormGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateCustomTypeSettingFormGUI extends ilPropertyFormGUI {

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
	 * @var
	 */
	protected $parent_gui;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var srCertificateCustomTypeSetting
	 */
	protected $setting;


	/**
	 * @param                                $parent_gui
	 * @param srCertificateCustomTypeSetting $setting
	 */
	public function __construct($parent_gui, srCertificateCustomTypeSetting $setting) {
		global $DIC;
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->rbac = $DIC->rbac()->review();
		$this->user = $DIC->user();
		$this->setting = $setting;
		$this->pl = ilCertificatePlugin::getInstance();
		$this->lng->loadLanguageModule('meta');
		$this->setFormAction($this->ctrl->getFormAction($parent_gui));
		$this->addCommandButton(srCertificateTypeGUI::CMD_SAVE_CUSTOM_SETTING, $this->lng->txt('save'));
		$this->initForm();
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->checkInput()) {
			return false;
		}
		try {
			$this->setting->setTypeId((int)$_GET['type_id']);
			$this->setting->setIdentifier($this->getInput('identifier'));
			$this->setting->setSettingTypeId($this->getInput('setting_type_id'));
			$this->setting->setData($this->getInput('data'));
			$this->setting->setValue($this->getInput('value'));
			$this->setting->setEditableIn($this->getInput('editable_in'));
			/** @var srCertificateType $type */
			$type = srCertificateType::find($this->setting->getTypeId());
			foreach ($type->getLanguages() as $lang_code) {
				$this->setting->setLabel($this->getInput("label_{$lang_code}"), $lang_code);
			}
			$this->setting->save();

			// Check if existing definitions should be updated to own this setting
			if ($this->getInput('update_definitions')) {
				$definitions = srCertificateDefinition::where(array( 'type_id' => $type->getId() ))->get();
				/** @var $def srCertificateDefinition */
				foreach ($definitions as $def) {
					$definition_setting = new srCertificateCustomDefinitionSetting();
					$definition_setting->setDefinitionId($def->getId());
					$definition_setting->setIdentifier($this->setting->getIdentifier());
					$definition_setting->setValue($this->setting->getValue());
					$definition_setting->save();
				}
			}
		} catch (ilException $e) {
			ilUtil::sendFailure($e->getMessage());

			return false;
		}

		return true;
	}


	/**
	 * Init your form
	 *
	 */
	protected function initForm() {
		$title = ($this->setting->getId()) ? sprintf($this->pl->txt('edit_setting'), $this->setting->getIdentifier()) : $this->pl->txt('add_new_custom_setting');
		$this->setTitle($title);

		$item = new ilHiddenInputGUI('custom_setting_id');
		$item->setValue($this->setting->getId());
		$this->addItem($item);

		$item = new ilTextInputGUI($this->pl->txt('identifier'), 'identifier');
		$item->setRequired(true);
		$item->setValue($this->setting->getIdentifier());
		$item->setInfo(sprintf($this->pl->txt('identifier_info'), srCertificatePlaceholder::REGEX_VALID_IDENTIFIER));
		$this->addItem($item);

		$item = new ilRadioGroupInputGUI($this->pl->txt('custom_setting_type'), 'setting_type_id');
		$item->setRequired(true);
		$option = new ilRadioOption($this->pl->txt('custom_setting_type_'
			. srCertificateCustomTypeSetting::SETTING_TYPE_BOOLEAN), srCertificateCustomTypeSetting::SETTING_TYPE_BOOLEAN);
		$item->addOption($option);
		$option = new ilRadioOption($this->pl->txt('custom_setting_type_'
			. srCertificateCustomTypeSetting::SETTING_TYPE_SELECT), srCertificateCustomTypeSetting::SETTING_TYPE_SELECT);
		$subitem = new ilTextAreaInputGUI($this->pl->txt('custom_setting_type_2_data'), 'data');
		$subitem->setValue($this->setting->getData());
		$subitem->setInfo($this->pl->txt('custom_setting_type_2_data_info'));
		$option->addSubItem($subitem);
		$item->setValue($this->setting->getSettingTypeId());
		$item->addOption($option);
		$this->addItem($item);

		$item = new ilTextInputGUI($this->pl->txt('default_value'), "value");
		$item->setInfo($this->pl->txt('custom_setting_default_value_info'));
		$item->setValue($this->setting->getValue());
		$this->addItem($item);

		$item = new ilMultiSelectInputGUI($this->pl->txt('editable_in'), 'editable_in');
		$options = array();
		foreach (srCertificateType::getAllAvailableObjectTypes() as $type) {
			$options[$type] = $type;
		}
		$item->setOptions($options);
		$item->setValue($this->setting->getEditableIn());
		$this->addItem($item);

		// Update definitions, add settings
		if (!$this->setting->getId()) {
			$item = new ilCheckboxInputGUI($this->pl->txt('update_cert_definitions'), 'update_definitions');
			$item->setInfo($this->pl->txt('custom_setting_update_cert_definitions_info'));
			$this->addItem($item);
		}

		// Label per language
		/** @var srCertificateType $type */
		$type = ($this->setting->getId()) ? srCertificateType::find($this->setting->getTypeId()) : srCertificateType::find((int)$_GET['type_id']);
		foreach ($type->getLanguages() as $lang_code) {
			$this->addLabelInput($lang_code);
		}
	}


	protected function addLabelInput($lang_code) {
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->lng->txt("meta_l_{$lang_code}"));
		$this->addItem($section);
		$item = new ilTextInputGUI($this->pl->txt('label'), "label_{$lang_code}");
		$item->setValue($this->setting->getLabel($lang_code));
		$item->setRequired(true);
		$this->addItem($item);
	}
}