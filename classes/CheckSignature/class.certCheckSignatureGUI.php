<?php
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * GUI-Class certCheckSignatureGUI
 *
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 * @ilCtrl_IsCalledBy certCheckSignatureGUI: ilRouterGUI
 */
class certCheckSignatureGUI {

	const CMD_DECRYPT_SIGNATURE = 'decryptSignature';
	const CMD_SHOW_FORM = 'showForm';
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
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
		global $DIC;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		switch ($cmd) {
			case self::CMD_SHOW_FORM:
			default:
				$this->showForm();
				break;
			case self::CMD_DECRYPT_SIGNATURE:
				$this->decryptSignature();
				break;
		}

		return true;
	}


	public function showForm() {

		$form = new certCheckSignatureFormGUI();
		$this->tpl->setContent($form->getHTML());
	}


	public function decryptSignature() {

		$form = new certCheckSignatureFormGUI();
		if (!$form->checkInput()) {
			ilUtil::sendFailure($this->pl->txt('decrypt_failed'), true);
		}
		$public_key = openssl_get_publickey('file://' . ilCertificateConfig::getX('signature_publickey'));
		openssl_public_decrypt(base64_decode($form->getInput('signature')), $decrypted, $public_key);

		if ($decrypted) {
			ilUtil::sendInfo($this->pl->txt('decrypt_successful') . '<br/>' . $decrypted, true);
		} else {
			ilUtil::sendFailure($this->pl->txt('decrypt_failed'), true);
		}
	}
}