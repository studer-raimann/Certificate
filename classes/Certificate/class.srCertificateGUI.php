<?php
require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class srCertificateGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @author            Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
abstract class srCertificateGUI {

	const CMD_APPLY_FILTER = 'applyFilter';
	const CMD_BUILD_ACTIONS = 'buildActions';
	const CMD_DOWNLOAD_CERTIFICATE = 'downloadCertificate';
	const CMD_DOWNLOAD_CERTIFICATES = 'downloadCertificates';
	const CMD_INDEX = 'index';
	const CMD_RESET_FILTER = 'resetFilter';
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac;


	public function __construct() {
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->user = $DIC->user();
		$this->rbac = $DIC->rbac()->review();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->tpl->setTitleIcon(ilCertificatePlugin::getPluginIconImage());
		$DIC["ilMainMenu"]->setActive('none');
	}


	public function executeCommand() {
		if (!$this->checkPermission()) {
			ilUtil::sendFailure($this->pl->txt('msg_no_permission'), true);
			$this->ctrl->redirectByClass(ilPersonalDesktopGUI::class);
		}

		$this->tpl->getStandardTemplate();

		$cmd = $this->ctrl->getCmd(self::CMD_INDEX);
		switch ($cmd) {
			case self::CMD_INDEX:
				$this->index();
				break;
			case self::CMD_APPLY_FILTER:
				$this->applyFilter();
				break;
			case self::CMD_RESET_FILTER:
				$this->resetFilter();
				break;
			case self::CMD_DOWNLOAD_CERTIFICATE:
				$this->downloadCertificate();
				break;
			case self::CMD_DOWNLOAD_CERTIFICATES:
				$this->downloadCertificates();
				break;
			case self::CMD_BUILD_ACTIONS:
				$this->buildActions();
				break;
			default:
				$this->performCommand($cmd);
		}

		$this->tpl->show();
	}


	public function index() {
		$table = $this->getTable(self::CMD_INDEX);
		$this->tpl->setContent($table->getHTML());
	}


	public function applyFilter() {
		$table = $this->getTable(self::CMD_INDEX);
		$table->writeFilterToSession();
		$table->resetOffset();
		$this->index();
	}


	public function resetFilter() {
		$table = $this->getTable(self::CMD_INDEX);
		$table->resetOffset();
		$table->resetFilter();
		$this->index();
	}


	/**
	 * Download a certificate
	 *
	 */
	public function downloadCertificate() {
		if ($cert_id = (int)$_GET['cert_id']) {
			/** @var srCertificate $cert */
			$cert = srCertificate::find($cert_id);
			if ($cert->getStatus() == srCertificate::STATUS_CALLED_BACK) {
				ilUtil::sendFailure($this->pl->txt('msg_called_back'));
			} elseif ($cert->getStatus() != srCertificate::STATUS_PROCESSED) {
				ilUtil::sendFailure($this->pl->txt('msg_not_created_yet'));
			} else {
				$cert->download();
			}
		}
		$this->index();
	}


	/**
	 * Download multiple certificates as ZIP file
	 *
	 */
	public function downloadCertificates() {
		$cert_ids = (isset($_POST['cert_id'])) ? (array)$_POST['cert_id'] : array();
		srCertificate::downloadAsZip($cert_ids);
		$this->index();
	}


	/**
	 * build actions menu for a record asynchronous
	 */
	abstract protected function buildActions();


	/**
	 * Check permissions
	 */
	abstract protected function checkPermission();


	/**
	 * @param $cmd
	 *
	 * @return srCertificateTableGUI
	 */
	protected function getTable($cmd) {
		$options = (in_array($cmd, array( self::CMD_RESET_FILTER, self::CMD_APPLY_FILTER ))) ? array( 'build_data' => false ) : array();

		return new srCertificateTableGUI($this, $cmd, $options);
	}


	/**
	 * Subclasses can perform any specific commands not covered in the base class here
	 *
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
	}
}