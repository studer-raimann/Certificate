<?php

/**
 * srCertificateSetting
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
abstract class srCertificateSetting extends ActiveRecord implements srCertificateSettingInterface {

	const TABLE_NAME = 'You must define the constant TABLE_NAME in the subclass';


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return static::TABLE_NAME;
	}


	/**
	 * @return string
	 * @deprecated
	 */
	public static function returnDbTableName() {
		return static::TABLE_NAME;
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
	 * @var string
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       256
	 */
	protected $identifier = '';
	/**
	 * @var string
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       1204
	 */
	protected $value;


	public function __construct($id = 0) {
		parent::__construct($id);
	}


	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}


	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
}
