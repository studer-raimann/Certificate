<?php

/**
 * srCertificateCustomTypeSetting
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateCustomTypeSetting extends srCertificateTypeSetting
{

    /**
     * MySQL Table-Name
     */
    const TABLE_NAME = 'cert_type_setting_cus';

    const SETTING_TYPE_BOOLEAN = 1;
    const SETTING_TYPE_SELECT = 2;

    /**
     * Valid characters for identifier
     */
    const REGEX_VALID_IDENTIFIER = '#^[A-Za-z0-9_\-]+$#';

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $setting_type_id = self::SETTING_TYPE_BOOLEAN;

    /**
     * Labels for each defined language
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       1204
     */
    protected $labels = array();

    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       2048
     */
    protected $data;

    /**
     * @param srCertificateTypeSetting $old_setting
     */
    public function cloneSetting(srCertificateTypeSetting $old_setting) {
        parent::cloneSetting($old_setting);
        $this->setLabel($old_setting->getLabel());
        $this->setData($old_setting->getData());
    }

    /**
     * Set values after reading from DB, e.g. convert from JSON to Array
     *
     * @param $key
     * @param $value
     * @return mixed|null
     */
    public function wakeUp($key, $value)
    {
        switch ($key) {
            case 'labels':
                return json_decode($value, true);
        }

        return parent::wakeUp($key, $value);
    }


    /**
     * Set values before saving to DB
     *
     * @param $key
     * @return int|mixed|string
     */
    public function sleep($key)
    {
        $value = $this->{$key};
        switch ($key) {
            case 'labels':
                return json_encode($value);
                break;
        }

        return parent::sleep($key);
    }


    public function delete()
    {
        // Delete setting on all definitions
        foreach (srCertificateDefinition::where(array('type_id' => $this->getTypeId()))->get() as $definition) {
            /** @var srCertificateDefinition $setting */
            $setting = srCertificateCustomDefinitionSetting::where(array('definition_id' => $definition->getId(), 'identifier' => $this->getIdentifier()))->first();
            if ($setting) {
                $setting->delete();
            }
        }

        parent::delete();
    }


    /**
     * Set a label for a language
     *
     * @param string $label
     * @param string $lang e.g. de,en...
     */
    public function setLabel($label, $lang)
    {
        $this->labels[$lang] = $label;
    }


    /**
     * Get label of a language
     *
     * @param string $lang e.g. de,en...
     * @return string
     */
    public function getLabel($lang)
    {
        if (isset($this->labels[$lang])) {
            return $this->labels[$lang];
        }

        foreach ($this->labels as $label) {
            return $label;
        }

        return '';
    }


    /**
     * @return int
     */
    public function getSettingTypeId()
    {
        return $this->setting_type_id;
    }


    /**
     * @param int $setting_type_id
     */
    public function setSettingTypeId($setting_type_id)
    {
        $this->setting_type_id = $setting_type_id;
    }


    /**
     * @param bool $as_array True if data is parsed and returned as array
     * @return string
     */
    public function getData($as_array = false)
    {
        if ($as_array) {
            $data = array();
            $lines = explode("\n", $this->data);
            foreach ($lines as $line) {
                if (strpos($line, '||') !== false) {
                    $key_value = explode('||', $line);
                    $data[$key_value[0]] = $key_value[1];
                } else {
                    $data[$line] = $line;
                }
            }

            return $data;
        }

        return $this->data;
    }


    /**
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }


    /**
     * @param string $identifier
     * @throws srCertificateException
     */
    public function setIdentifier($identifier)
    {
        if (!preg_match(self::REGEX_VALID_IDENTIFIER, $identifier)) {
            throw new srCertificateException("Identifier '{$identifier}' not valid");
        }
        $this->identifier = $identifier;
    }

}
