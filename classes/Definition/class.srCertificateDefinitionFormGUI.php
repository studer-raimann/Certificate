<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Form/classes/class.ilDurationInputGUI.php');
require_once('class.srCertificateDefinition.php');

/**
 * Form-Class srCertificateDefinitionFormGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateDefinitionFormGUI extends ilPropertyFormGUI
{

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
     * @var
     */
    protected $lng;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var bool
     */
    protected $isNew = false;

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
     * @param $parent_gui
     * @param srCertificateDefinition $definition
     */
    function __construct($parent_gui, $definition)
    {
        global $tpl, $ilCtrl, $lng, $rbacreview, $ilUser;
        $this->parent_gui = $parent_gui;
        $this->definition = $definition;
        $this->tpl = $tpl;
        $this->ctrl = $ilCtrl;
        $this->pl = ilCertificatePlugin::getInstance();
        $this->lng = $lng;
        $this->isNew = ($this->definition->getId()) ? false : true;
        $this->rbac = $rbacreview;
        $this->user = $ilUser;
        $this->initForm();
    }


    /**
     * @return bool
     */
    public function saveObject()
    {
        if ( ! $this->fillObject()) {
            return false;
        }
        $this->definition->save();
        return true;
    }


    /**
     * @return bool
     */
    protected function fillObject()
    {
        $this->setValuesByPost();
        if (!$this->checkInput())
            return false;
        if (isset($_POST['type_id'])) {
            $this->definition->setTypeId($this->getInput('type_id'));
        }
        $this->definition->setRefId((int)$_GET['ref_id']);
        if ($this->isNew) {
            return true;
        } else {
            // Set new settings values
            /** @var $setting srCertificateDefinitionSetting */
            foreach ($this->definition->getSettings() as $setting) {
                if ( ! $setting->isEditable()) continue; // Don't set values if setting can't change its value
                $value = $this->getInput($setting->getIdentifier());
                if ($setting->getIdentifier() == srCertificateTypeSetting::IDENTIFIER_VALIDITY) {
                    $validity_type = $this->getInput(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE);
                    $value = $this->getInput(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE . '_' . $validity_type);
                }
                $setting->setValue($value);
            }

            foreach ($this->definition->getCustomSettings() as $setting) {
                if ( ! $setting->isEditable()) continue;
                $value = $this->getInput('custom_setting_' . $setting->getIdentifier());
                $setting->setValue($value);
            }

        }

        return true;
    }


    /**
     * Init form
     */
    protected function initForm()
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $title = ($this->isNew) ? $this->pl->txt('choose_cert_type') : $this->pl->txt('edit_settings');

        // Certificate Type
        $type = $this->getTypeInput();
        $n_types = count($type->getOptions());
        if ($this->isNew) {
            if (!$n_types) {
                ilUtil::sendInfo($this->pl->txt('msg_no_types_available'));
            } else {
                $this->setTitle($title);
                $this->addItem($type);
                $this->addCommandButton('createDefinition', $this->pl->txt('save'));
            }
            return;
        } else {
            if ($n_types > 1) {
                $this->setTitle($title);
                $checkbox = new ilCheckboxInputGUI($this->pl->txt('change_cert_type'), 'change_type');
                $checkbox->addSubItem($type);
                $this->addItem($checkbox);
            }
        }

        // Add all settings inputs
        $settings_inputs = $this->getSettingsInputs();
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->pl->txt('certificate'));
        $this->addItem($header);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG]);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE]);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_GENERATION]);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE]);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING]);

        // Custom settings
        /** @var srCertificateCustomDefinitionSetting $setting */
        foreach ($this->definition->getCustomSettings() as $setting) {
            switch ($setting->getSettingTypeId()) {
                case srCertificateCustomTypeSetting::SETTING_TYPE_BOOLEAN:
                    $item = new ilCheckboxInputGUI($setting->getLabel($this->user->getLanguage()), 'custom_setting_' . $setting->getIdentifier());
                    if ($setting->getValue()) {
                        $item->setChecked(true);
                    }
                    break;
                case srCertificateCustomTypeSetting::SETTING_TYPE_SELECT:
                    $item = new ilSelectInputGUI($setting->getLabel($this->user->getLanguage()), 'custom_setting_' . $setting->getIdentifier());
                    $item->setValue($setting->getValue());
                    $item->setOptions($setting->getCustomTypeSetting()->getData(true));
                    break;
            }
            $this->addItem($item);
        }

        // Notification
        $header = new ilFormSectionHeaderGUI();
        $header->setTitle($this->pl->txt('setting_id_notification'));
        $this->addItem($header);
        $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_NOTIFICATION]);
        if (isset($settings_inputs[srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER])) {
            $this->addItem($settings_inputs[srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER]);
        }

        $this->addCommandButton("updateDefinition", "Save");
    }

    /**
     * Get all settings with the correct input field
     *
     * @return array
     */
    protected function getSettingsInputs()
    {
        $settings = array();
        /** @var $setting srCertificateDefinitionSetting */
        foreach ($this->definition->getSettings() as $setting) {
            $identifier = $setting->getIdentifier();
            $value = $setting->getValue();
            if ($identifier == srCertificateTypeSetting::IDENTIFIER_VALIDITY)
                continue; // Validity itself is set depending on the validity type and displayed as subitem

            switch ($identifier) {
                case srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG:
                    // Select contains all available languages defined in the type
                    $input = $this->getDefaultLangInput();
                    $input->setValue($value);
                    break;
                case srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE:
                    $input = $this->getValidityInput($setting);
                    $input->setValue($value);
                    break;
                case srCertificateTypeSetting::IDENTIFIER_GENERATION:
                    $input = new ilRadioGroupInputGUI($this->pl->txt('setting_id_generation'), $identifier);
                    $input->setRequired(true);
                    $option = new ilRadioOption($this->pl->txt('setting_generation_auto'), srCertificateTypeSetting::GENERATION_AUTO);
                    $input->addOption($option);
                    $option = new ilRadioOption($this->pl->txt('setting_generation_manually'), srCertificateTypeSetting::GENERATION_MANUAL);
                    $input->addOption($option);
                    $input->setValue($value);
                    break;
                case srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE:
                case srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER:
                case srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING:
                    $input = new ilCheckboxInputGUI($this->pl->txt("setting_id_{$identifier}"), $identifier);
                    $input->setInfo($this->pl->txt("setting_id_{$identifier}_info"));
                    $input->setValue(1);
                    if ($setting->getValue()) $input->setChecked(true);
                    break;
                default:
                    $input = new ilTextInputGUI($this->pl->txt("setting_id_{$identifier}"), $identifier);
                    $input->setInfo($this->pl->txt("setting_id_{$identifier}_info"));
                    $input->setValue($value);
                    break;
            }
            $input->setDisabled(!$setting->isEditable());
            $settings[$identifier] = $input;
        }
        return $settings;
    }


    /**
     * Build ValidityInput
     *
     * @param srCertificateDefinitionSetting $setting
     * @return ilRadioGroupInputGUI
     */
    protected function getValidityInput(srCertificateDefinitionSetting $setting)
    {
        $validity_value = $this->definition->getValidity();

        // Always
        $input = new ilRadioGroupInputGUI($this->pl->txt('setting_id_validity'), $setting->getIdentifier());
        $input->setRequired(true);
        $option = new ilRadioOption($this->pl->txt('always'), srCertificateTypeSetting::VALIDITY_TYPE_ALWAYS);
        $input->addOption($option);

        // Date range
        $option = new ilRadioOption($this->pl->txt('setting_validity_range'), srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE);
        $subitem = new ilDurationInputGUI($this->pl->txt('date_range'), $setting->getIdentifier() . '_' . srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE);
        $subitem->setShowMinutes(false);
        $subitem->setShowHours(false);
        $subitem->setShowDays(true);
        $subitem->setShowMonths(true);
        if ($setting->getValue() == srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE && $validity_value) {
            $range_array = json_decode($validity_value, true);
            $data = array();
            $data[$subitem->getPostVar()]['MM'] = $range_array['m'];
            $data[$subitem->getPostVar()]['dd'] = $range_array['d'];
            $subitem->setValueByArray($data);
        }
        $option->addSubItem($subitem);
        $input->addOption($option);

        // Date
        $option = new ilRadioOption($this->pl->txt('setting_validity_date'), srCertificateTypeSetting::VALIDITY_TYPE_DATE);
        $subitem = new ilDateTimeInputGUI($this->pl->txt('date'), $setting->getIdentifier() . '_' . srCertificateTypeSetting::VALIDITY_TYPE_DATE);
        $subitem->setMode(ilDateTimeInputGUI::MODE_INPUT);
        if ($setting->getValue() == srCertificateTypeSetting::VALIDITY_TYPE_DATE && $validity_value)
            $subitem->setDate(new ilDateTime($validity_value, IL_CAL_DATE));
        $option->addSubItem($subitem);
        $input->addOption($option);

        $subitem->setDisabled(!$setting->isEditable()); // SW This seems to have no effect...
        return $input;
    }

    /**
     * Get dropdown for languages
     *
     * @return ilSelectInputGUI
     */
    protected function getDefaultLangInput()
    {
        $input = new ilSelectInputGUI($this->pl->txt('setting_id_default_lang'), srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG);
        $languages = array();
        foreach ($this->definition->getType()->getLanguages() as $lang) {
            $languages[$lang] = $lang;
        }
        $input->setOptions($languages);
        $input->setRequired(true);
        $input->setInfo($this->pl->txt('setting_id_default_lang_info'));
        return $input;
    }


    /**
     * Get dropdown for choosing the certificate type
     *
     * @return ilSelectInputGUI
     */
    protected function getTypeInput()
    {
        $types = srcertificateType::get();
        $options = array();
        $object_type = ($this->pl->isCourseTemplate((int)$_GET['ref_id'])) ? 'crs-tpl' : ilObject::_lookupType((int)$_GET['ref_id'], true);
        /** @var $type srCertificateType */
        foreach ($types as $type) {
            if (!srCertificateType::isSelectable($type, (int)$_GET['ref_id'])) {
                continue;
            }
            $options[$type->getId()] = $type->getTitle();
        }
        $item = new ilSelectInputGUI($this->pl->txt('setting_id_type'), 'type_id');
        $item->setOptions($options);
        $info = ($this->isNew) ? $this->pl->txt('setting_id_type_info_new') : $this->pl->txt('setting_id_type_info_change');
        $item->setInfo($info);
        $item->setValue($this->definition->getTypeId());
        $item->setRequired(true);
        return $item;
    }
}