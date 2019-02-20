<?php

/**
 * Form-Class certCheckSignatureFormGUI
 *
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 */
class certCheckSignatureFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;


	function __construct() {
		parent::__construct();
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->pl = ilCertificatePlugin::getInstance();

		$this->initForm();
	}


	protected function initForm() {
		$this->setFormAction($this->ctrl->getFormActionByClass(certCheckSignatureGUI::class, certCheckSignatureGUI::CMD_DECRYPT_SIGNATURE));

		$te = new ilFormSectionHeaderGUI();
		$te->setTitle($this->pl->txt('signature_check'));
		$this->addItem($te);

		// signature
		$signature = new ilTextInputGUI($this->pl->txt('signature'), 'signature');
		$signature->setMaxLength(1000);
		$this->addItem($signature);

		$this->addCommandButton(certCheckSignatureGUI::CMD_DECRYPT_SIGNATURE, $this->pl->txt('send'));
	}
}
