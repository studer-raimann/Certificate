<?php

/**
 * srCertificateSignature
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version
 */
class srCertificateSignature extends ActiveRecord {

	/**
	 * MySQL Table-Name
	 */
	const TABLE_NAME = 'cert_signature';
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
	 * @var int ID of srCertificateType where this signature belongs to
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $type_id;
	/**
	 * @var String suffix of file
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       256
	 */
	protected $suffix;
	/**
	 * @var String first name of signatures owner
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       256
	 */
	protected $first_name;
	/**
	 * @var String last name of signatures owner
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       256
	 */
	protected $last_name;
	/**
	 * @var srCertificateType Object where this signature belongs to
	 */
	protected $type;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;


	public function __construct($id = 0) {
		parent::__construct($id);
		$this->pl = ilCertificatePlugin::getInstance();
	}


	// Public

	public function delete() {
		parent::delete();
		@unlink($this->getFilePath(true));
		@unlink($this->getFilePath(true) . '.' . $this->getSuffix());

		// We must update any definitions holding this signature
		$definitions = srCertificateDefinition::where(array( 'signature_id' => $this->getId() ))->get();
		foreach ($definitions as $definition) {
			/** @var srCertificateDefinition $definition */
			$definition->setSignatureId(0);
			$definition->update();
		}
	}


	public function download() {
		ilUtil::deliverFile($this->getFilePath(true), 'signature_' . $this->getLastName() . '_' . $this->getFirstName() . '.' . $this->getSuffix());
	}


	/**
	 * Get the path where the signature file is stored
	 *
	 * @param bool $append_file True if filename should be included
	 *
	 * @return string
	 */
	public function getFilePath($append_file = false) {
		if (!$this->getTypeId()) {
			return '';
		}
		$path = CLIENT_DATA_DIR . '/cert_signatures/type_' . $this->getTypeId();
		if ($append_file) {
			return $path . '/sig_' . $this->getId();
		}

		return $path;
	}


	/**
	 * @param array $file_data
	 *
	 * @return bool
	 */
	public function storeSignatureFile(array $file_data) {
		if ($file_data['name'] && !$file_data['error']) {
			$file_path = $this->getFilePath(false);
			if (!is_dir($file_path)) {
				ilUtil::makeDirParents($file_path);
			}
			$suffix = pathinfo($file_data['name'], PATHINFO_EXTENSION);
			$this->setSuffix($suffix);

			return copy($file_data['tmp_name'], $this->getFilePath(true));
		}

		return false;
	}


	// Static


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}


	// Getters & Setters


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param \srCertificateType $type
	 */
	public function setCertificateType($type) {
		$this->type = $type;
		$this->type_id = $type->getId();
	}


	/**
	 * @return \srCertificateType
	 */
	public function getCertificateType() {
		if (is_null($this->type)) {
			$this->type = srCertificateType::find($this->getTypeId());
		}

		return $this->type;
	}


	/**
	 * @return int
	 */
	public function getTypeId() {
		return $this->type_id;
	}


	/**
	 * @param int $type_id
	 */
	public function setTypeId($type_id) {
		$this->type_id = $type_id;
	}


	/**
	 * @param String $first_name
	 */
	public function setFirstName($first_name) {
		$this->first_name = $first_name;
	}


	/**
	 * @return String
	 */
	public function getFirstName() {
		return $this->first_name;
	}


	/**
	 * @param String $last_name
	 */
	public function setLastName($last_name) {
		$this->last_name = $last_name;
	}


	/**
	 * @return String
	 */
	public function getLastName() {
		return $this->last_name;
	}


	/**
	 * @return String
	 */
	public function getSuffix() {
		return $this->suffix;
	}


	/**
	 * @param String $suffix
	 */
	public function setSuffix($suffix) {
		$this->suffix = strtolower($suffix);
	}
}
