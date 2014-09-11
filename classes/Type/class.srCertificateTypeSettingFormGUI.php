<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Form-Class srCertificateTypeSettingGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateTypeSettingFormGUI extends ilPropertyFormGUI
{

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
     * @var
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
     * @var string
     */
    protected $identifier;

    /**
     * @var srCertificateTypeSetting
     */
    protected $setting;

    /**
     * @param $parent_gui
     * @param srCertificateType $type
     * @param string $identifier
     */
    function __construct($parent_gui, srCertificateType $type, $identifier)
    {
        global $tpl, $ilCtrl, $lng, $rbacreview;
        $this->parent_gui = $parent_gui;
        $this->type = $type;
        $this->identifier = $identifier;
        $this->checkIdentifier();
        $this->setting = $this->type->getSettingByIdentifier($this->identifier);
        $this->tpl = $tpl;
        $this->ctrl = $ilCtrl;
        $this->rbac = $rbacreview;
        $this->pl = new ilCertificatePlugin();
        $this->lng = $lng;
        $this->lng->loadLanguageModule('meta');
        $this->initForm();
    }

    /**
     * Abort building form if there is no valid identifier given
     */
    protected function checkIdentifier()
    {
        if (!in_array($this->identifier, array_keys(srCertificateType::getDefaultSettings()))) {
            throw new ilException("Unrecognized identifier '{$this->identifier}'");
        }
    }

    /**
     * @return bool
     */
    public function saveObject()
    {
        if (!$this->fillObject()) {
            return false;
        }
        $this->setting->update();
        return true;
    }


    /**
     * @return bool
     */
    protected function fillObject()
    {
        $this->setValuesByPost();
        if (!$this->checkInput()) {
            return false;
        }
        $value = $this->getInput('default_value');
        // If the validity type is changed, the default value of the validity should be cleared
        if ($this->identifier == srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE) {
            if ($this->setting->getDefaultValue() != $value) {
                // Validity type did change. Reset validity value and produce info message
                $validity = $this->type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY);
                $validity->setDefaultValue('');
                $validity->update();
                ilUtil::sendInfo($this->pl->txt('msg_reset_validity'), true);
            }
        }

        // TODO Refactor, should not belong here
        if ($this->identifier == srCertificateTypeSetting::IDENTIFIER_VALIDITY) {
            $validity_type = $this->type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getDefaultValue();
            switch ($validity_type) {
                case srCertificateTypeSetting::VALIDITY_TYPE_ALWAYS:
                    $value = "";
                    break;
                case srCertificateTypeSetting::VALIDITY_TYPE_DATE:
                    $value = ($value['date']) ? date('Y-m-d', strtotime($value['date'])) : "";
                    break;
                case srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE:
                    $value = json_encode(array('d' => $value['dd'], 'm' => $value['MM']));
                    break;
            }
        }
        $this->setting->setDefaultValue($value);
        $this->setting->setEditableIn($this->getInput('editable_in'));
        return true;
    }


    /**
     * Init form
     */
    protected function initForm()
    {
        $this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
        $title = sprintf($this->pl->txt('edit_setting'), $this->pl->txt("setting_id_{$this->identifier}"));
        $this->setTitle($title);

        $item = new ilHiddenInputGUI('identifier');
        $item->setValue($this->identifier);
        $this->addItem($item);

        $item = $this->getInputByIdentifier();
        if ($item !== null) {
            $this->addItem($item);
        }

        $item = new ilMultiSelectInputGUI($this->pl->txt('editable_in'), 'editable_in');
        $options = array();
        foreach (srCertificateType::getAllAvailableObjectTypes() as $type) {
            $options[$type] = $type;
        }
        $item->setOptions($options);
        $item->setValue($this->type->getSettingByIdentifier($this->identifier)->getEditableIn());
        $this->addItem($item);

        $this->addCommandButton('updateSetting', $this->lng->txt('save'));
    }


    /**
     * Get input GUI depending on identifier
     *
     * @return ilFormPropertyGUI|null
     */
    protected function getInputByIdentifier()
    {
        $name = 'default_value';
        $title = $this->pl->txt('default_value');
        switch ($this->identifier) {
            case srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG:
                $input = new ilSelectInputGUI($title, $name);
                $options = array();
                foreach ($this->type->getLanguages() as $lang) {
                    $options[$lang] = $this->lng->txt("meta_l_{$lang}");
                }
                $input->setOptions($options);
                $input->setValue($this->setting->getDefaultValue());
                break;
            case srCertificateTypeSetting::IDENTIFIER_GENERATION:
                $input = new ilRadioGroupInputGUI($title, $name);
                $option = new ilRadioOption($this->pl->txt('setting_generation_auto'), srCertificateTypeSetting::GENERATION_AUTO);
                $input->addOption($option);
                $option = new ilRadioOption($this->pl->txt('setting_generation_manually'), srCertificateTypeSetting::GENERATION_MANUAL);
                $input->addOption($option);
                $input->setValue($this->setting->getDefaultValue());
                break;
            case srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE:
                $input = new ilRadioGroupInputGUI($title, $name);
                $option = new ilRadioOption($this->pl->txt('always'), srCertificateTypeSetting::VALIDITY_TYPE_ALWAYS);
                $input->addOption($option);
                $option = new ilRadioOption($this->pl->txt('setting_validity_range'), srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE);
                $input->addOption($option);
                $option = new ilRadioOption($this->pl->txt('setting_validity_date'), srCertificateTypeSetting::VALIDITY_TYPE_DATE);
                $input->addOption($option);
                $input->setValue($this->setting->getDefaultValue());
                break;
            case srCertificateTypeSetting::IDENTIFIER_VALIDITY:
                $validity_value = $this->setting->getDefaultValue();
                switch ($this->type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getDefaultValue()) {
                    case srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE:
                        $input = new ilDurationInputGUI($title, $name);
                        $input->setShowMinutes(false);
                        $input->setShowHours(false);
                        $input->setShowDays(true);
                        $input->setShowMonths(true);
                        if ($validity_value) {
                            $range_array = json_decode($validity_value, true);
                            $data = array();
                            $data[$input->getPostVar()]['MM'] = $range_array['m'];
                            $data[$input->getPostVar()]['dd'] = $range_array['d'];
                            $input->setValueByArray($data);
                        }
                        break;
                    case srCertificateTypeSetting::VALIDITY_TYPE_DATE:
                        $input = new ilDateTimeInputGUI($title, $name);
                        $input->setMode(ilDateTimeInputGUI::MODE_INPUT);
                        if ($validity_value) {
                            $input->setDate(new ilDateTime($validity_value, IL_CAL_DATE));
                        }
                        break;
                    case srCertificateTypeSetting::VALIDITY_TYPE_ALWAYS:
                        // Makes no sence to configure this further
                        $input = null;
                        break;
                    default:
                        $input = new ilTextInputGUI($title, $name);
                }
                break;
            case srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE:
            case srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER:
                $input = new ilCheckboxInputGUI($title, $name);
                if ($this->setting->getDefaultValue()) {
                    $input->setChecked(true);
                }
                break;
            default:
                $input = new ilTextInputGUI($title, $name);
                $input->setValue($this->setting->getDefaultValue());
        }
        return $input;
    }
}

?>