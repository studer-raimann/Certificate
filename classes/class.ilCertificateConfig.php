<?php

/**
 * Class ilCertificateConfig
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilCertificateConfig extends ActiveRecord {

	const DATE_FORMAT = 'str_format_date';
	const DATETIME_FORMAT = 'str_format_datetime';
	const PATH_HOOK_CLASS = 'path_hook_class';
	const ROLES_ADMINISTRATE_CERTIFICATE_TYPES = 'roles_administrate_certificate_types';
	const ROLES_ADMINISTRATE_CERTIFICATES = 'roles_administrate_certificates';
	const DISK_SPACE_WARNING = 'disk_space_warning';
	const TABLE_NAME = 'uihkcertificate_c';
	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var array
	 */
	protected static $cache_loaded = array();
	/**
	 * @var bool
	 */
	protected $ar_safe_read = false;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           128
	 */
	protected $name;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        clob
	 */
	protected $value;


	/**
	 * @param $name
	 *
	 * @return string
	 */
	public static function getX($name) {
		if (!isset(self::$cache_loaded[$name])) {
			$obj = self::find($name);
			if ($obj === NULL) {
				self::$cache[$name] = NULL;
			} else {
				self::$cache[$name] = $obj->getValue();
			}
			self::$cache_loaded[$name] = true;
		}

		return self::$cache[$name];
	}


	/**
	 * @param $name
	 * @param $value
	 *
	 * @return null
	 */
	public static function setX($name, $value) {
		/**
		 * @var $obj arConfig
		 */
		$obj = self::findOrGetInstance($name);
		$obj->setValue($value);
		if (self::where(array( 'name' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	public static function returnDbTableName() {
		return self::TABLE_NAME;
	}


	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}


	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}