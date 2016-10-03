<?php
require_once (dirname(dirname(__FILE__))) . '/Type/class.srCertificateType.php';
require_once (dirname(__FILE__)) . '/class.srCertificateDefinitionSetting.php';
require_once (dirname(dirname(__FILE__))) . '/Placeholder/class.srCertificatePlaceholderValue.php';
require_once (dirname(dirname(__FILE__))) . '/CustomSetting/class.srCertificateCustomDefinitionSetting.php';

/**
 * srCertificateDefinition
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateDefinition extends ActiveRecord
{

    const TABLE_NAME = 'cert_definition';

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected $id = 0;


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $type_id;

    /**
     * @var int Ref-ID to ILIAS object where this definition belongs to
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $ref_id = 0;

    /**
     * @var int ID of srCertificateSignature
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $signature_id = 0;


    /**
     * @var srCertificateType
     */
    protected $type;

    /**
     * @var array srCertificateDefinitionSetting[]
     */
    protected $settings;

    /**
     * @var array
     */
    protected $custom_settings;

    /**
     * @var array srCertificatePlaceholderValue[]
     */
    protected $placeholder_values;

    /**
     * Set to true if type changed
     *
     * @var boolean
     */
    protected $type_changed = false;


    public function __construct($id = 0)
    {
        parent::__construct($id);
    }


    public function create()
    {
        parent::create();
        $this->type = srCertificateType::find($this->getTypeId());
        $this->createSettings();
        $this->createPlaceholderValues();
    }


    /**
     * Also update placeholder values and settings.
     * If the certificate type did change, delete old settings/placeholder values and create new default ones from new type.
     *
     */
    public function update()
    {
        /** @var $setting srCertificateDefinitionSetting */
        /** @var $pl srCertificatePlaceholderValue */
        if ($this->type_changed) {
            $this->signature_id = 0; // Reset signature
        }
        parent::update();
        // If the type did change, we destroy all settings + placeholder values from the old type and create new ones
        if ($this->type_changed) {
            foreach ($this->getSettings() as $setting) {
                $setting->delete();
            }
            foreach ($this->getCustomSettings() as $custom_setting) {
                $custom_setting->delete();
            }
            foreach ($this->getPlaceholderValues() as $pl) {
                $pl->delete();
            }
            $this->createSettings();
            $this->createPlaceholderValues();
        } else {
            foreach ($this->getSettings() as $setting) {
                $setting->update();
            }
            foreach ($this->getCustomSettings() as $setting) {
                $setting->update();
            }
            foreach ($this->getPlaceholderValues() as $pl) {
                $pl->update();
            }
        }
    }


    /**
     * Clone/copy this definition for a new course
     *
     * @param int $ref_id ref-ID of new course
     * @return srCertificateDefinition
     */
    public function copy($ref_id)
    {
        $new_definition = new srCertificateDefinition();
        $new_definition->setRefId($ref_id);
        $new_definition->setTypeId($this->getTypeId());
        $new_definition->create();

        // Settings and placeholder values now contain default values inherited from type.
        // We overwrite them with the values from this definition

        /** @var $setting srCertificateDefinitionSetting */
        foreach ($this->getSettings() as $setting) {
            $s = $new_definition->getSettingByIdentifier($setting->getIdentifier());
            $s->setValue($setting->getValue());
            $s->update();
        }

        /** @var $ph_value srCertificatePlaceholderValue */
        foreach ($this->getPlaceholderValues() as $ph_value) {
            $ph = $new_definition->getPlaceholderValueByPlaceholderId($ph_value->getPlaceholderId());
            $ph->setValue($ph_value->getValue()); // This does set the values for each language
            $ph->update();
        }

        return $new_definition;
    }


    /**
     * Get a setting by identifier
     *
     * @param $identifier
     * @return null|srCertificateDefinitionSetting
     */
    public function getSettingByIdentifier($identifier)
    {
        /** @var $setting srCertificateDefinitionSetting */
        foreach ($this->getSettings() as $setting) {
            if ($setting->getIdentifier() == $identifier) {
                return $setting;
                break;
            }
        }

        return null;
    }


    /**
     * Get a placeholder value object by ID
     *
     * @param $id
     * @return null|srCertificatePlaceholderValue
     */
    public function getPlaceholderValueByPlaceholderId($id)
    {
        /** @var $ph_value srCertificatePlaceholderValue */
        foreach ($this->getPlaceholderValues() as $ph_value) {
            if ($ph_value->getPlaceholderId() == $id) {
                return $ph_value;
                break;
            }
        }

        return null;
    }


    /**
     * @return srCertificateSignature|null
     */
    public function getSignature()
    {
        return srCertificateSignature::find($this->signature_id);
    }


    /**
     * @return int
     */
    public function getSignatureId()
    {
        return $this->signature_id;
    }


    /**
     * @param $id
     */
    public function setSignatureId($id)
    {
        $this->signature_id = $id;
    }


    // Shortcut-Getters implemented for the settings

    public function getValidityType()
    {
        return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getValue();
    }


    public function getValidity()
    {
        return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY)->getValue();
    }


    public function getNotification()
    {
        return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION)->getValue();
    }


    public function getDefaultLanguage()
    {
        return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG)->getValue();
    }


    public function getGeneration()
    {
        return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_GENERATION)->getValue();
    }


    public function getDownloadable()
    {
        $setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE);

        return (is_null($setting)) ? null : $setting->getValue();
    }


    public function getNotificationUser()
    {
        $setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);

        return (is_null($setting)) ? null : $setting->getValue();
    }


    public function getScormTiming()
    {
        $setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING);

        return (is_null($setting)) ? null : $setting->getValue();
    }


    // Static

    /**
     * @return string
     * @description Return the Name of your Database Table
     */
    static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }


    // Protected

    /**
     * Create the settings inheriting default values defined in the type
     *
     */
    protected function createSettings()
    {
        $type_settings = $this->type->getSettings();
        /** @var srCertificateTypeSetting $type_setting */
        foreach ($type_settings as $type_setting) {
            $setting = new srCertificateDefinitionSetting();
            $setting->setIdentifier($type_setting->getIdentifier());
            $setting->setDefinitionId($this->getId());
            $setting->setValue($type_setting->getValue());
            $setting->create();
            $this->settings[] = $setting;
        }
        foreach ($this->type->getCustomSettings() as $custom_setting) {
            $setting = new srCertificateCustomDefinitionSetting();
            $setting->setDefinitionId($this->getId());
            $setting->setValue($custom_setting->getValue());
            $setting->setIdentifier($custom_setting->getIdentifier());
            $setting->save();
            $this->custom_settings[] = $setting;
        }
    }


    /**
     * Create the values for the placeholders defined in the type
     * Each placeholder value inherits the default value defined in the type, per language
     *
     */
    protected function createPlaceholderValues()
    {
        $placeholders = $this->type->getPlaceholders();
        /** @var $placeholder srCertificatePlaceholder */
        foreach ($placeholders as $placeholder) {
            $placeholder_value = new srCertificatePlaceholderValue();
            $placeholder_value->setPlaceholderId($placeholder->getId());
            $placeholder_value->setDefinitionId($this->getId());
            $placeholder_value->setValue($placeholder->getDefaultValues());
            $placeholder_value->create();
            $this->placeholder_values[] = $placeholder_value;
        }
    }

    // Getter & Setter

    /**
     * @param int $ref_id
     */
    public function setRefId($ref_id)
    {
        $this->ref_id = $ref_id;
    }


    /**
     * @return int
     */
    public function getRefId()
    {
        return $this->ref_id;
    }


    /**
     * @param int $type_id
     */
    public function setTypeId($type_id)
    {
        if ($type_id != $this->getTypeId())
            $this->type_changed = true;
        $this->type_id = $type_id;
        $this->type = srCertificateType::find($type_id);
    }


    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->type_id;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }


    /**
     * @return array
     */
    public function getSettings()
    {
        if (is_null($this->settings)) {
            $this->settings = srCertificateDefinitionSetting::where(array('definition_id' => $this->getId()))->get();
        }

        return $this->settings;
    }


    /**
     * @return array srCertificateCustomDefinitionSetting[]
     */
    public function getCustomSettings()
    {
        if (is_null($this->custom_settings)) {
            $this->custom_settings = srCertificateCustomDefinitionSetting::where(array('definition_id' => $this->getId()))->get();
        }

        return $this->custom_settings;
    }


    /**
     * @return \srCertificateType
     */
    public function getType()
    {
        return srCertificateType::find($this->getTypeId());
    }


    /**
     * @param array $placeholder_values
     */
    public function setPlaceholderValues($placeholder_values)
    {
        $this->placeholder_values = $placeholder_values;
    }


    /**
     * @return array
     */
    public function getPlaceholderValues()
    {
        if (is_null($this->placeholder_values)) {
            $this->placeholder_values = srCertificatePlaceholderValue::where(array('definition_id' => $this->getId()))->orderBy('placeholder_id')->get();
        }

        return $this->placeholder_values;
    }


    /**
     * @param boolean $type_changed
     */
    public function setTypeChanged($type_changed)
    {
        $this->type_changed = $type_changed;
    }


    /**
     * @return boolean
     */
    public function getTypeChanged()
    {
        return $this->type_changed;
    }

}