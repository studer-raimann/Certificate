<?php
require_once(dirname(dirname(__FILE__)) . '/TemplateType/class.srCertificateTemplateType.php');
require_once(dirname(dirname(__FILE__)) . '/Placeholder/class.srCertificatePlaceholder.php');
require_once(dirname(__FILE__) . '/class.srCertificateTypeSetting.php');

/**
 * srCertificateType
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateType extends ActiveRecord
{

    /**
     * MySQL Table-Name
     *
     */
    const TABLE_NAME = 'cert_type';

    /**
     * Objects (type) are allowed to generate certificates
     *
     * @var array
     */
    protected static $all_available_object_types = array('crs', 'crs-tpl');

    /**
     * Default settings with default values. They get created with this object.
     *
     * @var array
     */
    protected static $default_settings = array(
        srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG => array('default_value' => ''),
        srCertificateTypeSetting::IDENTIFIER_GENERATION => array('default_value' => srCertificateTypeSetting::GENERATION_AUTO),
        srCertificateTypeSetting::IDENTIFIER_NOTIFICATION => array('default_value' => ''),
        srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER => array('default_value' => 0),
        srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE => array('default_value' => srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE),
        srCertificateTypeSetting::IDENTIFIER_VALIDITY => array('default_value' => ''),
        srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE => array('default_value' => 1),
    );

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
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       256
     */
    protected $title = '';

    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $description = '';


    /**
     * Available languages for certificates of this type
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $languages = array();


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $template_type_id = srCertificateTemplateType::TEMPLATE_TYPE_JASPER;


    /**
     * Role-IDs which are allowed to choose this certificate type in a definition
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       4000
     */
    protected $roles = array();


    /**
     * Objects where this certificate type is available, e.g. 'crs', 'tst'...
     *
     * @var array
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       512
     */
    protected $available_objects = array();


    /**
     * Placeholders defined by this certificate type
     *
     * @var array srCertificatePlaceholder[]
     */
    protected $placeholders = array();

    /**
     * Settings of this certificate
     *
     * @var array srCertificateTypeSetting[]
     */
    protected $settings = array();

    /**
     * @var array
     */
    protected $custom_settings;


    public function __construct($id = 0)
    {
        parent::__construct($id);
    }


    // Public


    public function afterObjectLoad()
    {
        $placeholders = srCertificatePlaceholder::where(array('type_id' => (int) $this->getId()))->get();
        $this->setPlaceholders($placeholders);
        $settings = srCertificateTypeSetting::where(array('type_id' => (int) $this->getId()))->get();
        $this->setSettings($settings);
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
            case 'languages':
            case 'roles':
            case 'available_objects':
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
            case 'languages':
            case 'roles':
            case 'available_objects':
                $value = json_encode($value);
                break;
        }
        return $value;
    }


    /**
     * Get a path where the template layout file and static assets are stored
     *
     * @param bool $append_file True if filename should be included
     * @return string
     */
    public function getCertificateTemplatesPath($append_file = false)
    {
        $path = CLIENT_DATA_DIR . '/cert_templates/type_' . $this->getId() . '/template_type_' . $this->getTemplateTypeId();
        if ($append_file) {
            $filename = srCertificateTemplateTypeFactory::getById($this->getTemplateTypeId())->getTemplateFilename();
            return $path . '/' . $filename;
        }
        return $path;
    }


    /**
     * Get an array of all assets stored along with the certificate
     *
     * @return array
     */
    public function getAssets()
    {
        if ( ! is_dir($this->getCertificateTemplatesPath())) {
            ilUtil::makeDirParents($this->getCertificateTemplatesPath());
        }
        $files = scandir($this->getCertificateTemplatesPath());
        $tpl_filename = srCertificateTemplateTypeFactory::getById($this->getTemplateTypeId())->getTemplateFilename();
        $ignore = array('.', '..', '.DS_Store', $tpl_filename);
        foreach ($files as $k => $file) {
            if (in_array($file, $ignore)) {
                unset($files[$k]);
            }
        }
        return $files;
    }


    /**
     * Store an asset file, e.g. an image
     *
     * @param array $file_data Array from PHPs $_FILES array
     * @return bool
     */
    public function storeAsset(array $file_data)
    {
        if ($file_data['name'] && ! $file_data['error']) {
            $file_name = $file_data['name'];
            $file_path = $this->getCertificateTemplatesPath() . DIRECTORY_SEPARATOR . $file_name;
            return ilUtil::moveUploadedFile($file_data['tmp_name'], $file_name, $file_path, false);
        }
        return false;
    }


    /**
     * Store a new template file
     * Note that this method does not check for a valid file. It will rename the file (name and suffix) according
     * to the template file of the srCertificateTemplateType instance. An existing file will be overridden!
     *
     * @param array $file_data Array from PHPs $_FILES array
     * @return bool
     */
    public function storeTemplateFile(array $file_data)
    {
        if ($file_data['name'] && ! $file_data['error']) {

	        return $this->storeTemplateFileFromServer($file_data['tmp_name']);
        }
        return false;
    }


	/**
	 * Store a new template file.
	 *
	 * @param $path_to_template_file string
	 *
	 * @return bool
	 */
	public function storeTemplateFileFromServer($path_to_template_file) {
		if(!is_file($path_to_template_file)){
			return false;
		}
		$this->createTemplateDirectory();
		return copy($path_to_template_file, $this->getCertificateTemplatesPath(true));
	}


	/**
	 *
	 */
	protected function createTemplateDirectory(){
		$file_path = $this->getCertificateTemplatesPath();
		if ( ! is_dir($file_path)) {
			ilUtil::makeDirParents($file_path);
		}
	}

    /**
     * Remove a given asset
     *
     * @param string $filename
     */
    public function removeAsset($filename)
    {
        $file_path = $this->getCertificateTemplatesPath() . DIRECTORY_SEPARATOR . $filename;
        if (is_file($file_path)) {
            unlink($file_path);
        }
    }


    public function create()
    {
        parent::create();
        $this->createDefaultSettings();
    }


    /**
     * Get a setting by identifier
     *
     * @param $identifier
     * @return null|srCertificateTypeSetting
     */
    public function getSettingByIdentifier($identifier)
    {
        /** @var $setting srCertificateTypeSetting */
        foreach ($this->getSettings() as $setting) {
            if ($setting->getIdentifier() == $identifier) {
                return $setting;
                break;
            }
        }

        return null;
    }


    /**
     * Get a custom setting by identifier
     *
     * @param $identifier
     * @return null|\srCertificateTypeSetting
     */
    public function getCustomSettingByIdentifier($identifier)
    {
        /** @var $setting srCertificateTypeSetting */
        foreach ($this->getCustomSettings() as $setting) {
            if ($setting->getIdentifier() == $identifier) {
                return $setting;
                break;
            }
        }

        return null;
    }


    /**
     * Delete also related certificate definitions and assets
     */
    public function delete()
    {
        parent::delete();
        $definitions = srCertificateDefinition::where(array('type_id' => $this->getId()))->get();
        /** @var $def srCertificateDefinition */
        foreach ($definitions as $def) {
            $def->delete();
        }
    }


    // Static

    /**
     * Check if a given user is allowed to select a given certificate type to create a new definition for the given ref_id.
     * This method checks the following steps:
     *  1) Is the type restricted to certain object types, e.g. is the object type of the given ref_id valid?
     *  2) Is the type restricted to certain roles, e.g. check if the a user has at least one role
     *
     * @param srCertificateType $type
     * @param $ref_id
     * @param int $user_id If empty, the current user is used
     * @return bool
     */
    public static function isSelectable(srCertificateType $type, $ref_id, $user_id = 0)
    {
        global $ilUser, $rbacreview;
        $user_id = ($user_id) ? $user_id : $ilUser->getId();
        $pl = ilCertificatePlugin::getInstance();
        $object_type = ($pl->isCourseTemplate($ref_id)) ? 'crs-tpl' : ilObject::_lookupType($ref_id, true);
        if ( ! in_array($object_type, $type->getAvailableObjects())) {
            return false;
        }
        // Access restricted by roles. Check if current user has a role to choose the type
        if (count($type->getRoles()) && ! $rbacreview->isAssignedToAtLeastOneGivenRole($user_id, $type->getRoles())) {
            return false;
        }
        return true;
    }


    /**
     * @return string
     * @description Return the Name of your Database Table
     */
    static function returnDbTableName()
    {
        return static::TABLE_NAME;
    }


    /**
     * Return all object types where a certificate type can be defined
     *
     * @return array
     */
    public static function getAllAvailableObjectTypes()
    {
        $types = self::$all_available_object_types;
        // crs-tpl is only available if activated in the plugin config
        if ( ! ilCertificateConfig::get('course_templates')) {
            $key = array_search('crs-tpl', $types);
            unset($types[$key]);
        }
        return $types;
    }


    /**
     * Get the default settings
     *
     * @return array
     */
    public static function getDefaultSettings()
    {
        return self::$default_settings;
    }

    // Protected

    /**
     * Create corresponding default settings after creating type object
     *
     */
    protected function createDefaultSettings()
    {
        foreach (self::$default_settings as $identifier => $config) {
            $setting = new srCertificateTypeSetting();
            $setting->setIdentifier($identifier);
            $setting->setEditableIn($this->available_objects);
            $setting->setTypeId($this->getId());
            $setting->setValue($config['default_value']);
            $setting->create();
            $this->settings[] = $setting;
        }
    }


    // Getters & Setters

    /**
     * @param array $available_objects
     */
    public function setAvailableObjects($available_objects)
    {
        $this->available_objects = $available_objects;
    }


    /**
     * @return array
     */
    public function getAvailableObjects()
    {
        return $this->available_objects;
    }


    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * @param array $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }


    /**
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages;
    }


    /**
     * @param array $placeholders
     */
    public function setPlaceholders($placeholders)
    {
        $this->placeholders = $placeholders;
    }


    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }


    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }


    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
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
        return $this->settings;
    }


    /**
     * @return array
     */
    public function getCustomSettings()
    {
        if (is_null($this->custom_settings)) {
            $this->custom_settings = srCertificateCustomTypeSetting::where(array('type_id' => $this->getId()))->get();
        }

        return $this->custom_settings;
    }


    /**
     * @param int $template_type_id
     */
    public function setTemplateTypeId($template_type_id)
    {
        $this->template_type_id = $template_type_id;
    }


    /**
     * @return int
     */
    public function getTemplateTypeId()
    {
        return $this->template_type_id;
    }


    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}
