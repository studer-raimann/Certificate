<?php

/**
 * srCertificate
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version
 */
class srCertificate extends ActiveRecord {

	const TABLE_NAME = 'cert_obj';
	// Add a new status to method getAllStatus()
	const STATUS_DRAFT = 0;
	const STATUS_NEW = 1;
	const STATUS_WORKING = 2;
	const STATUS_PROCESSED = 3;
	const STATUS_FAILED = 4;
	const STATUS_CALLED_BACK = 5;
	// timezone offets are from -39600 to +46800
	const TIME_ZONE_CORRECTION = 39600;


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @return string
	 * @deprecated
	 */
	public static function returnDbTableName() {
		return self::TABLE_NAME;
	}


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
	 * @var boolean
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       1
	 * @db_is_notnull   true
	 */
	protected $active = false;
	/**
	 * @var string
	 *
	 * @db_has_field    true
	 * @db_fieldtype    timestamp
	 */
	protected $created_at;
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
	 *
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
	/**
	 * @var int
	 */
	protected $old_status;
	/**
	 * @var ilAppEventHandler
	 */
	protected $event_handler;


	public function __construct($id = 0) {
		global $DIC;
		parent::__construct($id);
		$this->log = $DIC["ilLog"];
		$this->pl = ilCertificatePlugin::getInstance();
		$this->event_handler = $DIC->event();
	}


	// Public


	/**
	 * @param bool $anonymized
	 *
	 * @return srCertificateStandardPlaceholders
	 */
	public function getStandardPlaceholders($anonymized = false) {
		if (is_null($this->standard_placeholders)) {
			$this->standard_placeholders = new srCertificateStandardPlaceholders($this, $anonymized);
		}

		return $this->standard_placeholders;
	}


	/**
	 * Get the path where this certificate is stored (without trailing slash)
	 *
	 * @return string
	 */
	public function getCertificatePath() {
		return CLIENT_DATA_DIR . '/cert_data/' . self::createPathFromId($this->getUserId()) . '/cert_' . $this->getId();
	}


	/**
	 * Get the full path and filename
	 *
	 * @return string
	 */
	public function getFilePath() {
		return $this->getCertificatePath() . '/' . $this->getFilename();
	}


	/**
	 * Convert fields before saving to DB
	 *
	 * @param $field_name
	 *
	 * @return mixed
	 */
	public function sleep($field_name) {
		switch ($field_name) {
			case 'active':
				return (int)$this->active;
		}

		return NULL;
	}


	/**
	 * Create certificate
	 * Before calling parent::create(), the valid_from and valid_to are are calculated based on the chosen validity in the definition
	 * If there exists already a certificate for the given definition and user, the version is increased
	 *
	 * @throws Exception
	 */
	public function create() {
		if (is_null($this->getDefinition()) || !$this->getUserId()) {
			throw new Exception("srCertificate::create() must have valid Definition and User-ID");
		}
		// Set validity dates
		$valid_from = $this->getValidFrom() ? $this->getValidFrom() : date("Y-m-d");
		$valid_to = $this->calculateValidTo();
		$this->setValidFrom($valid_from);
		$this->setValidTo($valid_to);
		// Check if we need to increase the version if a certificate for same user & definition already exists
		/** @var srCertificate $cert_last_version */
		$certs = srCertificate::where(array(
			'definition_id' => $this->getDefinitionId(),
			'user_id' => $this->getUserId(),
		))->orderBy('file_version', 'DESC');
		$cert_last_version = $certs->first();
		if (!is_null($cert_last_version)) {
			$this->setFileVersion((int)$cert_last_version->getFileVersion() + 1);
		}

		// Remove active flag from other versions of this certificate
		/** @var srCertificate $cert */
		foreach ($certs->get() as $cert) {
			$cert->setActive(false);
			$cert->save();
		}
		// Set active flag
		$this->setActive(true);
		// Set the filename for certificate
		$this->filename = $this->createFilename();
		$this->created_at = date('Y-m-d H:i:s');
		parent::create();
		$this->event_handler->raise('Certificate/srCertificate', 'create', array( 'object' => $this ));
	}


