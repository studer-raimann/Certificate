<?php

require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
require_once('./Services/Link/classes/class.ilLink.php');
require_once(dirname(__FILE__) . '/class.srCertificateDefinitionFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateDefinitionPlaceholdersFormGUI.php');
require_once(dirname(__FILE__) . '/class.srCertificateDefinitionTableGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificatePreview.php');

/**
 * GUI-Class srCertificateDefinitionGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
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
        global $tpl, $ilCtrl, $ilToolbar, $ilTabs, $lng, $ilAccess, $ilDB;
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->toolbar = $ilToolbar;
        $this->tabs = $ilTabs;
        $this->ref_id = (int)$_GET['ref_id'];
        $this->crs = ilObjectFactory::getInstanceByRefId($this->ref_id);
        $this->definition = srCertificateDefinition::where(array('ref_id' => $this->ref_id))->first();
        $this->pl = new ilCertificatePlugin();
        $this->lng = $lng;
        $this->access = $ilAccess;
        $this->db = $ilDB;
        $this->ctrl->saveParameter($this, 'ref_id');
//        $this->pl->updateLanguages();
        $this->tpl->addJavaScript($this->pl->getStyleSheetLocation('uihk_certificate.js'));
        $this->lng->loadLanguageModule('common');
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
                        $this->showDefinition();
                        break;
                    case 'showPlaceholders':
                        $this->showPlaceholders();
                        break;
                    case 'showCertificates':
                        $this->showCertificates();
                        break;
                    case 'downloadCertificate':
                        $this->downloadCertificate();
                        break;
                    case 'downloadCertificates':
                        $this->downloadCertificates();
                        break;
                    case 'updateDefinition':
                        $this->updateDefinition();
                        break;
                    case 'confirmTypeChange':
                        $this->confirmTypeChange();
                        break;
                    case 'updateType':
                        $this->updateType();
                        break;
                    case 'createDefinition':
                        $this->createDefinition();
                        break;
                    case 'updatePlaceholders':
                        $this->updatePlaceholders();
                        break;
                    case 'updatePlaceholdersPreview':
                        $this->updatePlaceholders('previewCertificate');
                        break;
                    case 'previewCertificate':
                        $this->previewCertificate();
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

    /**
     * Show Definition settings Form
     */
    public function showDefinition()
    {
        $this->tabs->setSubTabActive('show_definition');
        $definition = ($this->definition === NULL) ? new srCertificateDefinition() : $this->definition;
        $this->form = new srCertificateDefinitionFormGUI($this, $definition);
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Show available Placeholders of Definition
     */
    public function showPlaceholders()
    {
        $this->tabs->setSubTabActive('show_placeholders');
        $definition = srCertificateDefinition::where(array('ref_id' => $this->ref_id))->first();
        $this->form = new srCertificateDefinitionPlaceholdersFormGUI($this, $definition);
        $this->tpl->setContent($this->form->getHTML());
    }

    /**
     * Show all certificates
     */
    public function showCertificates()
    {
        $this->tabs->setSubTabActive("show_certificates");
        $table = new srCertificateDefinitionTableGUI($this, 'showCertificates', $this->definition);
        $this->tpl->setContent($table->getHTML());
    }


    /**
     * Create definition
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
        $preview->generate();
        $preview->download(false);
    }


    /**
     * Download a certificate
     */
    public function downloadCertificate()
    {
        if ($cert_id = (int)$_GET['cert_id']) {
            /** @var srCertificate $cert */
            $cert = srCertificate::find($cert_id);
            $cert->download();
        }
        $this->showCertificates();
    }


    /**
     * Download multiple certificates as ZIP file
     */
    public function downloadCertificates()
    {
        $cert_ids = $_POST['cert_id'];
        if (count($cert_ids)) {
            $zip_filename = date('d-m-Y') . '-' . $this->ref_id . '-certificates';
            // Make a random temp dir in ilias data directory
            $tmp_dir = ilUtil::ilTempnam();
            ilUtil::makeDir($tmp_dir);
            $zip_base_dir = $tmp_dir . DIRECTORY_SEPARATOR . $zip_filename;
            ilUtil::makeDir($zip_base_dir);
            // Copy all PDFs in folder
            foreach ($cert_ids as $cert_id) {
                /** @var srCertificate $cert */
                $cert = srCertificate::find((int)$cert_id);
                if (!is_null($cert) && $cert->getStatus() == srCertificate::STATUS_PROCESSED) {
                    copy($cert->getFilePath(), $zip_base_dir . DIRECTORY_SEPARATOR . $cert->getFilename(true));
                }
            }
            $tmp_zip_file = $tmp_dir . DIRECTORY_SEPARATOR . $zip_filename . '.zip';
            try {
                ilUtil::zip($zip_base_dir, $tmp_zip_file);
                rename($tmp_zip_file, $zip_file = ilUtil::ilTempnam());
                ilUtil::delDir($tmp_dir);
                ilUtil::deliverFile($zip_file, $zip_filename . '.zip', '', false, true);
            } catch (ilFileException $e) {
                ilUtil::sendInfo($e->getMessage());
            }
        }
        $this->showCertificates();
    }

    /**
     * Display INFO/Warning Screen if the type was changed by user
     */
    public function confirmTypeChange()
    {
        $new_type_id = (int)$_POST['type_id'];
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
     */
    public function updateType()
    {
        $new_type_id = (int)$_POST['type_id'];
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
     */
    protected function setSubTabs()
    {
        if ($this->definition !== NULL) {
            $this->tabs->addSubTab('show_certificates', 'Show Certificates', $this->ctrl->getLinkTarget($this, 'showCertificates'));
        }
        $this->tabs->addSubTab('show_definition', 'Definition settings', $this->ctrl->getLinkTarget($this, 'showDefinition'));
        if ($this->definition !== NULL) {
            $this->tabs->addSubTab('show_placeholders', 'Placeholders', $this->ctrl->getLinkTarget($this, 'showPlaceholders'));
        }
    }


    /**
     * Set Course title and icon in header
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