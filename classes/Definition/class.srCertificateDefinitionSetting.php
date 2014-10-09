<?php

/**
 * srCertificateDefinitionSetting
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateDefinitionSetting extends ActiveRecord
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
    protected $definition_id;


    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       256
     */
    protected $identifier;


    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       1204
     */
    protected $value;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;


    public function __construct($id = 0)
    {
        parent::__construct($id);
        $this->pl = new ilCertificatePlugin();
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
        return $setting->getDefaultValue();
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
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}

?>
