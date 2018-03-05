<?php

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('class.ilCertificateConfigFormGUI.php');
require_once('class.ilCertificateConfig.php');
require_once(dirname(__FILE__) . '/Placeholder/class.srCertificateStandardPlaceholders.php');

/**
 * Class ilCertificateConfigGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilCertificateConfigGUI extends ilPluginConfigGUI {

	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	public function __construct() {
		global $DIC;

		$this->pl = ilCertificatePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC->ui()->mainTemplate();
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		switch ($cmd) {
			case 'configure':
			case 'save':
				$this->$cmd();
				break;
		}
	}


	/**
	 * Configure screen
	 */
	public function configure() {
		$form = new ilCertificateConfigFormGUI($this);
		$form->fillForm();
		$ftpl = new ilTemplate('tpl.config_form.html', true, true, $this->pl->getDirectory());
		$ftpl->setVariable("FORM", $form->getHTML());
		$ftpl->setVariable("TXT_USE_PLACEHOLDERS", $this->pl->txt('txt_use_placeholders'));
		foreach (srCertificateStandardPlaceholders::getStandardPlaceholders() as $placeholder => $text) {
			$ftpl->setCurrentBlock("placeholder");
			$ftpl->setVariable("PLACEHOLDER", $placeholder);
			$ftpl->setVariable("TXT_PLACEHOLDER", $text);
			$ftpl->parseCurrentBlock();
		}
		$this->tpl->setContent($ftpl->get());
	}


	/**
	 * Save config
	 */
	public function save() {
		$form = new ilCertificateConfigFormGUI($this);
		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('msg_save_config'), true);
			$this->ctrl->redirect($this, 'configure');
		} else {
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHTML());
		}
	}
}
