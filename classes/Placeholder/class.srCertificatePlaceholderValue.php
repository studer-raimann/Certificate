<?php

/**
 * srCertificatePlaceholderValue
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificatePlaceholderValue extends ActiveRecord
{

    /**
     * MySQL Table-Name
     */
    const TABLE_NAME = 'cert_placeholder_value';


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
    protected $placeholder_id;


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $definition_id;


    /**
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $value = array();

    /**
     * @var srCertificatePlaceholder
     */
    protected $placeholder;

    /**
     * @var srCertificateDefinition
     */
    protected $definition;

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


    public function afterObjectLoad()
    {
        $this->setPlaceholder(srCertificatePlaceholder::find($this->getPlaceholderId()));
        $this->setDefinition(srCertificateDefinition::find($this->getDefinitionId()));
    }

    public function create()
    {
        parent::create();
        $this->setDefinition(srCertificateDefinition::find($this->getDefinitionId()));
    }


    /**
     * Check in the value of the placeholder is editable in the current context (crs, crs-tpl, tst...)
     *
     * @return bool
     */
    public function isEditable()
    {
        $ref_id = $this->definition->getRefId();
        $object_type = ($this->pl->isCourseTemplate($ref_id)) ? 'crs-tpl' : ilObject::_lookupType($ref_id, true);
        return in_array($object_type, $this->getPlaceholder()->getEditableIn());
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
            case 'value':
                $value = json_decode($value, true);
                break;
        }
        return $value;
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
            case 'value':
                $value = json_encode($value);
                break;
        }
        return $value;
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


    // Getters & Setters


    /**
     * @param \srCertificatePlaceholder $placeholder_object
     */
    public function setPlaceholder($placeholder_object)
    {
        $this->placeholder = $placeholder_object;
        $this->placeholder_id = $placeholder_object->getId();
    }

    /**
     * @return \srCertificatePlaceholder
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }


    /**
     * Get value of a language or all languages if the $lang_id parameter is omitted
     *
     * @param string $lang_id
     * @return string|array
     */
    public function getValue($lang_id = '')
    {
        // TODO Do some validation
        if ($lang_id) {
            $value = (isset($this->value[$lang_id])) ? $this->value[$lang_id] : "";
            return $value;
        }
        return $this->value;
    }

    /**
     * Set a value for a given language
     *
     * @param $value
     * @param $lang_id
     */
    public function setValue($value, $lang_id = '')
    {
        // TODO Do some validation
        if ($lang_id) {
            $this->value[$lang_id] = $value;
        } else {
            $this->value = $value;
        }
    }


    /**
     * @param int $placeholder_id
     */
    public function setPlaceholderId($placeholder_id)
    {
        $this->placeholder_id = $placeholder_id;
    }

    /**
     * @return int
     */
    public function getPlaceholderId()
    {
        return $this->placeholder_id;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \srCertificateDefinition $definition
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
        $this->definition_id = $definition->getId();
    }

    /**
     * @return \srCertificateDefinition
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param int $definition_id
     */
    public function setDefinitionId($definition_id)
    {
        $this->definition_id = $definition_id;
        $this->definition = srCertificateDefinition::find($definition_id);
    }

    /**
     * @return int
     */
    public function getDefinitionId()
    {
        return $this->definition_id;
    }


}

?>
