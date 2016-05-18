<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/exceptions/class.srCertificateException.php');
require_once('class.srCertificateStandardPlaceholders.php');

/**
 * srCertificatePlaceholder
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificatePlaceholder extends ActiveRecord
{

    /**
     * MySQL Table-Name
     */
    const TABLE_NAME = 'cert_placeholder';

    /**
     * Symbols wrapped around placeholder identifier for parsing
     */
    const PLACEHOLDER_START_SYMBOL = '[[';
    const PLACEHOLDER_END_SYMBOL = ']]';

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
     * @db_is_primary   true
     * @db_sequence     true
     */
    protected $id = 0;

    /**
     * @var int ID of srCertificateType where this placeholder belongs to
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $type_id;

    /**
     * @var srCertificateType Object where this placeholder belongs to
     */
    protected $type;

    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       256
     */
    protected $identifier = '';


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       1
     */
    protected $is_mandatory = 0;


    /**
     * Max characters of value
     *
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $max_characters_value = 256;

    /**
     * Default values for each defined language
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $default_values = array();


    /**
     * Labels for each defined language
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $labels = array();

    /**
     * Object types where placeholder is editable, e.g. crs,tst...
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       512
     */
    protected $editable_in = array();

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
     * Set default value for a language
     *
     * @param string $value
     * @param string $lang e.g. de,en...
     */
    public function setDefaultValue($value, $lang)
    {
        $this->default_values[$lang] = $value;
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
     * Get default value for a language
     *
     * @param string $lang e.g. de,en...
     * @return string
     */
    public function getDefaultValue($lang)
    {
        return (isset($this->default_values[$lang])) ? $this->default_values[$lang] : '';
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
            case 'default_values':
            case 'editable_in':
            case 'labels':
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
            case 'default_values':
            case 'labels':
            case 'editable_in':
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


    public function delete()
    {
        // By deleting a placeholder, all placeholder values of existing definitions are deleted as well!
        foreach (srCertificatePlaceholderValue::where(array('placeholder_id' => $this->getId()))->get() as $value) {
            /** @var $value srCertificatePlaceholderValue */
            $value->delete();
        }
        parent::delete();
    }


    /**
     * Format an array of identifiers, e.g. add "[[" "]]" symbols for parsing
     *
     * @param array $identifiers
     * @return array
     */
    public static function getFormattedIdentifiers(array $identifiers = array())
    {
        $identifier_formatted = array();
        foreach ($identifiers as $identifier) {
            $identifier_formatted[] = self::PLACEHOLDER_START_SYMBOL . $identifier . self::PLACEHOLDER_END_SYMBOL;
        }

        return $identifier_formatted;
    }


    /**
     * Given an array of placeholders, format the key, e.g. add "[[" "]]" symbols for parsing
     *
     * @param array $placeholders
     * @return array
     */
    public static function getFormattedPlaceholders(array $placeholders = array())
    {
        $ph_formatted = array();
        foreach ($placeholders as $k => $v) {
            $ph_formatted[self::PLACEHOLDER_START_SYMBOL . $k . self::PLACEHOLDER_END_SYMBOL] = $v;
        }

        return $ph_formatted;
    }


    // Protected


    // Getters & Setters


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $identifier
     * @throws srCertificateException
     */
    public function setIdentifier($identifier)
    {
        if (!preg_match(self::REGEX_VALID_IDENTIFIER, $identifier)) {
            throw new srCertificateException(sprintf($this->pl->txt('msg_identifier_not_valid'), $identifier));
        }
        if (srCertificateStandardPlaceholders::isReservedIdentifier($identifier)) {
            throw new srCertificateException(sprintf($this->pl->txt('msg_reserved_identifier'), $identifier));
        }
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
     * @param array $default_values
     */
    public function setDefaultValues($default_values)
    {
        $this->default_values = $default_values;
    }


    /**
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->default_values;
    }


    /**
     * @param int $is_mandatory
     */
    public function setIsMandatory($is_mandatory)
    {
        $this->is_mandatory = $is_mandatory;
    }


    /**
     * @return int
     */
    public function getIsMandatory()
    {
        return $this->is_mandatory;
    }


    /**
     * @param array $labels
     */
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }


    /**
     * @return array
     */
    public function getLabels()
    {
        return $this->labels;
    }


    /**
     * @param int $max_characters_value
     */
    public function setMaxCharactersValue($max_characters_value)
    {
        $this->max_characters_value = $max_characters_value;
    }


    /**
     * @return int
     */
    public function getMaxCharactersValue()
    {
        return $this->max_characters_value;
    }


    /**
     * @param \srCertificateType $type
     */
    public function setCertificateType($type)
    {
        $this->type = $type;
        $this->type_id = $type->getId();
    }


    /**
     * @return \srCertificateType
     */
    public function getCertificateType()
    {
        if (is_null($this->type)) {
            $this->type = srCertificateType::find($this->getTypeId());
        }

        return $this->type;
    }


    /**
     * @param array $editable_in
     */
    public function setEditableIn($editable_in)
    {
        $this->editable_in = $editable_in;
    }


    /**
     * @return array
     */
    public function getEditableIn()
    {
        return $this->editable_in;
    }


    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->type_id;
    }


    /**
     * @param int $type_id
     */
    public function setTypeId($type_id)
    {
        $this->type_id = $type_id;
    }


}
