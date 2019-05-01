<?php

/**
 * Class srParticipationCertificate
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertParticipationCertificate extends ActiveRecord {

	const TABLE_NAME = 'cert_parti_cert';

	const F_DEFINITION_ID = 'definition_id';
	const F_TYPE = 'type_id';
	const F_CONDITION_OBJECT_TYPE = 'condition_object_type';
	const F_CONDITION_OBJECT_VALUE_REF_IDS = 'condition_object_value_ref_ids';
	const F_CONDITION_OBJECT_VALUE_TYPES = 'condition_object_value_types';
	const F_CONDITION_STATUS = 'condition_status';

	const CONDITION_OBJECT_TYPE_ANY = 0;
	const CONDITION_OBJECT_TYPE_SPECIFIC_OBJECT = 1;
	const CONDITION_OBJECT_TYPE_OBJECT_TYPE = 2;

	const CONDITION_STATUS_TYPE_IN_PROGRESS = ilLPStatus::LP_STATUS_IN_PROGRESS_NUM;
	const CONDITION_STATUS_TYPE_COMPLETED = ilLPStatus::LP_STATUS_COMPLETED_NUM;

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
	 * @var array
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       128
	 */
	protected $condition_object_value_ref_ids;
	/**
	 * @var array
	 *
	 * @db_has_field    true
	 * @db_fieldtype    text
	 * @db_length       128
	 */
	protected $condition_object_value_types;
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
	 * @return srCertificateType
	 */
	public function getType() {
		return srCertificateType::find($this->getTypeId());
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
	 * @return array
	 */
	public function getConditionObjectValueRefIds() {
		return $this->condition_object_value_ref_ids;
	}

	/**
	 * @param int $condition_object_value_ref_ids
	 * @return static
	 */
	public function setConditionObjectValueRefIds($condition_object_value_ref_ids) {
		$this->condition_object_value_ref_ids = $condition_object_value_ref_ids;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getConditionObjectValueTypes() {
		return $this->condition_object_value_types;
	}

	/**
	 * @param array $condition_object_value_types
	 * @return static
	 */
	public function setConditionObjectValueTypes($condition_object_value_types) {
		$this->condition_object_value_types = $condition_object_value_types;
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

	/**
	 * @param $field_name
	 * @return mixed
	 */
	public function sleep($field_name) {
		switch ($field_name) {
			case self::F_CONDITION_OBJECT_VALUE_TYPES:
				return json_encode($this->getConditionObjectValueTypes());
			case self::F_CONDITION_OBJECT_VALUE_REF_IDS:
				return json_encode($this->getConditionObjectValueRefIds());
			default:
				return null;
		}
	}

	/**
	 * @param $field_name
	 * @param $field_value
	 * @return mixed
	 */
	public function wakeUp($field_name, $field_value) {
		switch ($field_name) {
			case self::F_CONDITION_OBJECT_VALUE_REF_IDS:
			case self::F_CONDITION_OBJECT_VALUE_TYPES:
				return json_decode($field_value, true);
			default:
				return null;
		}
	}


}