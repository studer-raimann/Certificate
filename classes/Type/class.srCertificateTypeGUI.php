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
class srCertificateTypeGUI
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
     * @var ilTemplate
     */
    protected $tpl;

    /**
     * @var ilToolbar
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


    public function __construct()
    {
        global $tpl, $ilCtrl, $ilToolbar, $ilTabs, $lng, $ilAccess, $ilDB, $rbacreview, $ilUser;
        /** @var ilCtrl ctrl */
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->toolbar = $ilToolbar;
        $this->tabs = $ilTabs;
        $this->type = (isset($_GET['type_id'])) ? srCertificateType::find((int) $_GET['type_id']) : null;
        $this->pl = ilCertificatePlugin::getInstance();
        $this->lng = $lng;
        $this->access = $ilAccess;
        $this->db = $ilDB;
        $this->tpl->addJavaScript($this->pl->getStyleSheetLocation('uihk_certificate.js'));
        $this->lng->loadLanguageModule('common');
        $this->tpl->setTitleIcon(ilCertificatePlugin::getPluginIconImage());
        $this->rbac = $rbacreview;
        $this->user = $ilUser;
    }


    public function executeCommand()
    {
        if (!$this->checkPermission()) {
            ilUtil::sendFailure($this->pl->txt('msg_no_permission'), true);
            $this->ctrl->redirectByClass('ilpersonaldesktopgui');
        }

        global $ilMainMenu;
        $ilMainMenu->setActive('none');

        $cmd = $this->ctrl->getCmd();
        $next_class = $this->ctrl->getNextClass($this);

        if (!in_array($cmd, array('addType', ''))) {
            $this->ctrl->saveParameter($this, 'type_id');
            $this->ctrl->saveParameter($this, 'signature_id');
        }
        // needed for ILIAS >= 4.5
        if (ilCertificatePlugin::getBaseClass() != 'ilRouterGUI') {
            $this->tpl->getStandardTemplate();
        }
        switch ($next_class) {
            case '':
                switch ($cmd) {
                    case 'showTypes':
                        $this->showTypes();
                        break;
                    case 'editType':
                        $this->editType();
                        $this->setTabs('general');
                        break;
                    case 'addType':
                        $this->addType();
                        $this->setTabs('general');
                        break;
                    case 'saveType':
                        $this->saveType();
                        $this->setTabs('general');
                        break;
                    case 'editTemplate':
                        $this->editTemplate();
                        $this->setTabs('template');
                        break;
                    case 'updateTemplate':
                        $this->updateTemplate();
                        $this->setTabs('template');
                        break;
                    case 'downloadDefaultTemplate':
                        $this->downloadDefaultTemplate();
                        $this->setTabs('template');
                        break;
                    case 'downloadTemplate':
                        $this->downloadTemplate();
                        $this->setTabs('template');
                        break;
                    case 'showSettings':
                        $this->showSettings();
                        $this->setTabs('settings');
                        break;
                    case 'editSetting':
                        $this->editSetting();
                        $this->setTabs('settings');
                        break;
                    case 'updateSetting':
                        $this->updateSetting();
                        $this->setTabs('settings');
                        break;
                    case 'addCustomSetting':
                        $this->addCustomSetting();
                        $this->setTabs('settings');
                        break;
                    case 'editCustomSetting':
                        $this->editCustomSetting();
                        $this->setTabs('settings');
                        break;
                    case 'confirmDeleteCustomSetting':
                        $this->confirmDeleteCustomSetting();
                        $this->setTabs('settings');
                        break;
                    case 'deleteCustomSetting':
                        $this->deleteCustomSetting();
                        break;
                    case 'saveCustomSetting':
                        $this->saveCustomSetting();
                        $this->setTabs('settings');
                        break;
                    case 'showPlaceholders':
                        $this->showPlaceholders();
                        $this->setTabs('placeholders');
                        break;
                    case 'addPlaceholder':
                        $this->addPlaceholder();
                        $this->setTabs('placeholders');
                        break;
                    case 'editPlaceholder':
                        $this->editPlaceholder();
                        $this->setTabs('placeholders');
                        break;
                    case 'updatePlaceholder':
                        $this->updatePlaceholder();
                        $this->setTabs('placeholders');
                        break;
                    case 'createPlaceholder':
                        $this->createPlaceholder();
                        $this->setTabs('placeholders');
                        break;
                    case 'deletePlaceholder':
                        $this->deletePlaceholder();
                        break;
                    case 'confirmDeletePlaceholder':
                        $this->confirmDeletePlaceholder();
                        $this->setTabs('placeholders');
                        break;
                    case 'showSignatures':
                        $this->showSignatures();
                        $this->setTabs('signatures');
                        break;
                    case 'addSignature':
                        $this->addSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'editSignature':
                        $this->editSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'createSignature':
                        $this->createSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'updateSignature':
                        $this->updateSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'confirmDeleteSignature':
                        $this->confirmDeleteSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'deleteSignature':
                        $this->deleteSignature();
                        $this->setTabs('signatures');
                        break;
                    case 'downloadSignature':
                        $this->downloadSignature();
                        $this->setTabs('signatures');
                        break;
                    case '':
                        $this->showTypes();
                        break;
                }
                break;
        }
        // needed for ILIAS >= 4.5
        if (ilCertificatePlugin::getBaseClass() != 'ilRouterGUI') {
            $this->tpl->show();
        }
    }


    /**
     * Add tabs to GUI
     *
     * @param string $active_tab_id ID of activated tab
     */
    protected function setTabs($active_tab_id = 'general')
    {
        $this->tabs->addTab('general', $this->pl->txt('general'), $this->ctrl->getLinkTarget($this, 'editType'));
        if ($this->type) {
            $this->tabs->addTab('template', $this->pl->txt('template'), $this->ctrl->getLinkTarget($this, 'editTemplate'));
            $this->tabs->addTab('settings', $this->lng->txt('settings'), $this->ctrl->getLinkTarget($this, 'showSettings'));
            $this->tabs->addTab('placeholders', $this->pl->txt('placeholders'), $this->ctrl->getLinkTarget($this, 'showPlaceholders'));
            $this->tabs->addTab('signatures', $this->pl->txt('signatures'), $this->ctrl->getLinkTarget($this, 'showSignatures'));
            $this->tpl->setTitle($this->type->getTitle());
            $this->tpl->setDescription($this->type->getDescription());
        }
        $this->tabs->activateTab($active_tab_id);
        $this->tabs->setBackTarget($this->pl->txt('back_to_overview'), $this->ctrl->getLinkTarget($this));
    }


    /**
     * Show existing certificate types in table
     */
    public function showTypes()
    {
        $this->tpl->setTitle($this->pl->txt('manage_cert_types'));
        $table = new srCertificateTypeTableGUI($this, 'showTypes');
        $this->tpl->setContent($table->getHTML());
    }


    /**
     * Show form for creating a type
     */
    public function addType()
    {
        $form = new srCertificateTypeFormGUI($this, new srCertificateType());
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Show form for editing a type (General)
     */
    public function editType()
    {
        $form = new srCertificateTypeFormGUI($this, $this->type);
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Show form for editing template settings of a type
     */
    public function editTemplate()
    {
        $form = new srCertificateTypeTemplateFormGUI($this, $this->type);
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Update template related stuff
     */
    public function updateTemplate()
    {
        $form = new srCertificateTypeTemplateFormGUI($this, $this->type);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_type_saved'), true);
            $this->ctrl->redirect($this, 'editTemplate');
        } else {
            $this->tpl->setContent($form->getHTML());
        }
    }


    /**
     * Download default template
     */
    public function downloadDefaultTemplate()
    {
        ilUtil::deliverFile('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/resources/template.jrxml', 'template.jrxml');
    }


    /**
     * Download template file
     */
    public function downloadTemplate()
    {
        if (is_file($this->type->getCertificateTemplatesPath(true))) {
            $filename = srCertificateTemplateTypeFactory::getById($this->type->getTemplateTypeId())->getTemplateFilename();
            ilUtil::deliverFile($this->type->getCertificateTemplatesPath(true), $filename);
        }
        $this->editTemplate();
    }


    /**
     * Show table with settings
     */
    public function showSettings()
    {
	    $button = ilLinkButton::getInstance();
	    $button->setCaption($this->pl->txt('add_new_custom_setting'), false);
	    $button->setUrl($this->ctrl->getLinkTargetByClass('srcertificatetypegui', 'addCustomSetting'));
	    $this->toolbar->addButtonInstance($button);
        $table = new srCertificateTypeSettingsTableGUI($this, 'showSettings', $this->type);
        $table_custom_settings = new srCertificateTypeCustomSettingsTableGUI($this, 'showSettings', $this->type);
        $spacer = '<div style="height: 30px;"></div>';
        $this->tpl->setContent($table->getHTML() . $spacer . $table_custom_settings->getHTML());
    }


    public function confirmDeleteCustomSetting()
    {
        /** @var srCertificateCustomTypeSetting $setting */
        $setting = srCertificateCustomTypeSetting::findOrFail((int) $_GET['custom_setting_id']);
        $gui = new ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($this->pl->txt('info_delete_custom_setting'));
        $gui->addItem('custom_setting_id', $setting->getId(), $setting->getLabel($this->user->getLanguage()));
        $gui->setConfirm($this->lng->txt('confirm'), 'deleteCustomSetting');
        $gui->setCancel($this->lng->txt('cancel'), 'showSettings');
        $this->tpl->setContent($gui->getHTML());
    }


    public function deleteCustomSetting()
    {
        $setting = srCertificateCustomTypeSetting::findOrFail((int) $_POST['custom_setting_id']);
        $setting->delete();
        ilUtil::sendSuccess($this->pl->txt('msg_success_custom_setting_deleted'), true);
        $this->ctrl->redirect($this, 'showSettings');
    }


    public function confirmDeletePlaceholder()
    {
        /** @var srCertificatePlaceholder $placeholder */
        $placeholder = srCertificatePlaceholder::find((int) $_GET['placeholder_id']);
        $gui = new ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($this->pl->txt('info_delete_custom_placeholder'));
        $gui->addItem('placeholder_id', $placeholder->getId(), $placeholder->getLabel($this->user->getLanguage()));
        $gui->setConfirm($this->lng->txt('confirm'), 'deletePlaceholder');
        $gui->setCancel($this->lng->txt('cancel'), 'showPlaceholders');
        $this->tpl->setContent($gui->getHTML());
    }


    public function deletePlaceholder()
    {
        $placeholder = srCertificatePlaceholder::findOrFail((int) $_POST['placeholder_id']);
        $placeholder->delete();
        ilUtil::sendSuccess($this->pl->txt('msg_success_custom_placeholder_deleted'), true);
        $this->ctrl->redirect($this, 'showPlaceholders');
    }


    /**
     * Show form for editing settings of a type
     */
    public function editSetting()
    {
        try {
            $form = new srCertificateTypeSettingFormGUI($this, $this->type, $_REQUEST['identifier']);
            $this->tpl->setContent($form->getHTML());
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showSettings');
        }
    }


    /**
     * Update settings
     */
    public function updateSetting()
    {
        try {
            $form = new srCertificateTypeSettingFormGUI($this, $this->type, $_REQUEST['identifier']);
            if ($form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('msg_setting_saved'), true);
                $this->ctrl->redirect($this, 'showSettings');
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showSettings');
        }
    }


    /**
     * @return string
     */
    public function addCustomSetting()
    {
        $form = new srCertificateCustomTypeSettingFormGUI($this, new srCertificateCustomTypeSetting());
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * @return string
     */
    public function editCustomSetting()
    {
        $form = new srCertificateCustomTypeSettingFormGUI($this, srCertificateCustomTypeSetting::find((int) $_GET['custom_setting_id']));
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Create/Update a custom setting
     */
    public function saveCustomSetting()
    {
        if (isset($_POST['custom_setting_id']) && $_POST['custom_setting_id']) {
            $setting = srCertificateCustomTypeSetting::find((int) $_POST['custom_setting_id']);
        } else {
            $setting = new srCertificateCustomTypeSetting();
        }

        $form = new srCertificateCustomTypeSettingFormGUI($this, $setting);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_setting_saved'), true);
            $this->ctrl->redirect($this, 'showSettings');
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
        }
    }


    /**
     * Show table with available placeholders for this type
     */
    public function showPlaceholders()
    {
        $table1 = new srCertificateTypeStandardPlaceholdersTableGUI($this, 'showPlaceholders');
        $table2 = new srCertificateTypePlaceholdersTableGUI($this, 'showPlaceholders', $this->type);
        $spacer = '<div style="height: 30px;"></div>';
        $this->tpl->setContent($table1->getHTML() . $spacer . $table2->getHTML());
        ilUtil::sendInfo(sprintf($this->pl->txt('msg_placeholder_format_info'), srCertificatePlaceholder::PLACEHOLDER_START_SYMBOL, srCertificatePlaceholder::PLACEHOLDER_END_SYMBOL));
    }


    /**
     * Add a new placeholder
     */
    public function addPlaceholder()
    {
        $placeholder = new srCertificatePlaceholder();
        $placeholder->setCertificateType($this->type);
        $form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Show form for editing a placeholder
     */
    public function editPlaceholder()
    {
        try {
            $placeholder = srCertificatePlaceholder::find($_REQUEST['placeholder_id']);
            if ($placeholder === null) {
                throw new ilException("Placeholder with ID " . $_REQUEST['placeholder_id'] . " not found");
            }
            $form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
            $this->tpl->setContent($form->getHTML());
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showPlaceholders');
        }
    }


    /**
     * Create a new placeholder
     */
    public function createPlaceholder()
    {
        $placeholder = new srCertificatePlaceholder();
        $placeholder->setCertificateType($this->type);
        $form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_placeholder_saved'), true);
            $this->ctrl->redirect($this, 'showPlaceholders');
        } else {
            $this->tpl->setContent($form->getHTML());
        }
    }


    /**
     * Update placeholder
     */
    public function updatePlaceholder()
    {
        try {
            $placeholder = srCertificatePlaceholder::find($_REQUEST['placeholder_id']);
            if ($placeholder === null) {
                throw new srCertificateException("Placeholder with ID " . $_REQUEST['placeholder_id'] . " not found");
            }
            $form = new srCertificateTypePlaceholderFormGUI($this, $placeholder);
            if ($form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('msg_placeholder_saved'), true);
                $this->ctrl->redirect($this, 'showPlaceholders');
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        } catch (ilException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showPlaceholders');
        }
    }


    /**
     * Show form for editing singatures
     */
    public function showSignatures()
    {
        $table = new srCertificateTypeSignaturesTableGUI($this, 'showSignatures', $this->type);
        $this->tpl->setContent($table->getHTML());
    }


    /**
     * Add a new placeholder
     */
    public function addSignature()
    {
        $signature = new srCertificateSignature();
        $signature->setCertificateType($this->type);
        $form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Create a new signature
     */
    public function createSignature()
    {
        $signature = new srCertificateSignature();
        $signature->setCertificateType($this->type);
        $form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_signature_saved'), true);
            $this->ctrl->redirect($this, 'showSignatures');
        } else {
            $this->tpl->setContent($form->getHTML());
        }
    }


    /**
     *
     */
    public function editSignature()
    {
        try {
            $signature = srCertificateSignature::find((int) $_GET['signature_id']);
            if ($signature === null) {
                throw new ilException("Signature with ID " . (int) $_GET['signature_id'] . " not found");
            }
            $form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
            $this->tpl->setContent($form->getHTML());
        } catch (Exception $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showSignatures');
        }
    }


    /**
     * Update signature related stuff
     */
    public function updateSignature()
    {
        try {
            $signature = srCertificateSignature::find($_GET['signature_id']);
            if ($signature === null) {
                throw new srCertificateException("Signature with ID " . $_GET['signature_id'] . " not found");
            }
            $form = new srCertificateTypeSignatureFormGUI($this, $signature, $this->type);
            if ($form->saveObject()) {
                ilUtil::sendSuccess($this->pl->txt('msg_signature_saved'), true);
                $this->ctrl->redirect($this, 'showSignatures');
            } else {
                $this->tpl->setContent($form->getHTML());
            }
        } catch (ilException $e) {
            ilUtil::sendFailure($e->getMessage(), true);
            $this->ctrl->redirect($this, 'showSignatures');
        }
    }


    /**
     *
     */
    public function confirmDeleteSignature()
    {
        $signature = srCertificateSignature::find($_GET['signature_id']);
        $item_html = $signature->getFirstName() . " " . $signature->getLastName() . '<br>';
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget($this->pl->txt('common_back'), $this->ctrl->getLinkTarget($this, 'view'));
        ilUtil::sendQuestion($this->pl->txt('signatures_confirm_delete'));

        $toolbar = new ilToolbarGUI();
        $this->ctrl->saveParameter($this, 'signature_id');
	    $button = ilLinkButton::getInstance();
	    $button->setCaption($this->pl->txt('confirm'), false);
	    $button->setUrl($this->ctrl->getLinkTarget($this, 'deleteSignature'));
	    $this->toolbar->addButtonInstance($button);
	    $button = ilLinkButton::getInstance();
	    $button->setCaption($this->pl->txt('cancel'), false);
	    $button->setUrl($this->ctrl->getLinkTarget($this, 'showSignatures'));
	    $this->toolbar->addButtonInstance($button);

        $this->tpl->setContent($item_html . '</br>' . $toolbar->getHTML());
    }


    /**
     *
     */
    public function deleteSignature()
    {
        $signature = srCertificateSignature::find($_GET['signature_id']);
        $signature->delete();
        ilUtil::sendSuccess($this->pl->txt('msg_delete_signature_success'), true);
        $this->ctrl->redirect($this, 'showSignatures');
    }


    public function downloadSignature()
    {
        $signature = srCertificateSignature::find($_GET['signature_id']);
        $signature->download();
    }


    /**
     * Create or update a type
     */
    public function saveType()
    {
        $type = ($this->type === NULL) ? new srCertificateType() : $this->type;
        $form = new srCertificateTypeFormGUI($this, $type);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('msg_type_saved'), true);
            $this->ctrl->setParameter($this, 'type_id', $type->getId());
            $this->ctrl->redirect($this, 'editType');
        } else {
            $this->tpl->setContent($form->getHTML());
        }
    }


    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        $allowed_roles = ilCertificateConfig::getX('roles_administrate_certificate_types');

        return $this->rbac->isAssignedToAtLeastOneGivenRole($this->user->getId(), json_decode($allowed_roles, true));
    }

}