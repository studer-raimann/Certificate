<?php
require_once(dirname(__FILE__) . '/class.srCertificateTypeTemplateFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeTableGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeSettingsTableGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypePlaceholdersTableGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeStandardPlaceholdersTableGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeSettingFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypePlaceholderFormGUI.php');
require_once(dirname(dirname(__FILE__)) . '/CustomSetting/class.srCertificateCustomTypeSettingFormGUI.php');
require_once(dirname(dirname(__FILE__)) . '/CustomSetting/class.srCertificateCustomTypeSetting.php');
require_once(dirname(dirname(__FILE__)) . '/CustomSetting/class.srCertificateTypeCustomSettingsTableGUI.php');
require_once(dirname(dirname(__FILE__)) . '/Signature/class.srCertificateSignature.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeSignaturesTableGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeSignatureFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');

/**
 * GUI-Class srCertificateTypeGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_isCalledBy srCertificateTypeGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateTypeGUI {

	const CMD_ADD_CUSTOM_SETTING = 'addCustomSetting';
	const CMD_ADD_PLACEHOLDER = 'addPlaceholder';
	const CMD_ADD_SIGNATURE = 'addSignature';
	const CMD_ADD_TYPE = 'addType';
	const CMD_CONFIRM_DELETE_CUSTOM_SETTING = 'confirmDeleteCustomSetting';
	const CMD_CONFIRM_DELETE_PLACEHOLDER = 'confirmDeletePlaceholder';
	const CMD_CONFIRM_DELETE_SIGNATURE = 'confirmDeleteSignature';
	const CMD_CREATE_PLACEHOLDER = 'createPlaceholder';
	const CMD_CREATE_SIGNATURE = 'createSignature';
	const CMD_DELETE_CUSTOM_SETTING = 'deleteCustomSetting';
	const CMD_DELETE_PLACEHOLDER = 'deletePlaceholder';
	const CMD_DELETE_SIGNATURE = 'deleteSignature';
	const CMD_DOWNLOAD_DEFAULT_TEMPLATE = 'downloadDefaultTemplate';
	const CMD_DOWNLOAD_SIGNATURE = 'downloadSignature';
	const CMD_DOWNLOAD_TEMPLATE = 'downloadTemplate';
	const CMD_EDIT_CUSTOM_SETTING = 'editCustomSetting';
	const CMD_EDIT_PLACEHOLDER = 'editPlaceholder';
	const CMD_EDIT_SETTING = 'editSetting';
	const CMD_EDIT_SIGNATURE = 'editSignature';
	const CMD_EDIT_TEMPLATE = 'editTemplate';
	const CMD_EDIT_TYPE = 'editType';
	const CMD_SAVE_CUSTOM_SETTING = 'saveCustomSetting';
	const CMD_SAVE_TYPE = 'saveType';
	const CMD_SHOW_PLACEHOLDERS = 'showPlaceholders';
	const CMD_SHOW_SETTINGS = 'showSettings';
	const CMD_SHOW_SIGNATURES = 'showSignatures';
	const CMD_SHOW_TYPES = 'showTypes';
	const CMD_UPDATE_PLACEHOLDER = 'updatePlaceholder';
	const CMD_UPDATE_SETTING = 'updateSetting';
	const CMD_UPDATE_SIGNATURE = 'updateSignature';
	const CMD_UPDATE_TEMPLATE = 'updateTemplate';
	const CMD_VIEW = 'view';
	const TAB_GENERAL = 'general';
	const TAB_PLACEHOLDERS = 'placeholders';
	const TAB_SETTINGS = 'settings';
	const TAB_SIGNATURES = 'signatures';
	const TAB_TEMPLATE = 'template';
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var srCertificateDefinitionFormGUI
	 */
	protected $form;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var srCertificateType
	 */
	protected $type;
	/**
	 * @var ilDB
	 */
	protected $db;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilMainMenuGUI
	 */
	protected $ilMainMenu;


	public function __construct() {
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->toolbar = $DIC->toolbar();
		$this->tabs = $DIC->tabs();
		$this->type = (isset($_GET['type_id'])) ? srCertificateType::find((int)$_GET['type_id']) : NULL;
		$this->pl = ilCertificatePlugin::getInstance();
		$this->lng = $DIC->language();
		$this->access = $DIC->access();
		$this->db = $DIC->database();
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('uihk_certificate.js'));
		$this->lng->loadLanguageModule('common');
		$this->tpl->setTitleIcon(ilCertificatePlugin::getPluginIconImage());
		$this->rbac = $DIC->rbac()->review();
		$this->user = $DIC->user();
		$this->ilMainMenu = $DIC["ilMainMenu"];
	}


	public function executeCommand() {
		if (!$this->checkPermission()) {
			ilUtil::sendFailure($this->pl->txt('msg_no_permission'), true);
			$this->ctrl->redirectByClass(ilPersonalDesktopGUI::class);
		}

		$this->ilMainMenu->setActive('none');

		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);

		if (!in_array($cmd, array( self::CMD_ADD_TYPE, '' ))) {
			$this->ctrl->saveParameter($this, 'type_id');
			$this->ctrl->saveParameter($this, 'signature_id');
		}
		$this->tpl->getStandardTemplate();
		switch ($next_class) {
			case '':
				switch ($cmd) {
					case self::CMD_SHOW_TYPES:
						$this->showTypes();
						break;
					case self::CMD_EDIT_TYPE:
						$this->editType();
						$this->setTabs(self::TAB_GENERAL);
						break;
					case self::CMD_ADD_TYPE:
						$this->addType();
						$this->setTabs(self::TAB_GENERAL);
						break;
					case self::CMD_SAVE_TYPE:
						$this->saveType();
						$this->setTabs(self::TAB_GENERAL);
						break;
					case self::CMD_EDIT_TEMPLATE:
						$this->editTemplate();
						$this->setTabs(self::TAB_TEMPLATE);
						break;
					case self::CMD_UPDATE_TEMPLATE:
						$this->updateTemplate();
						$this->setTabs(self::TAB_TEMPLATE);
						break;
					case self::CMD_DOWNLOAD_DEFAULT_TEMPLATE:
						$this->downloadDefaultTemplate();
						$this->setTabs(self::TAB_TEMPLATE);
						break;
					case self::CMD_DOWNLOAD_TEMPLATE:
						$this->downloadTemplate();
						$this->setTabs(self::TAB_TEMPLATE);
						break;
					case self::CMD_SHOW_SETTINGS:
						$this->showSettings();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_EDIT_SETTING:
						$this->editSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_UPDATE_SETTING:
						$this->updateSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_ADD_CUSTOM_SETTING:
						$this->addCustomSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_EDIT_CUSTOM_SETTING:
						$this->editCustomSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_CONFIRM_DELETE_CUSTOM_SETTING:
						$this->confirmDeleteCustomSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_DELETE_CUSTOM_SETTING:
						$this->deleteCustomSetting();
						break;
					case self::CMD_SAVE_CUSTOM_SETTING:
						$this->saveCustomSetting();
						$this->setTabs(self::TAB_SETTINGS);
						break;
					case self::CMD_SHOW_PLACEHOLDERS:
						$this->showPlaceholders();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_ADD_PLACEHOLDER:
						$this->addPlaceholder();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_EDIT_PLACEHOLDER:
						$this->editPlaceholder();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_UPDATE_PLACEHOLDER:
						$this->updatePlaceholder();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_CREATE_PLACEHOLDER:
						$this->createPlaceholder();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_DELETE_PLACEHOLDER:
						$this->deletePlaceholder();
						break;
					case self::CMD_CONFIRM_DELETE_PLACEHOLDER:
						$this->confirmDeletePlaceholder();
						$this->setTabs(self::TAB_PLACEHOLDERS);
						break;
					case self::CMD_SHOW_SIGNATURES:
						$this->showSignatures();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_ADD_SIGNATURE:
						$this->addSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_EDIT_SIGNATURE:
						$this->editSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_CREATE_SIGNATURE:
						$this->createSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_UPDATE_SIGNATURE:
						$this->updateSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_CONFIRM_DELETE_SIGNATURE:
						$this->confirmDeleteSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_DELETE_SIGNATURE:
						$this->deleteSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case self::CMD_DOWNLOAD_SIGNATURE:
						$this->downloadSignature();
						$this->setTabs(self::TAB_SIGNATURES);
						break;
					case '':
						$this->showTypes();
						break;
				}
				break;
		}
		$this->tpl->show();
	}


	/**
	 * Add tabs to GUI
	 *
	 * @param string $active_tab_id ID of activated tab
	 */
	protected function setTabs($active_tab_id = self::TAB_GENERAL) {
		$this->tabs->addTab(self::TAB_GENERAL, $this->pl->txt(self::TAB_GENERAL), $this->ctrl->getLinkTarget($this, self::CMD_EDIT_TYPE));
		if ($this->type) {
			$this->tabs->addTab(self::TAB_TEMPLATE, $this->pl->txt(self::TAB_TEMPLATE), $this->ctrl->getLinkTarget($this, self::CMD_EDIT_TEMPLATE));
			$this->tabs->addTab(self::TAB_SETTINGS, $this->lng->txt(self::TAB_SETTINGS), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_SETTINGS));
			$this->tabs->addTab(self::TAB_PLACEHOLDERS, $this->pl->txt(self::TAB_PLACEHOLDERS), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_PLACEHOLDERS));
			$this->tabs->addTab(self::TAB_SIGNATURES, $this->pl->txt(self::TAB_SIGNATURES), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_SIGNATURES));
			$this->tpl->setTitle($this->type->getTitle());
			$this->tpl->setDescription($this->type->getDescription());
		}
		$this->tabs->activateTab($active_tab_id);
		$this->tabs->setBackTarget($this->pl->txt('back_to_overview'), $this->ctrl->getLinkTarget($this));
	}


	/**
	 * Show existing certificate types in table
	 */
	public function showTypes() {
		$this->tpl->setTitle($this->pl->txt('manage_cert_types'));
		$table = new srCertificateTypeTableGUI($this, self::CMD_SHOW_TYPES);
		$this->tpl->setContent($table->getHTML());
	}


	/**
	 * Show form for creating a type
	 */
	public function addType() {
		$form = new srCertificateTypeFormGUI($this, new srCertificateType());
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Show form for editing a type (General)
	 */
	public function editType() {
		$form = new srCertificateTypeFormGUI($this, $this->type);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Show form for editing template settings of a type
	 */
	public function editTemplate() {
		$form = new srCertificateTypeTemplateFormGUI($this, $this->type);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Update template related stuff
	 */
	public function updateTemplate() {
		$form = new srCertificateTypeTemplateFormGUI($this, $this->type);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_type_saved'), true);
			$this->ctrl->redirect($this, self::CMD_EDIT_TEMPLATE);
		} else {
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * Download default template
	 */
	public function downloadDefaultTemplate() {
		ilUtil::deliverFile('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/resources/template.jrxml', 'template.jrxml');
	}


	/**
	 * Download template file
	 */
	public function downloadTemplate() {
		if (is_file($this->type->getCertificateTemplatesPath(true))) {
			$filename = srCertificateTemplateTypeFactory::getById($this->type->getTemplateTypeId())->getTemplateFilename();
			ilUtil::deliverFile($this->type->getCertificateTemplatesPath(true), $filename);
		}
		$this->editTemplate();
	}


	/**
	 * Show table with settings
	 */
	public function showSettings() {
		$button = ilLinkButton::getInstance();
		$button->setCaption($this->pl->txt('add_new_custom_setting'), false);
		$button->setUrl($this->ctrl->getLinkTargetByClass(srCertificateTypeGUI::class, self::CMD_ADD_CUSTOM_SETTING));
		$this->toolbar->addButtonInstance($button);
		$table = new srCertificateTypeSettingsTableGUI($this, self::CMD_SHOW_SETTINGS, $this->type);
		$table_custom_settings = new srCertificateTypeCustomSettingsTableGUI($this, self::CMD_SHOW_SETTINGS, $this->type);
		$spacer = '<div style="height: 30px;"></div>';
		$this->tpl->setContent($table->getHTML() . $spacer . $table_custom_settings->getHTML());
	}


	public function confirmDeleteCustomSetting() {
		/** @var srCertificateCustomTypeSetting $setting */
		$setting = srCertificateCustomTypeSetting::findOrFail((int)$_GET['custom_setting_id']);
		$gui = new ilConfirmationGUI();
		$gui->setFormAction($this->ctrl->getFormAction($this));
		$gui->setHeaderText($this->pl->txt('info_delete_custom_setting'));
		$gui->addItem('custom_setting_id', $setting->getId(), $setting->getLabel($this->user->getLanguage()));
		$gui->setConfirm($this->lng->txt('confirm'), self::CMD_DELETE_CUSTOM_SETTING);
		$gui->setCancel($this->lng->txt('cancel'), self::CMD_SHOW_SETTINGS);
		$this->tpl->setContent($gui->getHTML());
	}


	public function deleteCustomSetting() {
		$setting = srCertificateCustomTypeSetting::findOrFail((int)$_POST['custom_setting_id']);
		$setting->delete();
		ilUtil::sendSuccess($this->pl->txt('msg_success_custom_setting_deleted'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_SETTINGS);
	}


	public function confirmDeletePlaceholder() {
		/** @var srCertificatePlaceholder $placeholder */
		$placeholder = srCertificatePlaceholder::find((int)$_GET['placeholder_id']);
		$gui = new ilConfirmationGUI();
		$gui->setFormAction($this->ctrl->getFormAction($this));
		$gui->setHeaderText($this->pl->txt('info_delete_custom_placeholder'));
		$gui->addItem('placeholder_id', $placeholder->getId(), $placeholder->getLabel($this->user->getLanguage()));
		$gui->setConfirm($this->lng->txt('confirm'), self::CMD_DELETE_PLACEHOLDER);
		$gui->setCancel($this->lng->txt('cancel'), self::CMD_SHOW_PLACEHOLDERS);
		$this->tpl->setContent($gui->getHTML());
	}


	public function deletePlaceholder() {
		$placeholder = srCertificatePlaceholder::findOrFail((int)$_POST['placeholder_id']);
		$placeholder->delete();
		ilUtil::sendSuccess($this->pl->txt('msg_success_custom_placeholder_deleted'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_PLACEHOLDERS);
	}


	/**
	 * Show form for editing settings of a type
	 */
	public function editSetting() {
		try {
			$form = new srCertificateTypeSettingFormGUI($this, $this->type, $_REQUEST['identifier']);
			$this->tpl->setContent($form->getHTML());
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SETTINGS);
		}
	}


	/**
	 * Update settings
	 */
	public function updateSetting() {
		try {
			$form = new srCertificateTypeSettingFormGUI($this, $this->type, $_REQUEST['identifier']);
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('msg_setting_saved'), true);
				$this->ctrl->redirect($this, self::CMD_SHOW_SETTINGS);
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SETTINGS);
		}
	}


	/**
	 * @return string
	 */
	public function addCustomSetting() {
		$form = new srCertificateCustomTypeSettingFormGUI($this, new srCertificateCustomTypeSetting());
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * @return string
	 */
	public function editCustomSetting() {
		$form = new srCertificateCustomTypeSettingFormGUI($this, srCertificateCustomTypeSetting::find((int)$_GET['custom_setting_id']));
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Create/Update a custom setting
	 */
	public function saveCustomSetting() {
		if (isset($_POST['custom_setting_id']) && $_POST['custom_setting_id']) {
			$setting = srCertificateCustomTypeSetting::find((int)$_POST['custom_setting_id']);
		} else {
			$setting = new srCertificateCustomTypeSetting();
		}

		$form = new srCertificateCustomTypeSettingFormGUI($this, $setting);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_setting_saved'), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SETTINGS);
		} else {
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * Show table with available placeholders for this type
	 */
	public function showPlaceholders() {
		$table1 = new srCertificateTypeStandardPlaceholdersTableGUI($this, self::CMD_SHOW_PLACEHOLDERS);
		$table2 = new srCertificateTypePlaceholdersTableGUI($this, self::CMD_SHOW_PLACEHOLDERS, $this->type);
		$spacer = '<div style="height: 30px;"></div>';
		$this->tpl->setContent($table1->getHTML() . $spacer . $table2->getHTML());
		ilUtil::sendInfo(sprintf($this->pl->txt('msg_placeholder_format_info'), srCertificatePlaceholder::PLACEHOLDER_START_SYMBOL, srCertificatePlaceholder::PLACEHOLDER_END_SYMBOL));
	}


	/**
	 * Add a new placeholder
	 */
	public function addPlaceholder() {
		$placeholder = new srCertificatePlaceholder();
		$placeholder->setCertificateType($this->type);
		$form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Show form for editing a placeholder
	 */
	public function editPlaceholder() {
		try {
			$placeholder = srCertificatePlaceholder::find($_REQUEST['placeholder_id']);
			if ($placeholder === NULL) {
				throw new ilException("Placeholder with ID " . $_REQUEST['placeholder_id'] . " not found");
			}
			$form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
			$this->tpl->setContent($form->getHTML());
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_PLACEHOLDERS);
		}
	}


	/**
	 * Create a new placeholder
	 */
	public function createPlaceholder() {
		$placeholder = new srCertificatePlaceholder();
		$placeholder->setCertificateType($this->type);
		$form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_placeholder_saved'), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_PLACEHOLDERS);
		} else {
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * Update placeholder
	 */
	public function updatePlaceholder() {
		try {
			$placeholder = srCertificatePlaceholder::find($_REQUEST['placeholder_id']);
			if ($placeholder === NULL) {
				throw new srCertificateException("Placeholder with ID " . $_REQUEST['placeholder_id'] . " not found");
			}
			$form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('msg_placeholder_saved'), true);
				$this->ctrl->redirect($this, self::CMD_SHOW_PLACEHOLDERS);
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		} catch (ilException $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_PLACEHOLDERS);
		}
	}


	/**
	 * Show form for editing singatures
	 */
	public function showSignatures() {
		$table = new srCertificateTypeSignaturesTableGUI($this, self::CMD_SHOW_SIGNATURES, $this->type);
		$this->tpl->setContent($table->getHTML());
	}


	/**
	 * Add a new placeholder
	 */
	public function addSignature() {
		$signature = new srCertificateSignature();
		$signature->setCertificateType($this->type);
		$form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * Create a new signature
	 */
	public function createSignature() {
		$signature = new srCertificateSignature();
		$signature->setCertificateType($this->type);
		$form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_signature_saved'), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SIGNATURES);
		} else {
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 *
	 */
	public function editSignature() {
		try {
			$signature = srCertificateSignature::find((int)$_GET['signature_id']);
			if ($signature === NULL) {
				throw new ilException("Signature with ID " . (int)$_GET['signature_id'] . " not found");
			}
			$form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
			$this->tpl->setContent($form->getHTML());
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SIGNATURES);
		}
	}


	/**
	 * Update signature related stuff
	 */
	public function updateSignature() {
		try {
			$signature = srCertificateSignature::find($_GET['signature_id']);
			if ($signature === NULL) {
				throw new srCertificateException("Signature with ID " . $_GET['signature_id'] . " not found");
			}
			$form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
			if ($form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('msg_signature_saved'), true);
				$this->ctrl->redirect($this, self::CMD_SHOW_SIGNATURES);
			} else {
				$this->tpl->setContent($form->getHTML());
			}
		} catch (ilException $e) {
			ilUtil::sendFailure($e->getMessage(), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_SIGNATURES);
		}
	}


	/**
	 *
	 */
	public function confirmDeleteSignature() {
		$signature = srCertificateSignature::find($_GET['signature_id']);
		$item_html = $signature->getFirstName() . " " . $signature->getLastName() . '<br>';
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->pl->txt('common_back'), $this->ctrl->getLinkTarget($this, self::CMD_VIEW));
		ilUtil::sendQuestion($this->pl->txt('signatures_confirm_delete'));

		$toolbar = new ilToolbarGUI();
		$this->ctrl->saveParameter($this, 'signature_id');
		$button = ilLinkButton::getInstance();
		$button->setCaption($this->pl->txt('confirm'), false);
		$button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_DELETE_SIGNATURE));
		$this->toolbar->addButtonInstance($button);
		$button = ilLinkButton::getInstance();
		$button->setCaption($this->pl->txt('cancel'), false);
		$button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_SHOW_SIGNATURES));
		$this->toolbar->addButtonInstance($button);

		$this->tpl->setContent($item_html . '</br>' . $toolbar->getHTML());
	}


	/**
	 *
	 */
	public function deleteSignature() {
		$signature = srCertificateSignature::find($_GET['signature_id']);
		$signature->delete();
		ilUtil::sendSuccess($this->pl->txt('msg_delete_signature_success'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_SIGNATURES);
	}


	public function downloadSignature() {
		$signature = srCertificateSignature::find($_GET['signature_id']);
		$signature->download();
	}


	/**
	 * Create or update a type
	 */
	public function saveType() {
		$type = ($this->type === NULL) ? new srCertificateType() : $this->type;
		$form = new srCertificateTypeFormGUI($this, $type);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_type_saved'), true);
			$this->ctrl->setParameter($this, 'type_id', $type->getId());
			$this->ctrl->redirect($this, self::CMD_EDIT_TYPE);
		} else {
			$this->tpl->setContent($form->getHTML());
		}
	}


	/**
	 * Check permissions
	 */
	protected function checkPermission() {
		$allowed_roles = ilCertificateConfig::getX('roles_administrate_certificate_types');

		return $this->rbac->isAssignedToAtLeastOneGivenRole($this->user->getId(), json_decode($allowed_roles, true));
	}
}