	public function update() {
		parent::update();
		if ($this->hasStatusChanged()) {
			// Status has changed
			$this->event_handler->raise('Certificate/srCertificate', 'changeStatus', array(
				'object' => $this,
				'old_status' => $this->old_status,
				'new_status' => $this->status,
			));
		}
		$this->event_handler->raise('Certificate/srCertificate', 'update', array( 'object' => $this ));
		$this->old_status = NULL;
	}


	/**
	 * Also delete certificate file
	 */
	public function delete() {
		parent::delete();
		@unlink($this->getFilePath());
	}


	/**
	 * Generate certificate pdf
	 *
	 * @param bool $force If true, recreates the PDF if already existing
	 *
	 * @return bool
	 */
	public function generate($force = false) {
		// Don't generate certificate if a PDF is already existing, unless $force is set to true
		if ($this->getStatus() == self::STATUS_PROCESSED && is_file($this->getFilePath()) && !$force) {
			return false;
		}
		$cert_type = $this->getDefinition()->getType();
		$template_type = srCertificateTemplateTypeFactory::getById($cert_type->getTemplateTypeId());
		$this->setStatus(self::STATUS_WORKING);
		$this->update();
		$generated = $template_type->generate($this);
		// Only set the status to processed if generating was successful
		if ($generated) {
			$this->setStatus(self::STATUS_PROCESSED);
			$this->update();

			return true;
		} else {
			$this->setStatus(self::STATUS_FAILED);
			$this->update();
			$this->log->write("srCertificate::generate() Failed to generate certificate with ID {$this->getId()}");

			return false;
		}
	}


