<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
require_once(dirname(dirname(__FILE__)) . '/Placeholder/class.srCertificateStandardPlaceholders.php');
require_once(dirname(dirname(__FILE__)) . '/Placeholder/class.srCertificatePlaceholder.php');
require_once(dirname(dirname(__FILE__)) . '/Definition/class.srCertificateDefinition.php');
require_once(dirname(dirname(__FILE__)) . '/TemplateType/class.srCertificateTemplateTypeFactory.php');
require_once(dirname(dirname(__FILE__)) . '/class.ilCertificatePlugin.php');

/**
 * srCertificate
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificate extends ActiveRecord
{

    const TABLE_NAME = 'cert_obj';

    const STATUS_DRAFT = 0;
    const STATUS_NEW = 1;
    const STATUS_WORKING = 2;
    const STATUS_PROCESSED = 3;

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
     * @db_is_notnull   true
     */
    protected $user_id;


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_notnull   true
     */
    protected $definition_id;


    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    date
     * @db_is_notnull   true
     */
    protected $valid_from;


    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    date
     */
    protected $valid_to;

    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_notnull   true
     */
    protected $file_version = 1;


    /**
     * @var string
     *
     * @db_has_field    true
     * @db_fieldtype    text
     * @db_length       256
     */
    protected $filename = '';


    /**
     * @var int
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_notnull   true
     */
    protected $status = self::STATUS_DRAFT;

    /**
     * @var srCertificateStandardPlaceholders
     */
    protected $standard_placeholders;

    /**
     * Contains all the loaded standard and custom placeholders for this certificate (loaded by calling getter)
     * @var array
     */
    protected $placeholders;

    /**
     * @var srCertificateDefinition
     */
    protected $definition;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @var ilLog
     */
    protected $log;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;


    public function __construct($id = 0)
    {
        global $ilLog;
        parent::__construct($id);
        $this->log = $ilLog;
        $this->pl = new ilCertificatePlugin();
        $this->standard_placeholders = new srCertificateStandardPlaceholders($this);
    }


    // Public


    public function afterObjectLoad()
    {
        $this->definition = srCertificateDefinition::find($this->getDefinitionId());
    }


    /**
     * Get the path where this certificate is stored (without trailing slash)
     *
     * @return string
     */
    public function getCertificatePath()
    {
        return CLIENT_DATA_DIR . DIRECTORY_SEPARATOR . 'cert_data' . DIRECTORY_SEPARATOR .
        self::createPathFromId($this->getUserId()) . DIRECTORY_SEPARATOR . 'cert_' . $this->getId();

    }


    /**
     * Get the full path and filename
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->getCertificatePath() . DIRECTORY_SEPARATOR . $this->getFilename();
    }


    /**
     * Create certificate
     * Before calling parent::create(), the valid_from and valid_to are are calculated based on the chosen validity in the definition
     * If there exists already a certificate for the given definition and user, the version is increased
     *
     * @throws Exception
     */
    public function create()
    {
        if ($this->getDefinition() === NULL || !$this->getUserId())
            throw new Exception("srCertificate::create() must have valid Definition and User-ID");

        // Set validity dates
        $valid_from = date("Y-m-d");
        $valid_to = $this->calculateValidTo();
        $this->setValidFrom($valid_from);
        $this->setValidTo($valid_to);

        // Check if we need to increase the version if a certificate for same user & definition already exists
        /** @var srCertificate $cert_existing */
        $cert_existing = srCertificate::where(
            array(
                'definition_id' => $this->getDefinitionId(),
                'user_id' => $this->getUserId(),
            )
        )->orderBy('file_version', 'DESC')->first();
        if ($cert_existing !== null) {
            $this->setFileVersion((int)$cert_existing->getFileVersion() + 1);
        }

        // Set the filename for certificate
        $this->filename = $this->createFilename();
        parent::create();
    }


    /**
     * Also delete certificate file
     */
    public function delete()
    {
        parent::delete();
        @unlink($this->getFilePath());
    }


    /**
     * Generate certificate pdf
     *
     * @param bool $force If true, recreates the PDF if already existing
     * @return bool
     */
    public function generate($force = false)
    {
        // Don't generate certificate if a PDF is already existing, unless $force is set to true
        if ($this->getStatus() == self::STATUS_PROCESSED && is_file($this->getFilePath()) && !$force) {
            return false;
        }
        $cert_type = $this->definition->getType();
        $template_type = srCertificateTemplateTypeFactory::getById($cert_type->getTemplateTypeId());
        $this->setStatus(srCertificate::STATUS_WORKING);
        $this->update();
        $generated = $template_type->generate($this);
        // Only set the status to processed if generating was successful
        if ($generated) {
            $this->setStatus(srCertificate::STATUS_PROCESSED);
            $this->update();
            return true;
        } else {
            $this->log->write("srCertificate::generate() Failed to generate certificate with ID {$this->getId()}");
            return false;
        }
    }


    /**
     * Download certificate
     */
    public function download()
    {
        if ($this->status != self::STATUS_PROCESSED) {
            return;
        }
        $file = $this->getFilePath();
        if (!is_file($file)) {
            $this->log->write("srCertificate::download(): Trying to download certificate but file is missing $file");
        }
        ilUtil::deliverFile($file, $this->getFilename(), 'application/pdf');
    }


    // Static

    /**
     * Create a path from an id: e.g 12345 will be converted to 1/23/45
     *
     * @access public
     * @static
     *
     * @param int $id
     * @return string
     */
    public static function createPathFromId($id)
    {
        $path = array();
        $found = false;
        $id = (int)$id;
        for ($i = 2; $i >= 0; $i--) {
            $factor = pow(100, $i);
            if (($tmp = (int)($id / $factor)) or $found) {
                $path[] = $tmp;
                $id = $id % $factor;
                $found = true;
            }
        }

        $path_string = '';
        if (count($path)) {
            $path_string = implode(DIRECTORY_SEPARATOR, $path);
        }
        return $path_string;
    }


    /**
     * @return string
     * @description Return the Name of your Database Table
     */
    static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }


    /**
     * Get Certificate data as array
     *
     * @param array $filters Optional filtering query with the following keys:
     *                                      definition_id, user_id, file_version, status
     * @param array $sort Fields as keys, direction as values. E.g. array('usr.lastname' => 'ASC')
     * @param bool $only_newest_version If false, returns multiple file versions of the same certificate
     * @return array
     */
    static public function getCertificateData($filters = array(), $sort = array(), $only_newest_version = true)
    {
        global $ilDB;
        /** @var ilDB $ilDB */
        $sql = "SELECT cert.*, usr.firstname, usr.lastname FROM cert_obj AS cert
                INNER JOIN usr_data AS usr ON (usr.usr_id = cert.user_id)";
        if (count($filters) || $only_newest_version) {
            $sql .= " WHERE ";
            $and = "";
            if (isset($filters['definition_id'])) {
                $sql .= "{$and} cert.definition_id = " . $ilDB->quote($filters['definition_id'], 'integer');
                $and = " AND ";
            }
            if (isset($filters['user_id'])) {
                $sql .= "{$and} cert.user_id = " . $ilDB->quote($filters['user_id'], 'integer');
                $and = " AND ";
            }
            if (isset($filters['file_version'])) {
                $sql .= "{$and} cert.file_version = " . $ilDB->quote($filters['file_version'], 'integer');
                $and = " AND ";
            }
            if (isset($filters['status'])) {
                $sql .= "{$and} cert.status = " . $ilDB->quote($filters['status'], 'integer');
                $and = " AND ";
            }
            if ($only_newest_version) {
                $sql .= "{$and} cert.file_version IN (SELECT MAX(file_version) FROM cert_obj WHERE cert_obj.definition_id = cert.definition_id AND cert_obj.user_id = cert.user_id)";
            }
        }
        if (count($sort)) {
            $sql .= " ORDER BY ";
            foreach ($sort as $field => $dir) {
                $sql .= " {$field} {$dir},";
            }
            $sql = rtrim(',', $sql);
        }
        $set = $ilDB->query($sql);
        $data = array();
        while ($row = $ilDB->fetchAssoc($set)) {
            $data[] = $row;
        }
        return $data;
    }


    // Protected

    /**
     * Get the calculated valid-to date based on the validity type
     *
     * @return null|string
     */
    protected function calculateValidTo()
    {
        $validity = $this->definition->getValidity();
        $validity_type = $this->definition->getValidityType();
        switch ($validity_type) {
            case srCertificateTypeSetting::VALIDITY_TYPE_DATE:
                // Date already stored in Y-m-d format
                $valid_to = $validity;
                break;
            case srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE:
                $validity = json_decode($validity, true);
                $calc_str = '';
                if (isset($validity['m'])) {
                    $calc_str .= $validity['m'] . ' months';
                }
                if (isset($validity['d'])) {
                    $calc_str .= ' ' . $validity['d'] . 'days';
                }
                $to = ($calc_str) ? strtotime('+' . $calc_str) : time();
                $valid_to = date('Y-m-d', $to);
                break;
            default:
                $valid_to = null; // Always valid
        }
        return $valid_to;
    }


    /**
     * Create a (readable) filename for the certificate
     *
     * @return string
     */
    protected function createFilename()
    {
        $ref_id = $this->definition->getRefId();
        $obj_title = ilObject::_lookupTitle(ilObject::_lookupObjectId($ref_id));
        $user_name = $this->getUser()->getLastname() . '-' . $this->getUser()->getFirstname();
        $filename_elements = array(
            date('Y-m-d', strtotime($this->getValidFrom())),
            $this->sanitizeStr($user_name),
            $this->sanitizeStr($obj_title),
        );
        $filename = implode('-', $filename_elements);
        $filename = rtrim($filename, '-');
        return $filename . '.pdf';
    }


    /**
     * Sanitize a string: Convert UTF-8 to ASCII, remove spaces and other unwanted characters
     *
     * @param $str
     * @return string
     */
    protected function sanitizeStr($str)
    {
        $str = mb_strtolower($str);
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('#[^a-z0-9\-]+#', '-', $str); // Replace spaces and other unwanted characters
        $str = preg_replace('#-{2,}#', '-', $str); // Replace multiple dashes
        return $str;
    }


    /**
     * Load all the placeholders (standard and custom) with key => value
     * Custom placeholders are loaded in the correct language
     * All placeholders are passed to the hook class to do custom logic.
     * Finally keys are wrapped with the start/end symbols, e.g. [[key]]
     */
    protected function loadPlaceholders()
    {
        $placeholders = $this->standard_placeholders->getParsedPlaceholders();
        $available_langs = $this->definition->getType()->getLanguages();
        $user_lang = $this->getUser()->getLanguage();
        $default_lang = $this->definition->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG);
        $lang = (in_array($user_lang, $available_langs)) ? $user_lang : $default_lang;
        /** @var $ph_value srCertificatePlaceholderValue */
        foreach ($this->definition->getPlaceholderValues() as $ph_value) {
            $placeholders[$ph_value->getPlaceholder()->getIdentifier()] = $ph_value->getValue($lang);
        }
        $this->placeholders = $this->pl->getHooks()->processPlaceholders($this, $placeholders);
        $this->placeholders = srCertificatePlaceholder::getFormattedPlaceholders($this->placeholders);
    }


    // Getters & Setters


    /**
     * @param bool $suffix Include suffix
     * @return mixed|string
     */
    public function getFilename($suffix = true)
    {
        return ($suffix) ? $this->filename : str_replace('.pdf', '', $this->filename);
    }

    /**
     * @param \ilObjUser $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return \ilObjUser
     */
    public function getUser()
    {
        if ($this->user === NULL) {
            $this->user = new ilObjUser($this->getUserId());
        }
        return $this->user;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        if ($this->placeholders === NULL) {
            $this->loadPlaceholders();
        }
        return $this->placeholders;
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

    /**
     * @param \srCertificateDefinition $definition
     */
    public function setDefinition(srCertificateDefinition $definition)
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
     * @param int $file_version
     */
    public function setFileVersion($file_version)
    {
        $this->file_version = $file_version;
    }

    /**
     * @return int
     */
    public function getFileVersion()
    {
        return $this->file_version;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        $this->user = new ilObjUser($user_id);
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $valid_from
     */
    public function setValidFrom($valid_from)
    {
        $this->valid_from = $valid_from;
    }

    /**
     * @return int
     */
    public function getValidFrom()
    {
        return $this->valid_from;
    }

    /**
     * @param int $valid_to
     */
    public function setValidTo($valid_to)
    {
        $this->valid_to = $valid_to;
    }

    /**
     * @return int
     */
    public function getValidTo()
    {
        return $this->valid_to;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}