<?php

use srag\CustomInputGUIs\Certificate\PropertyFormGUI\PropertyFormGUI;

/**
 * Class srCertParticipationCertificateFormGUI
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertParticipationCertificateFormGUI extends PropertyFormGUI {

	const PLUGIN_CLASS_NAME = ilCertificatePlugin::class;

	const PROPERTY_TITLE = 'setTitle';

	/**
	 * @var srCertParticipationCertificate
	 */
	protected $srCertParticipationCertificate;

	/**
	 * srCertParticipationCertificateFormGUI constructor.
	 * @param $parent
	 * @param $srCertificateDefinition srCertificateDefinition
	 * @throws \srag\DIC\Certificate\Exception\DICException
	 */
	public function __construct($parent, $srCertificateDefinition) {
		$this->srCertParticipationCertificate = srCertParticipationCertificate::findOrGetInstance($srCertificateDefinition->getId());
		parent::__construct($parent);
		self::dic()->mainTemplate()->addJavaScript(self::plugin()->directory() . '/templates/js/participation_certificate_form.js');
	}


	/**
	 * @param string $key
	 * @return mixed|void
	 */
	protected function getValue($key) {

	}

	/**
	 *
	 */
	protected function initCommands() {
		$this->addCommandButton(srCertificateDefinitionGUI::CMD_UPDATE_PARTICIPATION_CERTIFICATE, self::dic()->language()->txt('update'));
	}

	/**
	 * @throws \srag\DIC\Certificate\Exception\DICException
	 */
	protected function initFields() {
		$this->fields = [
			srCertParticipationCertificate::F_TYPE => [
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_TITLE => self::plugin()->translate('cert_type'),
				self::PROPERTY_OPTIONS => $this->getTypeInputOptions(),
				self::PROPERTY_VALUE => $this->srCertParticipationCertificate->getTypeId(),
			],
			srCertParticipationCertificate::F_CONDITION_OBJECT_TYPE => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_VALUE => $this->srCertParticipationCertificate->getConditionObjectType(),
				self::PROPERTY_SUBITEMS => [
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_ANY => [
						self::PROPERTY_CLASS => ilRadioOption::class,
					],
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_SPECIFIC_OBJECT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE_REF_ID => [
								self::PROPERTY_CLASS => ilNumberInputGUI::class,
								self::PROPERTY_VALUE => $this->srCertParticipationCertificate->getConditionObjectValueRefId(),
							]
						]
					],
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_OBJECT_TYPE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE_TYPE => [
								self::PROPERTY_CLASS => ilTextInputGUI::class,
								self::PROPERTY_VALUE => $this->srCertParticipationCertificate->getConditionObjectValueType(),
							]
						]
					]
				]
			],
			srCertParticipationCertificate::F_CONDITION_STATUS => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_VALUE => $this->srCertParticipationCertificate->getConditionStatusType(),
				self::PROPERTY_SUBITEMS => [
					srCertParticipationCertificate::CONDITION_STATUS_TYPE_COMPLETED => [
						self::PROPERTY_CLASS => ilRadioOption::class
					],
					srCertParticipationCertificate::CONDITION_STATUS_TYPE_IN_PROGRESS => [
						self::PROPERTY_CLASS => ilRadioOption::class
					]
				]
			]
		];
	}

	/**
	 *
	 */
	protected function initId() {
	}

	/**
	 * @throws \srag\DIC\Certificate\Exception\DICException
	 */
	protected function initTitle() {
		$this->setTitle(self::plugin()->translate('participation_certificate'));
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	protected function storeValue($key, $value) {
		switch ($key) {
			case srCertParticipationCertificate::F_TYPE:
				$this->srCertParticipationCertificate->setTypeId($value);
				break;
			case srCertParticipationCertificate::F_CONDITION_STATUS:
				$this->srCertParticipationCertificate->setConditionStatusType($value);
				break;
			case srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE_REF_ID:
				$this->srCertParticipationCertificate->setConditionObjectValueRefId($value);
				break;
			case srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE_TYPE:
				$this->srCertParticipationCertificate->setConditionObjectValueType($value);
				break;
			case srCertParticipationCertificate::F_CONDITION_OBJECT_TYPE:
				$this->srCertParticipationCertificate->setConditionObjectType($value);
				break;
		}
	}

	/**
	 * @return bool
	 */
	public function storeForm() {
		if (parent::storeForm() === false) {
			return false;
		}
		$this->srCertParticipationCertificate->store();
		return true;
	}


	/**
	 * @return array
	 */
	protected function getTypeInputOptions() {
		$types = srCertificateType::get();
		$options = array();
		$invalid = array();
		/** @var $type srCertificateType */
		foreach ($types as $type) {
			if (!srCertificateType::isSelectable($type, (int)$_GET['ref_id'])) {
				continue;
			}
			// Skip the type if it contains no valid template file!
			if (!is_file($type->getCertificateTemplatesPath(true))) {
				$invalid[] = $type->getTitle();
				continue;
			}
			$options[$type->getId()] = $type->getTitle();
		}
		if (count($invalid) && $this->isNew) {
			ilUtil::sendInfo(sprintf($this->pl->txt('msg_info_invalid_cert_types'), implode(', ', $invalid)));
		}
		asort($options);
		array_unshift($options, self::dic()->language()->txt('inactive'));
		return $options;
	}
}