	/**
	 * Download certificate
	 *
	 * Note: No permission checking, this must be done by the controller calling this method
	 */
	public function download() {
		if ($this->status != self::STATUS_PROCESSED && $this->status != self::STATUS_CALLED_BACK) {
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
	 * @return array
	 */
	public static function getAllStatus() {
		return array(
			self::STATUS_DRAFT,
			self::STATUS_NEW,
			self::STATUS_WORKING,
			self::STATUS_PROCESSED,
			self::STATUS_FAILED,
			self::STATUS_CALLED_BACK,
		);
	}


	/**
	 * Create a path from an id: e.g 12345 will be converted to 1/23/45
	 *
	 * @access public
	 * @static
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public static function createPathFromId($id) {
		$path = array();
		$found = false;
		$id = (int)$id;
		for ($i = 2; $i >= 0; $i --) {
			$factor = pow(100, $i);
			if (($tmp = (int)($id / $factor)) or $found) {
				$path[] = $tmp;
				$id = $id % $factor;
				$found = true;
			}
		}

		$path_string = '';
		if (count($path)) {
			$path_string = implode('/', $path);
		}

		return $path_string;
	}


	/**
	 * Download the given IDs of certificates as ZIP-File.
	 * Note: No permission checking, this must be done by the controller calling this method
	 *
	 * @param array  $cert_ids
	 * @param string $filename Filename of zip, appended to the current date
	 */
	public static function downloadAsZip(array $cert_ids = array(), $filename = 'certificates') {
		if (count($cert_ids)) {
			$zip_filename = date('d-m-Y') . '-' . $filename;
			// Make a random temp dir in ilias data directory
			$tmp_dir = ilUtil::ilTempnam();
			ilUtil::makeDir($tmp_dir);
			$zip_base_dir = $tmp_dir . DIRECTORY_SEPARATOR . $zip_filename;
			ilUtil::makeDir($zip_base_dir);
			// Copy all PDFs in folder
			foreach ($cert_ids as $cert_id) {
				/** @var srCertificate $cert */
				$cert = srCertificate::find((int)$cert_id);
				if (!is_null($cert) && $cert->getStatus() == srCertificate::STATUS_PROCESSED) {
					copy($cert->getFilePath(), $zip_base_dir . DIRECTORY_SEPARATOR . $cert->getFilename(true));
				}
			}
			$tmp_zip_file = $tmp_dir . DIRECTORY_SEPARATOR . $zip_filename . '.zip';
			try {
				ilUtil::zip($zip_base_dir, $tmp_zip_file);
				rename($tmp_zip_file, $zip_file = ilUtil::ilTempnam());
				ilUtil::delDir($tmp_dir);
				ilUtil::deliverFile($zip_file, $zip_filename . '.zip', '', false, true);
			} catch (ilFileException $e) {
				ilUtil::sendInfo($e->getMessage());
			}
		}
	}


	/**
	 * Get Certificate data as array.
	 * This method accepts an array with the following keys:
	 * - filters: Array containing key/value pairs to filter the data, please take a look at the code for the available fields
	 * - sort: Sorting the data, e.g. array('usr.lastname' => 'ASC')
	 * - limit: Limit from/to, e.g. array(0,30)
	 * - count: True if the query counts the number of affected records and returns the count
	 *
	 * To get only the newest version of a certificate, add the following constraint to your filters array:
	 * 'active' => 1
	 *
	 * @param array $options
	 *
	 * @return array|int
	 */
	public static function getCertificateData(array $options = array()) {
		global $DIC;
		$ilDB = $DIC->database();
		$_options = array(
			'filters' => array(),
			'sort' => array(),
			'limit' => array(),
			'count' => false,
		);
		$options = array_merge($_options, $options);

		$sql = "SELECT ";
		$sql .= ($options['count']) ? 'COUNT(*) AS count ' : 'cert.*, usr.firstname, usr.lastname, cert_type.title AS cert_type, obj_data.title AS crs_title ';
		$sql .= "FROM " . self::TABLE_NAME . " AS cert " . "INNER JOIN cert_definition AS cert_def ON (cert_def.id = cert.definition_id) "
			. "INNER JOIN cert_type ON (cert_type.id = cert_def.type_id) " . "LEFT JOIN usr_data AS usr ON (usr.usr_id = cert.user_id) "
			. "LEFT JOIN object_reference AS obj_ref ON (obj_ref.ref_id = cert_def.ref_id) "
			. "LEFT JOIN object_data AS obj_data ON (obj_data.obj_id = obj_ref.obj_id)";
		if (count($options['filters'])) {
			$sql .= " WHERE ";
			$and = "";
			foreach ($options['filters'] as $filter => $value) {
				switch ($filter) {
					case 'firstname':
					case 'lastname':
						$sql .= "{$and} usr.{$filter} LIKE " . $ilDB->quote("%{$value}%", 'text');
						break;
					case 'crs_title':
						$sql .= "{$and} obj_data.title LIKE " . $ilDB->quote("%{$value}%", 'text');
						break;
					case 'definition_id':
					case 'user_id':
					case 'file_version':
					case 'status':
					case 'id':
					case 'active':
						$sql .= "{$and} cert.{$filter} = " . $ilDB->quote($value, 'integer');
						break;
					case 'valid_from':
						$sql .= "{$and} cert.valid_from >= " . $ilDB->quote($value, 'date');
						break;
					case 'valid_to':
						$sql .= "{$and} cert.valid_to >= " . $ilDB->quote($value, 'date');
						break;
					case 'type_id':
						$sql .= "{$and} cert_type.id = " . $ilDB->quote($value, 'integer');
						break;
				}
				$and = " AND ";
			}
		}
		if (count($options['sort']) && !$options['count']) {
			$replaces = array(
				'crs_title' => 'obj_data.title',
				'cert_type' => 'cert_type.title',
			);
			$sql .= " ORDER BY ";
			foreach ($options['sort'] as $field => $dir) {
				if (isset($replaces[$field])) {
					$field = $replaces[$field];
				}
				$sql .= " {$field} {$dir},";
			}
			$sql = rtrim($sql, ',');
		}
		if (count($options['limit']) && !$options['count']) {
			$sql .= " LIMIT " . implode(',', $options['limit']);
		}
		$set = $ilDB->query($sql);
		if ($options['count']) {
			return (int)$ilDB->fetchObject($set)->count;
		}
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
	protected function calculateValidTo() {
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
				$to = ($calc_str) ? strtotime('+' . $calc_str, strtotime($this->getValidFrom())) : strtotime($this->getValidFrom());
				$valid_to = date('Y-m-d', $to);
				break;
			default:
				$valid_to = NULL; // Always valid
		}

		return $valid_to;
	}


	/**
	 * Create a (readable) filename for the certificate
	 *
	 * @return string
	 */
	protected function createFilename() {
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
	 *
	 * @return string
	 */
	protected function sanitizeStr($str) {
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
	 *
	 * @param bool $anonymized
	 */
	protected function loadPlaceholders($anonymized = false) {
		$placeholders = $this->getStandardPlaceholders($anonymized)->getParsedPlaceholders();
		$available_langs = $this->definition->getType()->getLanguages();
		$user_lang = $this->getUser()->getLanguage();
		$default_lang = $this->definition->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG)->getValue();
		$lang = (in_array($user_lang, $available_langs)) ? $user_lang : $default_lang;
		/** @var $ph_value srCertificatePlaceholderValue */
		foreach ($this->definition->getPlaceholderValues() as $ph_value) {
			$placeholders[$ph_value->getPlaceholder()->getIdentifier()] = $ph_value->getValue($lang);
		}
		// Hacky: Add signature placeholders
		if ($this->definition->getSignatureId()) {
			$signature = $this->definition->getSignature();
			$placeholders['SIGNATURE_NAME'] = $signature->getFirstName() . ' ' . $signature->getLastName();
			$placeholders['SIGNATURE_FIRSTNAME'] = $signature->getFirstName();
			$placeholders['SIGNATURE_LASTNAME'] = $signature->getLastName();
			$placeholders['SIGNATURE_IMAGE'] = $signature->getFilePath(true);
			$placeholders['SIGNATURE_IMAGE_SUFFIX'] = $signature->getSuffix();
		}
		$this->placeholders = $this->pl->getHooks()->processPlaceholders($this, $placeholders);
		$this->placeholders = srCertificatePlaceholder::getFormattedPlaceholders($this->placeholders);
	}


	// Getters & Setters


	/**
	 * @param bool $suffix Include suffix
	 *
	 * @return mixed|string
	 */
	public function getFilename($suffix = true) {
		return ($suffix) ? $this->filename : str_replace('.pdf', '', $this->filename);
	}


	/**
	 * @param \ilObjUser $user
	 */
	public function setUser($user) {
		$this->user = $user;
	}


	/**
	 * @return \ilObjUser
	 */
	public function getUser() {
		if ($this->user === NULL) {
			$this->user = new ilObjUser($this->getUserId());
		}

		return $this->user;
	}


	/**
	 * @param bool $anonymized If true, placeholders are anonymized
	 *
	 * @return array
	 */
	public function getPlaceholders($anonymized = false) {
		if ($this->placeholders === NULL) {
			$this->loadPlaceholders($anonymized);
		}

		return $this->placeholders;
	}


	/**
	 * @param int $definition_id
	 */
	public function setDefinitionId($definition_id) {
		$this->definition_id = $definition_id;
		$this->definition = srCertificateDefinition::find($definition_id);
	}


	/**
	 * @return int
	 */
	public function getDefinitionId() {
		return $this->definition_id;
	}


	/**
	 * @param \srCertificateDefinition $definition
	 */
	public function setDefinition(srCertificateDefinition $definition) {
		$this->definition = $definition;
		$this->definition_id = $definition->getId();
	}


	/**
	 * @return \srCertificateDefinition
	 */
	public function getDefinition() {
		if (is_null($this->definition)) {
			$this->definition = srCertificateDefinition::find($this->getDefinitionId());
		}

		return $this->definition;
	}


	/**
	 * @param int $file_version
	 */
	public function setFileVersion($file_version) {
		$this->file_version = $file_version;
	}


	/**
	 * @return int
	 */
	public function getFileVersion() {
		return $this->file_version;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		if ($status != $this->status) {
			$this->old_status = $this->status;
		}
		$this->status = $status;
	}


	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $user_id
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;
		$this->user = new ilObjUser($user_id);
	}


	/**
	 * @return int
	 */
	public function getUserId() {
		return $this->user_id;
	}


	/**
	 * @param int $valid_from
	 */
	public function setValidFrom($valid_from) {
		$this->valid_from = $valid_from;
	}


	/**
	 * @return int
	 */
	public function getValidFrom() {
		return $this->valid_from;
	}


	/**
	 * @param int $valid_to
	 */
	public function setValidTo($valid_to) {
		$this->valid_to = $valid_to;
	}


	/**
	 * @return int
	 */
	public function getValidTo() {
		return $this->valid_to;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param boolean $active
	 */
	public function setActive($active) {
		$this->active = $active;
	}


	/**
	 * @return boolean
	 */
	public function getActive() {
		return (bool)$this->active;
	}


	/**
	 * @return string
	 */
	public function getCreatedAt() {
		return $this->created_at;
	}


	/**
	 * @return bool
	 */
	public function hasStatusChanged() {
		return ($this->old_status !== NULL);
	}
}