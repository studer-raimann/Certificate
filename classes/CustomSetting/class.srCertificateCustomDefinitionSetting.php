<?php

/**
 * srCertificateCustomTypeSetting
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateCustomDefinitionSetting extends srCertificateDefinitionSetting {

	/**
	 * MySQL Table-Name
	 */
	const TABLE_NAME = 'cert_def_setting_cus';
	// Public


	/**
	 * Check in the certificate type if this setting is editable in the current context (crs, tst...)
	 *
	 * @return bool
	 */
	public function isEditable() {
		/** @var srCertificateDefinition $definition */
		$definition = srCertificateDefinition::find($this->getDefinitionId());
		$type = $definition->getType();
		$setting = $type->getCustomSettingByIdentifier($this->getIdentifier());
		$ref_id = $definition->getRefId();
		$object_type = ($this->pl->isCourseTemplate($ref_id)) ? 'crs-tpl' : ilObject::_lookupType($ref_id, true);

		return in_array($object_type, $setting->getEditableIn());
	}


	/**
	 * Returns the default value defined in the type
	 *
	 * @return string
	 */
	public function getDefaultValue() {
		/** @var srCertificateDefinition $definition */
		$definition = srCertificateDefinition::find($this->getDefinitionId());
		$type = $definition->getType();
		$setting = $type->getCustomSettingByIdentifier($this->getIdentifier());

		return $setting->getValue();
	}


	/**
	 * @return int
	 */
	public function getSettingTypeId() {
		return $this->getCustomTypeSetting()->getSettingTypeId();
	}


	/**
	 * @param $lang
	 *
	 * @return string
	 */
	public function getLabel($lang) {
		return $this->getCustomTypeSetting()->getLabel($lang);
	}


	/**
	 * @return srCertificateCustomTypeSetting
	 */
	public function getCustomTypeSetting() {
		/** @var srCertificateDefinition $definition */
		$definition = srCertificateDefinition::find($this->getDefinitionId());

		return srCertificateCustomTypeSetting::where(array( 'identifier' => $this->getIdentifier(), 'type_id' => $definition->getTypeId() ))->first();
	}
}
