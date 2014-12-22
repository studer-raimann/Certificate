<?php

require_once(dirname(dirname(__FILE__)) .'/Setting/class.srCertificateSetting.php');

/**
 * srCertificateDefinitionSetting
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateDefinitionSetting extends srCertificateSetting
{

    /**
     * MySQL Table-Name
     */
    const TABLE_NAME = 'cert_def_setting';


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $definition_id;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;


    public function __construct($id = 0)
    {
        parent::__construct($id);
        $this->pl = ilCertificatePlugin::getInstance();
    }


    // Public

    /**
     * Check in the certificate type if this setting is editable in the current context (crs, tst...)
     *
     * @return bool
     */
    public function isEditable()
    {
        /** @var srCertificateDefinition $definition */
        $definition = srCertificateDefinition::find($this->getDefinitionId());
        $type = $definition->getType();
        $setting = $type->getSettingByIdentifier($this->getIdentifier());
        $ref_id = $definition->getRefId();
        $object_type = ($this->pl->isCourseTemplate($ref_id)) ? 'crs-tpl' : ilObject::_lookupType($ref_id, true);
        return in_array($object_type, $setting->getEditableIn());
    }

    /**
     * Returns the default value defined in the type
     *
     * @return string
     */
    public function getDefaultValue()
    {
        /** @var srCertificateDefinition $definition */
        $definition = srCertificateDefinition::find($this->getDefinitionId());
        $type = $definition->getType();
        $setting = $type->getSettingByIdentifier($this->getIdentifier());
        return $setting->getValue();
    }


    // Getters & Setters


    /**
     * @param int $definition_id
     */
    public function setDefinitionId($definition_id)
    {
        $this->definition_id = $definition_id;
    }

    /**
     * @return int
     */
    public function getDefinitionId()
    {
        return $this->definition_id;
    }


    /**
     * @param string $value
     */
    public function setValue($value)
    {
        // This should be factored out, currently there is one exception where a value needs to be parsed before storing in DB
        if ($value && $this->getIdentifier() == srCertificateTypeSetting::IDENTIFIER_VALIDITY) {
            /** @var srCertificateDefinition $definition */
            $definition = srCertificateDefinition::find($this->getDefinitionId());
            $validity_type = $definition->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getValue();
            $value = srCertificateTypeSetting::formatValidityBasedOnType($validity_type, $value);
        }

        $this->value = $value;
    }

}
