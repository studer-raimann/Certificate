<?php

/**
 * srCertificateDefinition
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateDefinition extends ActiveRecord {

	const TABLE_NAME = 'cert_definition';
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
	protected $type_id;
	/**
	 * @var int Ref-ID to ILIAS object where this definition belongs to
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $ref_id = 0;
	/**
	 * @var int ID of srCertificateSignature
	 *
	 * @db_has_field    true
	 * @db_fieldtype    integer
	 * @db_length       8
	 */
	protected $signature_id = 0;
	/**
	 * @var srCertificateType
	 */
	protected $type;
	/**
	 * @var array srCertificateDefinitionSetting[]
	 */
	protected $settings;
	/**
	 * @var array
	 */
	protected $custom_settings;
	/**
	 * @var array srCertificatePlaceholderValue[]
	 */
	protected $placeholder_values;
	/**
	 * Set to true if type changed
	 *
	 * @var boolean
	 */
	protected $type_changed = false;


	public function __construct($id = 0) {
		parent::__construct($id);
	}


	public function create() {
		parent::create();
		$this->type = srCertificateType::find($this->getTypeId());
		$this->createSettings();
		$this->createPlaceholderValues();
	}


	/**
	 * Also update placeholder values and settings.
	 * If the certificate type did change, delete old settings/placeholder values and create new default ones from new type.
	 *
	 */
	public function update() {
		/** @var $setting srCertificateDefinitionSetting */
		/** @var $pl srCertificatePlaceholderValue */
		if ($this->type_changed) {
			$this->signature_id = 0; // Reset signature
		}
		parent::update();
		// If the type did change, we destroy all settings + placeholder values from the old type and create new ones
		if ($this->type_changed) {
			foreach ($this->getSettings() as $setting) {
				$setting->delete();
			}
			foreach ($this->getCustomSettings() as $custom_setting) {
				$custom_setting->delete();
			}
			foreach ($this->getPlaceholderValues() as $pl) {
				$pl->delete();
			}
			$this->createSettings();
			$this->createPlaceholderValues();
		} else {
			foreach ($this->getSettings() as $setting) {
				$setting->update();
			}
			foreach ($this->getCustomSettings() as $setting) {
				$setting->update();
			}
			foreach ($this->getPlaceholderValues() as $pl) {
				$pl->update();
			}
		}
	}


	/**
	 * @param $string
	 */
	protected function log($string) {
		global $DIC;
		//$DIC["ilLog"]->write('srCertificateDefinition(' . $this->getId() . '): ' . $string);
	}


	/**
	 * Clone/copy this definition for a new course
	 *
	 * @param int $ref_id ref-ID of new course
	 *
	 * @return srCertificateDefinition
	 */
	public function copy($ref_id = 0) {
		$this->log('Certificate: copy definitions from ' . $this->getRefId() . ' to ' . $ref_id);
		$new_definition = srCertificateDefinition::where(array( "ref_id" => $ref_id ))->first();
		if (!$new_definition) {
			$this->log('there is no existing definition for ' . $ref_id . ', generating a new one.');
			$new_definition = new srCertificateDefinition();
			$new_definition->setRefId($ref_id);
			$new_definition->setTypeId($this->getTypeId());
			$new_definition->create();
		} else {
			$this->log('used existing definition for ' . $ref_id . '.');
		}
		$this->log('ID of clone: ' . $new_definition->getId());

		$new_definition->setRefId($ref_id);
		$new_definition->setTypeId($this->getTypeId());
		// Clone Signature setting
		if ($this->getSignatureId()) {
			$new_definition->setSignatureId($this->getSignatureId());
		}
		$new_definition->setTypeChanged(false);
		$new_definition->update();

		// Settings and placeholder values now contain default values inherited from type.
		// We overwrite them with the values from this definition

		/** @var $setting srCertificateDefinitionSetting */
		$this->log('copy srCertificateDefinitionSetting');
		foreach ($this->getSettings() as $setting) {
			$s = $new_definition->getSettingByIdentifier($setting->getIdentifier());
			$this->log($setting->getIdentifier());
			if (!$s instanceof srCertificateDefinitionSetting) {
				$this->log('not found, generating new one');
				$s = new srCertificateDefinitionSetting();
				$s->setDefinitionId($new_definition->getId());
				$s->setIdentifier($setting->getIdentifier());
				$s->create();
			}
			$s->setValue($setting->getValue());
			$s->update();
		}

		/** @var $ph_value srCertificatePlaceholderValue */
		$this->log('copy srCertificatePlaceholderValue');
		foreach ($this->getPlaceholderValues() as $ph_value) {
			$ph = $new_definition->getPlaceholderValueByPlaceholderId($ph_value->getPlaceholderId());
			$this->log($ph_value->getPlaceholderId());
			if (!$ph instanceof srCertificatePlaceholderValue) {
				$this->log('not found, generating new one');
				$ph = new srCertificatePlaceholderValue();
				$ph->setDefinitionId($new_definition->getId());
				$ph->setPlaceholderId($ph_value->getPlaceholderId());
				$ph->create();
			}
			$ph->setValue($ph_value->getValue()); // This does set the values for each language
			$ph->update();
		}

		/** @var $cust_setting srCertificateCustomDefinitionSetting */
		foreach ($this->getCustomSettings() as $cust_setting) {
			$cs = $new_definition->getCustomSettingByIdentifier($cust_setting->getIdentifier());
			$this->log($cust_setting->getIdentifier());
			if (!$cs instanceof srCertificateCustomDefinitionSetting) {
				$this->log('not found, generating new one');
				$cs = new srCertificateCustomDefinitionSetting();
				$cs->setDefinitionId($new_definition->getId());
				$cs->setIdentifier($cust_setting->getIdentifier());
				$cs->create();
			}
			$cs->setValue($cust_setting->getValue()); // This does set the values for each language
			$cs->update();
			$this->log('old value: ' . $cust_setting->getValue());
			$this->log('cloned value: ' . $cs->getValue());
		}
		$this->log('finished');

		return $new_definition;
	}


	/**
	 * Get a setting by identifier
	 *
	 * @param $identifier
	 *
	 * @return null|srCertificateDefinitionSetting
	 */
	public function getSettingByIdentifier($identifier) {
		/** @var $setting srCertificateDefinitionSetting */
		foreach ($this->getSettings() as $setting) {
			if ($setting->getIdentifier() == $identifier) {
				return $setting;
				break;
			}
		}

		return NULL;
	}


	/**
	 * Get a setting by identifier
	 *
	 * @param $identifier
	 *
	 * @return null|srCertificateCustomDefinitionSetting
	 */
	public function getCustomSettingByIdentifier($identifier) {
		/** @var $setting srCertificateCustomDefinitionSetting */
		foreach ($this->getCustomSettings() as $setting) {
			if ($setting->getIdentifier() == $identifier) {
				return $setting;
				break;
			}
		}

		return NULL;
	}


	/**
	 * Get a placeholder value object by ID
	 *
	 * @param $id
	 *
	 * @return null|srCertificatePlaceholderValue
	 */
	public function getPlaceholderValueByPlaceholderId($id) {
		/** @var $ph_value srCertificatePlaceholderValue */
		foreach ($this->getPlaceholderValues() as $ph_value) {
			if ($ph_value->getPlaceholderId() == $id) {
				return $ph_value;
				break;
			}
		}

		return NULL;
	}


	/**
	 * @return srCertificateSignature|null
	 */
	public function getSignature() {
		return srCertificateSignature::find($this->signature_id);
	}


	/**
	 * @return int
	 */
	public function getSignatureId() {
		return $this->signature_id;
	}


	/**
	 * @param $id
	 */
	public function setSignatureId($id) {
		$this->signature_id = $id;
	}


	// Shortcut-Getters implemented for the settings


	/**
	 * @return mixed
	 */
	public function getValidityType() {
		return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getValue();
	}


	/**
	 * @return mixed
	 */
	public function getValidity() {
		return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY)->getValue();
	}


	/**
	 * @return mixed
	 */
	public function getNotification() {
		return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION)->getValue();
	}


	/**
	 * @return mixed
	 */
	public function getDefaultLanguage() {
		return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DEFAULT_LANG)->getValue();
	}


	/**
	 * @return mixed
	 */
	public function getGeneration() {
		return $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_GENERATION)->getValue();
	}


	/**
	 * @return mixed|null
	 */
	public function getDownloadable() {
		$setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_DOWNLOADABLE);

		return (is_null($setting)) ? NULL : $setting->getValue();
	}


	/**
	 * @return mixed|null
	 */
	public function getNotificationUser() {
		$setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);

		return (is_null($setting)) ? NULL : $setting->getValue();
	}


	/**
	 * @return mixed|null
	 */
	public function getScormTiming() {
		$setting = $this->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_SCORM_TIMING);

		return (is_null($setting)) ? NULL : $setting->getValue();
	}


	// Static


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return self::TABLE_NAME;
	}


	// Protected


	/**
	 * Create the settings inheriting default values defined in the type
	 *
	 */
	protected function createSettings() {
		$type_settings = $this->type->getSettings();
		/** @var srCertificateTypeSetting $type_setting */
		foreach ($type_settings as $type_setting) {
			$setting = srCertificateDefinitionSetting::where(array(
				'definition_id' => $this->getId(),
				'identifier' => $type_setting->getIdentifier(),
			))->first();
			if (!$setting) {
				$setting = new srCertificateDefinitionSetting();
				$setting->create();
			}
			$setting->setIdentifier($type_setting->getIdentifier());
			$setting->setDefinitionId($this->getId());
			$setting->setValue($type_setting->getValue());
			$setting->update();
			$this->settings[] = $setting;
		}
		foreach ($this->type->getCustomSettings() as $custom_setting) {
			$setting = srCertificateCustomDefinitionSetting::where(array(
				'definition_id' => $this->getId(),
				'identifier' => $type_setting->getIdentifier(),
			))->first();
			if (!$setting) {
				$setting = new srCertificateCustomDefinitionSetting();
				$setting->create();
			}
			$setting->setDefinitionId($this->getId());
			$setting->setValue($custom_setting->getValue());
			$setting->setIdentifier($custom_setting->getIdentifier());
			$setting->update();
			$this->custom_settings[] = $setting;
		}
	}


	/**
	 * Create the values for the placeholders defined in the type
	 * Each placeholder value inherits the default value defined in the type, per language
	 *
	 */
	protected function createPlaceholderValues() {
		$placeholders = $this->type->getPlaceholders();
		/** @var $placeholder srCertificatePlaceholder */
		foreach ($placeholders as $placeholder) {
			$placeholder_value = new srCertificatePlaceholderValue();
			$placeholder_value->setPlaceholderId($placeholder->getId());
			$placeholder_value->setDefinitionId($this->getId());
			$placeholder_value->setValue($placeholder->getDefaultValues());
			$placeholder_value->create();
			$this->placeholder_values[] = $placeholder_value;
		}
	}

	// Getter & Setter


	/**
	 * @param int $ref_id
	 */
	public function setRefId($ref_id) {
		$this->ref_id = $ref_id;
	}


	/**
	 * @return int
	 */
	public function getRefId() {
		return $this->ref_id;
	}


	/**
	 * @param int $type_id
	 */
	public function setTypeId($type_id) {
		if ($type_id != $this->getTypeId()) {
			$this->type_changed = true;
		}
		$this->type_id = $type_id;
		$this->type = srCertificateType::find($type_id);
	}


	/**
	 * @return int
	 */
	public function getTypeId() {
		return $this->type_id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param array $settings
	 */
	public function setSettings($settings) {
		$this->settings = $settings;
	}


	/**
	 * @return array
	 */
	public function getSettings() {
		if (is_null($this->settings)) {
			$this->settings = srCertificateDefinitionSetting::where(array( 'definition_id' => $this->getId() ))->get();
		}

		return $this->settings;
	}


	/**
	 * @return array srCertificateCustomDefinitionSetting[]
	 */
	public function getCustomSettings() {
		if (is_null($this->custom_settings)) {
			$this->custom_settings = srCertificateCustomDefinitionSetting::where(array( 'definition_id' => $this->getId() ))->get();
		}

		return $this->custom_settings;
	}


	/**
	 * @return \srCertificateType
	 */
	public function getType() {
		return srCertificateType::find($this->getTypeId());
	}


	/**
	 * @param array $placeholder_values
	 */
	public function setPlaceholderValues($placeholder_values) {
		$this->placeholder_values = $placeholder_values;
	}


	/**
	 * @return array
	 */
	public function getPlaceholderValues() {
		if (is_null($this->placeholder_values)) {
			$this->placeholder_values = srCertificatePlaceholderValue::where(array( 'definition_id' => $this->getId() ))->orderBy('placeholder_id')
				->get();
		}

		return $this->placeholder_values;
	}


	/**
	 * @param boolean $type_changed
	 */
	public function setTypeChanged($type_changed) {
		$this->type_changed = $type_changed;
	}


	/**
	 * @return boolean
	 */
	public function getTypeChanged() {
		return $this->type_changed;
	}
}