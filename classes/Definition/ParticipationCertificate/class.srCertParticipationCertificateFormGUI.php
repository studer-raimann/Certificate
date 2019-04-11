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

	public function __construct($parent) {
		parent::__construct($parent);
		self::dic()->mainTemplate()->addJavaScript(self::plugin()->directory() . '/templates/js/participation_certificate_form.js');
	}


	protected function getValue($key) {
		// TODO: Implement getValue() method.
	}

	protected function initCommands() {
		$this->addCommandButton(srCertificateDefinitionGUI::CMD_UPDATE_PARTICIPATION_CERTIFICATE, self::dic()->language()->txt('update'));
	}

	protected function initFields() {
		$this->fields = [
			srCertParticipationCertificate::F_TYPE => [
				self::PROPERTY_CLASS => ilSelectInputGUI::class,
				self::PROPERTY_TITLE => self::plugin()->translate('cert_type'),
				self::PROPERTY_OPTIONS => $this->getTypeInputOptions(),
			],
			srCertParticipationCertificate::F_CONDITION_OBJECT_TYPE => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
				self::PROPERTY_SUBITEMS => [
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_ANY => [
						self::PROPERTY_CLASS => ilRadioOption::class,
					],
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_SPECIFIC_OBJECT => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE . '[' . srCertParticipationCertificate::CONDITION_OBJECT_TYPE_SPECIFIC_OBJECT . ']' =>
							[
								self::PROPERTY_CLASS => ilNumberInputGUI::class
							]
						]
					],
					srCertParticipationCertificate::CONDITION_OBJECT_TYPE_OBJECT_TYPE => [
						self::PROPERTY_CLASS => ilRadioOption::class,
						self::PROPERTY_SUBITEMS => [
							srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE . '[' . srCertParticipationCertificate::CONDITION_OBJECT_TYPE_OBJECT_TYPE . ']' => [
								self::PROPERTY_CLASS => ilTextInputGUI::class,
							]
						]
					]
				]
			],
			srCertParticipationCertificate::F_CONDITION_STATUS => [
				self::PROPERTY_CLASS => ilRadioGroupInputGUI::class,
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

	protected function initId() {
	}

	protected function initTitle() {
		$this->setTitle(self::plugin()->translate('participation_certificate'));
	}

	protected function storeValue($key, $value) {
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