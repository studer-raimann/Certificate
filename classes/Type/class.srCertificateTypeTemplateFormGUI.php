<?php

/**
 * Form-Class srCertificateTypeTemplateFormGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateTypeTemplateFormGUI extends ilPropertyFormGUI {

	/**
	 * @var srCertificateType
	 */
	protected $type;
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
	 * @param $parent_gui
	 * @param $type
	 */
	function __construct($parent_gui, srCertificateType $type) {
		global $DIC;
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->type = $type;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->ctrl = $DIC->ctrl();
		$this->rbac = $DIC->rbac()->review();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->lng = $DIC->language();
		$this->lng->loadLanguageModule('meta');
		$this->lng->loadLanguageModule('form');
		$this->initForm();
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		$template_file = (array)$this->getInput('template_file');
		if (count($template_file) && isset($template_file['name'])) {
			$this->type->storeTemplateFile($template_file);
		}
		// Delete and add assets
		foreach ((array)$this->getInput('remove_assets') as $asset) {
			$this->type->removeAsset($asset);
		}
		if (isset($_FILES['add_assets'])) {
			$files = $this->formatFileArray($_FILES['add_assets']);
			foreach ($files as $file) {
				$this->type->storeAsset($file);
			}
		}
		$this->type->update();

		return true;
	}


	/**
	 * Rearrange the $_FILES array from multiple file inputs
	 * http://www.php.net/manual/de/reserved.variables.files.php
	 *
	 * @param array $file_data
	 *
	 * @return array
	 */
	protected function formatFileArray(array $file_data) {
		$result = array();
		foreach ($file_data as $key1 => $value1) {
			foreach ($value1 as $key2 => $value2) {
				$result[$key2][$key1] = $value2;
			}
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	protected function fillObject() {
		$this->setValuesByPost();
		// Hacky: The file input must accept only valid suffixes depending on the chosen template type
		$template_type = srCertificateTemplateTypeFactory::getById((int)$_POST['template_type_id']);
		/** @var ilFileInputGUI $file_input */
		$file_input = $this->getItemByPostVar('template_file');
		$file_input->setSuffixes($template_type->getValidSuffixes());
		if (!$this->checkInput()) {
			return false;
		}
		$this->type->setTemplateTypeId($this->getInput('template_type_id'));

		return true;
	}


	/**
	 * Init form
	 */
	protected function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->setTitle($this->pl->txt('edit_type_template'));

		$types_available = array();
		$types = array(
			srCertificateTemplateTypeFactory::getById(srCertificateTemplateType::TEMPLATE_TYPE_HTML),
			srCertificateTemplateTypeFactory::getById(srCertificateTemplateType::TEMPLATE_TYPE_JASPER),
		);
		/** @var $type srCertificateTemplateType */
		foreach ($types as $type) {
			if ($type->isAvailable()) {
				$types_available[$type->getId()] = $type->getTitle();
			}
		}

		if (!count($types_available)) {
			ilUtil::sendInfo($this->pl->txt('msg_no_template_types'));
		}
		$item = new ilSelectInputGUI($this->pl->txt('template_type_id'), 'template_type_id');
		$item->setOptions($types_available);
		$item->setRequired(true);
		$item->setValue($this->type->getTemplateTypeId());
		$this->addItem($item);

		$item = new ilFileInputGUI($this->pl->txt('template_file'), 'template_file');
		$template_file = $this->type->getCertificateTemplatesPath(true);
		if (is_file($template_file)) {
			$item->setValue($template_file);
		}
		$item->setFilename($template_file);
		$item->setInfo($this->pl->txt('template_file_info'));
		$item->setRequired(!is_file($template_file));
		$this->addItem($item);

		$assets = $this->type->getAssets();
		if (count($assets)) {
			$item = new ilMultiSelectInputGUI($this->pl->txt('assets'), 'remove_assets');
			$options = array();
			foreach ($assets as $asset) {
				$options[$asset] = $asset;
			}
			$item->setOptions($options);
			$item->setInfo($this->pl->txt('assets_info'));
			$this->addItem($item);
		}

		$item = new ilFileWizardInputGUI($this->pl->txt('add_assets'), 'add_assets');
		$item->setFilenames(array( 0 => '' ));
		$this->addItem($item);

		$this->addCommandButton(srCertificateTypeGUI::CMD_DOWNLOAD_DEFAULT_TEMPLATE, $this->pl->txt('download_default_template'));
		if (is_file($this->type->getCertificateTemplatesPath(true))) {
			$this->addCommandButton(srCertificateTypeGUI::CMD_DOWNLOAD_TEMPLATE, $this->pl->txt('download_template'));
		}
		$this->addCommandButton(srCertificateTypeGUI::CMD_UPDATE_TEMPLATE, $this->lng->txt('save'));
	}
}