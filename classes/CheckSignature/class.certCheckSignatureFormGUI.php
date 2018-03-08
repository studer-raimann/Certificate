<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');

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
	/**
	 * @var ilLanguage
	 */
	protected $lng;


	function __construct() {
		parent::__construct();
		global $DIC;
		$this->ctrl = $DIC->ctrl();
		$this->pl = ilCertificatePlugin::getInstance();

		$this->lng = $DIC->language();
		$this->initForm();
	}


	protected function initForm() {
		$this->setFormAction($this->ctrl->getFormActionByClass(certCheckSignatureGUI::class, certCheckSignatureGUI::CMD_DECRYPT_SIGNATURE));
		$te = new ilFormSectionHeaderGUI();
		$te->setTitle($this->pl->txt('signature_check'));
		$this->addItem($te);

		// signature
		$signature = new ilTextInputGUI($this->pl->txt('signature'), 'signature');
		$this->addItem($signature);

		$this->addCommandButton(certCheckSignatureGUI::CMD_DECRYPT_SIGNATURE, $this->lng->txt('send'));
	}
}

?>