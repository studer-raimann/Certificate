<?php
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * GUI-Class srCertificateDefinitionGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_isCalledBy srCertificateDefinitionGUI: ilRouterGUI, ilUIPluginRouterGUI
 * @ilCtrl_Calls      srCertificateDefinitionGUI: srCertificateDefinitionFormGUI
 */
class srCertificateDefinitionGUI {

	const CMD_BUILD_ACTIONS = 'buildActions';
	const CMD_CALL_BACK = 'callBack';
	const CMD_CONFIRM_TYPE_CHANGE = 'confirmTypeChange';
	const CMD_CREATE_DEFINITION = 'createDefinition';
	const CMD_DOWNLOAD_CERTIFICATE = 'downloadCertificate';
	const CMD_DOWNLOAD_CERTIFICATES = 'downloadCertificates';
	const CMD_PREVIEW_CERTIFICATE = 'previewCertificate';
	const CMD_RETRY_GENERATION = 'retryGeneration';
	const CMD_SET_DATE = 'setDate';
	const CMD_SET_DATE_AND_CREATE = 'setDateAndCreate';
	const CMD_SHOW_CERTIFICATES = 'showCertificates';
	const CMD_SHOW_DEFINITION = 'showDefinition';
	const CMD_SHOW_PARTICIPANTS = 'showParticipants';
	const CMD_SHOW_PLACEHOLDERS = 'showPlaceholders';
	const CMD_UNDO_CALL_BACK = 'undoCallBack';
	const CMD_UPDATE_DEFINITION = 'updateDefinition';
	const CMD_UPDATE_PLACEHOLDERS = 'updatePlaceholders';
	const CMD_UPDATE_PLACEHOLDERS_PREVIEW = 'updatePlaceholdersPreview';
	const CMD_UPDATE_TYPE = 'updateType';
	const TAB_SHOW_CERTIFICATES = 'show_certificates';
	const TAB_SHOW_DEFINITION = 'show_definition';
	const TAB_SHOW_PARTICIPANTS = 'show_participants';
	const TAB_SHOW_PLACEHOLDERS = 'show_placeholders';
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var srCertificateDefinitionFormGUI
	 */
	protected $form;
	/**
	 * @var ilObjCourse
	 */
	protected $crs;
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
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var int
	 */
	protected $ref_id;
	/**
	 * @var srCertificateDefinition
	 */
	protected $definition;
	/**
	 * @var ilDB
	 */
	protected $db;
    /**
     * @var ilLogger
     */
	protected $log;


