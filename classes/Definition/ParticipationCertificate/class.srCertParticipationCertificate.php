<?php

/**
 * Class srParticipationCertificate
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertParticipationCertificate extends ActiveRecord {

	const TABLE_NAME = 'sr_cert_part_cert';

	const F_DEFINITION_ID = 'definition_id';
	const F_TYPE = 'type_id';
	const F_CONDITION_OBJECT_TYPE = 'condition_object_type';
	const F_CONDITION_OBJECT_VALUE = 'condition_object_value';
	const F_CONDITION_STATUS = 'condition_status';

	const CONDITION_OBJECT_TYPE_ANY = 0;
	const CONDITION_OBJECT_TYPE_SPECIFIC_OBJECT = 1;
	const CONDITION_OBJECT_TYPE_OBJECT_TYPE = 2;

	const CONDITION_STATUS_TYPE_COMPLETED = 0;
	const CONDITION_STATUS_TYPE_IN_PROGRESS = 1;

	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
		return self::TABLE_NAME;
	}


	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 * @db_is_primary   true
	 */
	protected $definition_id;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $type_id;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $condition_object_type = self::CONDITION_OBJECT_TYPE_ANY;
	/**
	 * @var String
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       128
	 */
	protected $condition_object_value;
	/**
	 * @var int
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $condition_status_type = self::CONDITION_STATUS_TYPE_COMPLETED;

	/**
	 * @return int
	 */
	public function getDefinitionId() {
		return $this->definition_id;
	}

	/**
	 * @param int $definition_id
	 * @return static
	 */
	public function setDefinitionId($definition_id) {
		$this->definition_id = $definition_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getTypeId() {
		return $this->type_id;
	}

	/**
	 * @param int $type_id
	 * @return static
	 */
	public function setTypeId($type_id) {
		$this->type_id = $type_id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getConditionObjectType() {
		return $this->condition_object_type;
	}

	/**
	 * @param int $condition_object_type
	 * @return static
	 */
	public function setConditionObjectType($condition_object_type) {
		$this->condition_object_type = $condition_object_type;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getConditionObjectValue() {
		return $this->condition_object_value;
	}

	/**
	 * @param String $condition_object_value
	 * @return static
	 */
	public function setConditionObjectValue($condition_object_value) {
		$this->condition_object_value = $condition_object_value;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getConditionStatusType() {
		return $this->condition_status_type;
	}

	/**
	 * @param int $condition_status_type
	 * @return static
	 */
	public function setConditionStatusType($condition_status_type) {
		$this->condition_status_type = $condition_status_type;
		return $this;
	}


}