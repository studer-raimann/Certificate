<?php

require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
require_once('./Services/Link/classes/class.ilLink.php');
require_once(dirname(__FILE__) . '/class.srCertificateDefinitionFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateDefinitionPlaceholdersFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateParticipantsTableGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificatePreview.php');
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificateTableGUI.php');
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificate.php');

/**
 * GUI-Class srCertificateDefinitionGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_isCalledBy srCertificateDefinitionGUI: ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateDefinitionGUI
{


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


    public function __construct()
    {
        global $tpl, $ilCtrl, $ilToolbar, $ilTabs, $lng, $ilAccess, $ilDB, $ilLocator;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->toolbar = $ilToolbar;
        $this->tabs = $ilTabs;
        $this->ref_id = (int) $_GET['ref_id'];
        $this->crs = ilObjectFactory::getInstanceByRefId($this->ref_id);
        $this->definition = srCertificateDefinition::where(array('ref_id' => $this->ref_id))->first();
        $this->pl = ilCertificatePlugin::getInstance();
        $this->lng = $lng;
        $this->access = $ilAccess;
        $this->db = $ilDB;
        $this->ctrl->saveParameter($this, 'ref_id');
        $this->tpl->addJavaScript($this->pl->getStyleSheetLocation('uihk_certificate.js'));
        $this->lng->loadLanguageModule('common');
        $ilLocator->addRepositoryItems();
        $this->tpl->setVariable("LOCATOR", $ilLocator->getHTML());
    }


    public function executeCommand()
    {
        $this->checkPermission();
        $this->initHeader();
        $this->setSubTabs();
        $cmd = $this->ctrl->getCmd();
        $next_class = $this->ctrl->getNextClass($this);
        // needed for ILIAS >= 4.5
        if (ilCertificatePlugin::getBaseClass() != 'ilRouterGUI') {
            $this->tpl->getStandardTemplate();
        }
        switch ($next_class) {
            case '':
                switch ($cmd) {
                    case 'showDefinition':
                    case 'showPlaceholders':
                    case 'showCertificates':
                    case 'showParticipants':
                    case 'downloadCertificate':
                    case 'downloadCertificates':
                    case 'updateDefinition':
                    case 'confirmTypeChange':
                    case 'updateType':
                    case 'createDefinition':
                    case 'updatePlaceholders':
                    case 'previewCertificate':
                    case 'buildActions':
	                case 'setDateAndCreate':
	                case 'setDate':
		                $this->$cmd();
		                break;
	                case 'updatePlaceholdersPreview':
                        $this->updatePlaceholders('previewCertificate');
                        break;
                    case 'callBack':
                    case 'undoCallBack':
                    case 'retryGeneration':
                        /** @var srCertificate $certificate */
                        $certificate = srCertificate::find((int) $_GET['cert_id']);
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
        // needed for ILIAS >= 4.5
        if (ilCertificatePlugin::getBaseClass() != 'ilRouterGUI') {
            $this->tpl->show();
        }
    }


    protected function showPreviewCertificateInToolbar()
    {
        if ($this->definition) {
            if (is_file($this->definition->getType()->getCertificateTemplatesPath(true))) {
                $this->toolbar->addButton($this->pl->txt('preview_certificate'), $this->ctrl->getLinkTarget($this, 'previewCertificate'));
            } else {
                ilUtil::sendInfo($this->pl->txt('msg_info_current_type_no_invalid_tempalte'));
            }
        }
    }


    /**
     * Build action menu for a record asynchronous
     *
     */
    protected function buildActions()
    {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId((int) $_GET['cert_id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameter($this, 'cert_id', (int) $_GET['cert_id']);

        switch ($_GET['status']) {
            case srCertificate::STATUS_CALLED_BACK:
                $alist->addItem($this->pl->txt('undo_callback'), 'undoCallback', $this->ctrl->getLinkTarget($this, 'undoCallBack'));
                break;
            case srCertificate::STATUS_FAILED:
                $alist->addItem($this->pl->txt('retry'), 'retry', $this->ctrl->getLinkTarget($this, 'retryGeneration'));
                break;
            case srCertificate::STATUS_PROCESSED:
                $alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, 'downloadCertificate'));
                $alist->addItem($this->pl->txt('call_back'), 'call_back', $this->ctrl->getLinkTarget($this, 'callBack'));
                break;
        }

        echo $alist->getHTML(true);
        exit;
    }


    /**
     * Show Definition settings Form
     *
     */
    public function showDefinition()
    {
        $this->tabs->setSubTabActive('show_definition');
        $definition = ($this->definition === NULL) ? new srCertificateDefinition() : $this->definition;
        $this->form = new srCertificateDefinitionFormGUI($this, $definition);
        $this->tpl->setContent($this->form->getHTML());
        if ($this->definition) {
            $this->showPreviewCertificateInToolbar();
        }
    }


    /**
     * Show available Placeholders of Definition
     *
     */
    public function showPlaceholders()
    {
        $this->tabs->setSubTabActive('show_placeholders');
        $this->showPreviewCertificateInToolbar();
        /** @var srCertificateDefinition $definition */
        $definition = srCertificateDefinition::where(array('ref_id' => $this->ref_id))->first();
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
    public function showCertificates()
    {
        $this->tabs->setSubTabActive("show_certificates");
        $this->showPreviewCertificateInToolbar();
        $options = array(
            'columns' => array('firstname', 'lastname', 'valid_from', 'valid_to', 'file_version', 'status'),
            'definition_id' => $this->definition->getId(),
            'show_filter' => false,
        );
        $table = new srCertificateTableGUI($this, 'showCertificates', $options);
        $this->tpl->setContent($table->getHTML());
    }
    /**
     * Show all participants with the possibility to manually create new certificates
     *
     */
    public function showParticipants()
    {
        $this->tabs->setSubTabActive("show_participants");
        $table = new srCertificateParticipantsTableGUI($this, 'showParticipants', $this->definition);
        $this->tpl->setContent($table->getHTML());
    }


    /**
     * Create definition
     *
     */
    public function createDefinition()
    {
        $this->tabs->setSubTabActive("show_definition");
        $definition = new srCertificateDefinition();
        $this->form = new srCertificateDefinitionFormGUI($this, $definition);
        if ($this->form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_definition_created'), true);
            $this->ctrl->redirect($this, 'showDefinition');
        } else {
            $this->tpl->setContent($this->form->getHTML());
        }
    }


    /**
     * Update definition settings
     *
     */
    public function updateDefinition()
    {
        $this->tabs->setSubTabActive("show_definition");
        if ($_POST['change_type'] && $_POST['type_id'] != $this->definition->getTypeId()) {
            $this->confirmTypeChange();
        } else {
            $this->form = new srCertificateDefinitionFormGUI($this, $this->definition);
            if ($this->form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('msg_definition_updated'), true);
                $this->ctrl->redirect($this, 'showDefinition');
            } else {
                $this->tpl->setContent($this->form->getHTML());
            }
        }
    }


    /**
     * Update placeholders
     *
     */
    public function updatePlaceholders($redirect_cmd = 'showPlaceholders')
    {
        $this->tabs->setSubTabActive("show_placeholders");
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
    public function previewCertificate()
    {
        $preview = new srCertificatePreview();
        $preview->setDefinition($this->definition);
        if ($preview->generate()) {
            $preview->download();
        }
        ilUtil::sendFailure($this->pl->txt('msg_error_preview_certificate'));
        $this->showCertificates();
    }


    /**
     * Download a certificate
     *
     */
    public function downloadCertificate()
    {
        if ($cert_id = (int) $_GET['cert_id']) {
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
    public function downloadCertificates()
    {
        $cert_ids = (array) $_POST['cert_id'];
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
    	$this->tabs->setSubTabActive('show_participants');
    	ilUtil::sendInfo($this->pl->txt('set_date_info'));

    	if ($_POST['user_id']) {
			$user_ids = $_POST['user_id'];
	    } else {
    		$user_ids = array($_GET['user_id']);
	    }

	    $form = new ilPropertyFormGUI();
	    $form->setFormAction($this->ctrl->getFormAction($this));

	    $ilHiddenInputGUI = new ilHiddenInputGUI('user_ids');
    	$ilHiddenInputGUI->setValue(json_encode($user_ids));
    	$form->addItem($ilHiddenInputGUI);

    	$ilDateInputGUI = new ilDateTimeInputGUI($this->pl->txt('passed_date'), 'passed_date');
    	$form->addItem($ilDateInputGUI);

    	$form->addCommandButton('setDateAndCreate', $this->lng->txt('save'));
    	$form->addCommandButton('showParticipants', $this->lng->txt('cancel'));

    	$this->tpl->setContent($form->getHTML());
    }


	/**
	 * initiates the creation of a new certificate with the chosen date for the chosen user(s)
	 */
    public function setDateAndCreate() {
    	$user_ids = json_decode($_POST['user_ids'], true);
    	$date = $_POST['passed_date']['date'];
    	$date_string = $date['y'] . '-' . $date['m'] . '-' . $date['d'];
    	foreach ($user_ids as $user_id) {
		    $cert = new srCertificate();
		    $cert->setValidFrom($date_string);
		    $cert->setUserId($user_id);
		    $cert->setDefinition($this->definition);
		    $cert->create();
	    }
	    ilUtil::sendSuccess($this->pl->txt('msg_cert_created'), true);
	    $this->ctrl->redirect($this, 'showParticipants');
    }


    /**
     * @param srCertificate $certificate
     */
    protected function callBack(srCertificate $certificate)
    {
        $certificate->setStatus(srCertificate::STATUS_CALLED_BACK);
        $certificate->update();
        ilUtil::sendSuccess($this->pl->txt('msg_called_back'), true);
        $this->ctrl->redirect($this, 'showCertificates');
    }


    /**
     * @param srCertificate $certificate
     */
    protected function undoCallBack(srCertificate $certificate)
    {
        $certificate->setStatus(srCertificate::STATUS_PROCESSED);
        $certificate->update();
        ilUtil::sendSuccess($this->pl->txt('msg_undo_called_back'), true);
        $this->ctrl->redirect($this, 'showCertificates');
    }


    /**
     * @param srCertificate $certificate
     */
    protected function retryGeneration(srCertificate $certificate)
    {
        $certificate->setStatus(srCertificate::STATUS_NEW);
        $certificate->update();
        ilUtil::sendSuccess($this->pl->txt('msg_retry_generation'), true);
        $this->ctrl->redirect($this, 'showCertificates');
    }


    /**
     * Display INFO/Warning Screen if the type was changed by user
     *
     */
    public function confirmTypeChange()
    {
        $new_type_id = (int) $_POST['type_id'];
        $conf_gui = new ilConfirmationGUI();
        $conf_gui->setFormAction($this->ctrl->getFormAction($this));
        $conf_gui->setHeaderText($this->pl->txt('confirm_type_change'));
        $conf_gui->addItem('type_id', $new_type_id, $this->pl->txt('confirm_type_change_text'));
        $conf_gui->setConfirm($this->lng->txt('change'), 'updateType');
        $conf_gui->setCancel($this->lng->txt('cancel'), 'showDefinition');
        $this->tpl->setContent($conf_gui->getHTML());
    }


    /**
     * Update type of definition
     *
     */
    public
    function updateType()
    {
        $new_type_id = (int) $_POST['type_id'];
        if ($new_type_id && $new_type_id != $this->definition->getTypeId()) {
            $this->definition->setTypeId($new_type_id);
            $this->definition->update();
            ilUtil::sendSuccess($this->pl->txt('msg_type_updated'), true);
        }
        $this->ctrl->redirect($this, 'showDefinition');
    }


    /**
     * Check permission of user
     * Redirect to course if permission check fails
     *
     */
    protected function checkPermission()
    {
        if (!$this->access->checkAccess('write', '', $this->ref_id)) {
            $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $this->ref_id);
            ilUtil::sendFailure($this->pl->txt('msg_no_permission_certificates'), true);
            $this->ctrl->redirectByClass('ilrepositorygui');
        }
    }


    /**
     * Set Subtabs
     *
     */
    protected function setSubTabs()
    {
        if ($this->definition !== NULL) {
            $this->tabs->addSubTab('show_certificates', 'Show Certificates', $this->ctrl->getLinkTarget($this, 'showCertificates'));
            $this->tabs->addSubTab('show_participants', 'Participants', $this->ctrl->getLinkTarget($this, 'showParticipants'));
        }
        $this->tabs->addSubTab('show_definition', 'Definition settings', $this->ctrl->getLinkTarget($this, 'showDefinition'));
        if ($this->definition !== NULL) {
            $this->tabs->addSubTab('show_placeholders', 'Placeholders', $this->ctrl->getLinkTarget($this, 'showPlaceholders'));
        }
    }


    /**
     * Set Course title and icon in header
     *
     */
    protected function initHeader()
    {
        $lgui = ilObjectListGUIFactory::_getListGUIByType($this->crs->getType());
        $this->tpl->setTitle($this->crs->getTitle());
        $this->tpl->setDescription($this->crs->getDescription());
        if ($this->crs->getOfflineStatus())
            $this->tpl->setAlertProperties($lgui->getAlertProperties());
        $this->tpl->setTitleIcon(ilUtil::getTypeIconPath('crs', $this->crs->getId(), 'big'));
        $this->ctrl->setParameterByClass('ilrepositorygui', 'ref_id', $this->ref_id);
        $this->tabs->setBackTarget($this->pl->txt('back_to_course'), $this->ctrl->getLinkTargetByClass('ilrepositorygui'));
    }
}