	public function __construct() {
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->toolbar = $DIC->toolbar();
		$this->tabs = $DIC->tabs();
		$this->ref_id = (int)$_GET['ref_id'];
		$this->crs = ilObjectFactory::getInstanceByRefId($this->ref_id);
		$this->definition = srCertificateDefinition::where(array( 'ref_id' => $this->ref_id ))->first();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->access = $DIC->access();
		$this->db = $DIC->database();
		$this->log = $DIC->logger()->root();
		$this->ctrl->saveParameter($this, 'ref_id');
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('uihk_certificate.js'));
		$ilLocator = $DIC["ilLocator"];
		$ilLocator->addRepositoryItems();
		$this->tpl->setVariable("LOCATOR", $ilLocator->getHTML());
	}


	public function executeCommand() {
		$this->checkPermission();
		$this->initHeader();
		$this->setSubTabs();
		$cmd = $this->ctrl->getCmd();
		$next_class = $this->ctrl->getNextClass($this);
		$this->tpl->getStandardTemplate();
		switch ($next_class) {
            case strtolower(srCertificateDefinitionFormGUI::class):
                $this->initForm();
                return $this->ctrl->forwardCommand($this->form);
			case '':
				switch ($cmd) {
					case self::CMD_SHOW_DEFINITION:
					case self::CMD_SHOW_PLACEHOLDERS:
					case self::CMD_SHOW_CERTIFICATES:
					case self::CMD_SHOW_PARTICIPANTS:
					case self::CMD_DOWNLOAD_CERTIFICATE:
					case self::CMD_DOWNLOAD_CERTIFICATES:
					case self::CMD_UPDATE_DEFINITION:
					case self::CMD_CONFIRM_TYPE_CHANGE:
					case self::CMD_UPDATE_TYPE:
					case self::CMD_CREATE_DEFINITION:
					case self::CMD_UPDATE_PLACEHOLDERS:
					case self::CMD_PREVIEW_CERTIFICATE:
					case self::CMD_BUILD_ACTIONS:
					case self::CMD_SET_DATE_AND_CREATE:
					case self::CMD_SET_DATE:
						$this->$cmd();
						break;
					case self::CMD_UPDATE_PLACEHOLDERS_PREVIEW:
						$this->updatePlaceholders(self::CMD_PREVIEW_CERTIFICATE);
						break;
					case self::CMD_CALL_BACK:
					case self::CMD_UNDO_CALL_BACK:
					case self::CMD_RETRY_GENERATION:
						/** @var srCertificate $certificate */
						$certificate = srCertificate::find((int)$_GET['cert_id']);
						if ($certificate->getDefinitionId() == $this->definition->getId()) {
							$this->$cmd($certificate);
						}
						break;
					case '':
						if ($this->definition) {
							$this->showCertificates();
						} else {
							$this->showDefinition();
						}
				}
				break;
		}
		$this->tpl->show();
	}


	protected function showPreviewCertificateInToolbar() {
		if ($this->definition) {
			if (is_file($this->definition->getType()->getCertificateTemplatesPath(true))) {
				$button = ilLinkButton::getInstance();
				$button->setCaption($this->pl->txt('preview_certificate'), false);
				$button->setUrl($this->ctrl->getLinkTarget($this, self::CMD_PREVIEW_CERTIFICATE));
				$this->toolbar->addButtonInstance($button);
			} else {
				ilUtil::sendInfo($this->pl->txt('msg_info_current_type_no_invalid_tempalte'));
			}
		}
	}


	/**
	 * Build action menu for a record asynchronous
	 *
	 */
	protected function buildActions() {
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId((int)$_GET['cert_id']);
		$alist->setListTitle($this->pl->txt('actions'));
		$this->ctrl->setParameter($this, 'cert_id', (int)$_GET['cert_id']);

		switch ($_GET['status']) {
			case srCertificate::STATUS_CALLED_BACK:
				$alist->addItem($this->pl->txt('undo_callback'), self::CMD_UNDO_CALL_BACK, $this->ctrl->getLinkTarget($this, self::CMD_UNDO_CALL_BACK));
				break;
			case srCertificate::STATUS_FAILED:
				$alist->addItem($this->pl->txt('retry'), 'retry', $this->ctrl->getLinkTarget($this, self::CMD_RETRY_GENERATION));
				break;
			case srCertificate::STATUS_PROCESSED:
				$alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, self::CMD_DOWNLOAD_CERTIFICATE));
				$alist->addItem($this->pl->txt('call_back'), 'call_back', $this->ctrl->getLinkTarget($this, self::CMD_CALL_BACK));
				break;
		}

		echo $alist->getHTML(true);
		exit;
	}


	/**
	 * Show Definition settings Form
	 *
	 */
	public function showDefinition() {
		$this->tabs->activateSubTab(self::TAB_SHOW_DEFINITION);
		$this->initForm();
		$this->tpl->setContent($this->form->getHTML());
		if ($this->definition) {
			$this->showPreviewCertificateInToolbar();
		}
	}

    /**
     *
     */
    public function initForm() {
        $definition = ($this->definition === NULL) ? new srCertificateDefinition() : $this->definition;
        $this->form = new srCertificateDefinitionFormGUI($this, $definition);
	}


	/**
	 * Show available Placeholders of Definition
	 *
	 */
	public function showPlaceholders() {
		$this->tabs->activateSubTab(self::TAB_SHOW_PLACEHOLDERS);
		$this->showPreviewCertificateInToolbar();
		/** @var srCertificateDefinition $definition */
		$definition = srCertificateDefinition::where(array( 'ref_id' => $this->ref_id ))->first();
		if (!count($definition->getPlaceholderValues()) && !$this->definition->getType()->getSignatures()) {
			ilUtil::sendInfo($this->pl->txt('msg_no_placeholders'));
		} else {
			$this->form = new srCertificateDefinitionPlaceholdersFormGUI($this, $definition);
			$this->tpl->setContent($this->form->getHTML());
		}
	}


	/**
	 * Show all certificates
	 *
	 */
	public function showCertificates() {
		$this->tabs->activateSubTab(self::TAB_SHOW_CERTIFICATES);
		$this->showPreviewCertificateInToolbar();
		$options = array(
			'columns' => array( 'firstname', 'lastname', 'valid_from', 'valid_to', 'file_version', 'status' ),
			'definition_id' => $this->definition->getId(),
			'show_filter' => false,
		);
		$table = new srCertificateTableGUI($this, self::CMD_SHOW_CERTIFICATES, $options);
		$this->tpl->setContent($table->getHTML());
	}


	/**
	 * Show all participants with the possibility to manually create new certificates
	 *
	 */
	public function showParticipants() {
		$this->tabs->activateSubTab(self::TAB_SHOW_PARTICIPANTS);
		$table = new srCertificateParticipantsTableGUI($this, self::CMD_SHOW_PARTICIPANTS, $this->definition);
		$this->tpl->setContent($table->getHTML());
	}


	/**
	 * Create definition
	 *
	 */
	public function createDefinition() {
		$this->tabs->activateSubTab(self::TAB_SHOW_DEFINITION);
		$definition = new srCertificateDefinition();
		$this->form = new srCertificateDefinitionFormGUI($this, $definition);
		if ($this->form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_definition_created'), true);
			$this->ctrl->redirect($this, self::CMD_SHOW_DEFINITION);
		} else {
			$this->tpl->setContent($this->form->getHTML());
		}
	}


	/**
	 * Update definition settings
	 *
	 */
	public function updateDefinition() {
		$this->tabs->activateSubTab(self::TAB_SHOW_DEFINITION);
		if ($_POST['change_type'] && $_POST['type_id'] != $this->definition->getTypeId()) {
			$this->confirmTypeChange();
		} else {
			$this->form = new srCertificateDefinitionFormGUI($this, $this->definition);
			if ($this->form->saveObject()) {
				ilUtil::sendSuccess($this->pl->txt('msg_definition_updated'), true);
				$this->ctrl->redirect($this, self::CMD_SHOW_DEFINITION);
			} else {
				$this->tpl->setContent($this->form->getHTML());
			}
		}
	}


	/**
	 * Update placeholders
	 *
	 */
	public function updatePlaceholders($redirect_cmd = self::CMD_SHOW_PLACEHOLDERS) {
		$this->tabs->activateSubTab(self::TAB_SHOW_PLACEHOLDERS);
		$this->form = new srCertificateDefinitionPlaceholdersFormGUI($this, $this->definition);
		if ($this->form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_placeholders_updated'), true);
			$this->ctrl->redirect($this, $redirect_cmd);
		} else {
			$this->tpl->setContent($this->form->getHTML());
		}
	}


	/**
	 * Generate a preview certificate for the current definition and download file
	 */
	public function previewCertificate() {
		$preview = new srCertificatePreview();
		$preview->setDefinition($this->definition);
		try {
            $preview->generate();
            $preview->download();
        } catch (Exception $e) {
            $this->log->log($e->getMessage(), ilLogLevel::ERROR);
            ilUtil::sendFailure($this->pl->txt('msg_error_preview_certificate'));
        }
		$this->showCertificates();
	}


	/**
	 * Download a certificate
	 *
	 */
	public function downloadCertificate() {
		if ($cert_id = (int)$_GET['cert_id']) {
			/** @var srCertificate $cert */
			$cert = srCertificate::find($cert_id);
			$cert->download();
		}
		$this->showCertificates();
	}


	/**
	 * Download multiple certificates as ZIP file
	 *
	 */
	public function downloadCertificates() {
		$cert_ids = (array)$_POST['cert_id'];
		$ids = array();
		foreach ($cert_ids as $cert_id) {
			/** @var srCertificate $certificate */
			$certificate = srCertificate::find($cert_id);
			if ($certificate && $certificate->getDefinitionId() == $this->definition->getId()) {
				$ids[] = $cert_id;
			}
		}
		if (count($ids)) {
			srCertificate::downloadAsZip($ids, $this->ref_id . '-certificates');
		}
		$this->showCertificates();
	}


	/**
	 * called by srCertificateParticipantsTableGUI
	 * shows form to choose a date for creating a new certificate manually
	 * save -> setDateAndCreate()
	 */
	public function setDate() {
		$this->tabs->activateSubTab(self::TAB_SHOW_PARTICIPANTS);
		ilUtil::sendInfo($this->pl->txt('set_date_info'));

		if ($_POST['user_id']) {
			$user_ids = $_POST['user_id'];
		} else {
			$user_ids = array( $_GET['user_id'] );
		}

		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));

		$ilHiddenInputGUI = new ilHiddenInputGUI('user_ids');
		$ilHiddenInputGUI->setValue(json_encode($user_ids));
		$form->addItem($ilHiddenInputGUI);

		$ilDateInputGUI = new ilDateTimeInputGUI($this->pl->txt('passed_date'), 'passed_date');
		$form->addItem($ilDateInputGUI);

		$form->addCommandButton(self::CMD_SET_DATE_AND_CREATE, $this->pl->txt('save'));
		$form->addCommandButton(self::CMD_SHOW_PARTICIPANTS, $this->pl->txt('cancel'));

		$this->tpl->setContent($form->getHTML());
	}


	/**
	 * initiates the creation of a new certificate with the chosen date for the chosen user(s)
	 */
	public function setDateAndCreate() {
		$user_ids = json_decode($_POST['user_ids'], true);
		/*$date = $_POST['passed_date']['date'];
		$date_string = $date['y'] . '-' . $date['m'] . '-' . $date['d'];*/
		$date = $_POST['passed_date'];
		$date_string = date("Y-m-d", strtotime($date));
		foreach ($user_ids as $user_id) {
			$cert = new srCertificate();
			$cert->setValidFrom($date_string);
			$cert->setUserId($user_id);
			$cert->setDefinition($this->definition);
			$cert->create();
		}
		ilUtil::sendSuccess($this->pl->txt('msg_cert_created'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_PARTICIPANTS);
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function callBack(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_CALLED_BACK);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_called_back'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_CERTIFICATES);
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function undoCallBack(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_PROCESSED);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_undo_called_back'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_CERTIFICATES);
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function retryGeneration(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_NEW);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_retry_generation'), true);
		$this->ctrl->redirect($this, self::CMD_SHOW_CERTIFICATES);
	}


	/**
	 * Display INFO/Warning Screen if the type was changed by user
	 *
	 */
	public function confirmTypeChange() {
		$new_type_id = (int)$_POST['type_id'];
		$conf_gui = new ilConfirmationGUI();
		$conf_gui->setFormAction($this->ctrl->getFormAction($this));
		$conf_gui->setHeaderText($this->pl->txt('confirm_type_change'));
		$conf_gui->addItem('type_id', $new_type_id, $this->pl->txt('confirm_type_change_text'));
		$conf_gui->setConfirm($this->pl->txt('change'), self::CMD_UPDATE_TYPE);
		$conf_gui->setCancel($this->pl->txt('cancel'), self::CMD_SHOW_DEFINITION);
		$this->tpl->setContent($conf_gui->getHTML());
	}


	/**
	 * Update type of definition
	 *
	 */
	public function updateType() {
		$new_type_id = (int)$_POST['type_id'];
		if ($new_type_id && $new_type_id != $this->definition->getTypeId()) {
			$this->definition->setTypeId($new_type_id);
			$this->definition->update();
			ilUtil::sendSuccess($this->pl->txt('msg_type_updated'), true);
		}
		$this->ctrl->redirect($this, self::CMD_SHOW_DEFINITION);
	}


	/**
	 * Check permission of user
	 * Redirect to course if permission check fails
	 *
	 */
	protected function checkPermission() {
		if (!$this->access->checkAccess('write', '', $this->ref_id)) {
			$this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', $this->ref_id);
			ilUtil::sendFailure($this->pl->txt('msg_no_permission_certificates'), true);
			$this->ctrl->redirectByClass(ilRepositoryGUI::class);
		}
	}


	/**
	 * Set Subtabs
	 *
	 */
	protected function setSubTabs() {
		if ($this->definition !== NULL) {
			$this->tabs->addSubTab(self::TAB_SHOW_CERTIFICATES, $this->pl->txt('show_certificates'), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_CERTIFICATES));
			$this->tabs->addSubTab(self::TAB_SHOW_PARTICIPANTS, $this->pl->txt('participants'), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_PARTICIPANTS));
		}
		$this->tabs->addSubTab(self::TAB_SHOW_DEFINITION, $this->pl->txt('definition_settings'), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_DEFINITION));
		if ($this->definition !== NULL) {
			$this->tabs->addSubTab(self::TAB_SHOW_PLACEHOLDERS, $this->pl->txt('placeholders'), $this->ctrl->getLinkTarget($this, self::CMD_SHOW_PLACEHOLDERS));
		}
	}


	/**
	 * Set Course title and icon in header
	 *
	 */
	protected function initHeader() {
		$lgui = ilObjectListGUIFactory::_getListGUIByType($this->crs->getType());
		$this->tpl->setTitle($this->crs->getTitle());
		$this->tpl->setDescription($this->crs->getDescription());
		if ($this->crs->getOfflineStatus()) {
			$this->tpl->setAlertProperties($lgui->getAlertProperties());
		}
		$this->tpl->setTitleIcon(ilUtil::getTypeIconPath('crs', $this->crs->getId(), 'big'));
		$this->ctrl->setParameterByClass(ilRepositoryGUI::class, 'ref_id', $this->ref_id);
		$this->tabs->setBackTarget($this->pl->txt('back_to_course'), $this->ctrl->getLinkTargetByClass(ilRepositoryGUI::class));
	}
}
