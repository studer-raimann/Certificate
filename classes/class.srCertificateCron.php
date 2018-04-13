<?php
$cron = new srCertificateCron($_SERVER['argv']);
$cron->run();
$cron->logout();    // this is necessary if the prevention of simulataneous logins is active

/**
 * srCertificateCreatePdfCron
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * Use the following command for the cronjob:
 * /usr/bin/php /[ILIAS-Absolute-Path]/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.srCertificateCron.php [adminuser] [adminpwd] [client_id]
 */
class srCertificateCron {

	const DEBUG = false;
	/**
	 * @var Ilias
	 */
	protected $ilias;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilDB
	 */
	protected $db;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param array $data
	 */
	function __construct($data) {
		global $DIC;
		$_COOKIE['ilClientId'] = $data[3];
		$_POST['username'] = $data[1];
		$_POST['password'] = $data[2];
		$this->initILIAS();

		if (self::DEBUG) {
			$DIC["ilLog"]->write('Auth passed for async Certificate');
		}
		$this->db = $DIC->database();
		$this->user = $DIC->user();
		$this->ctrl = $DIC->ctrl();
		$this->ilias = $DIC["ilias"];
		require_once __DIR__ . '/../vendor/autoload.php';
		$this->pl = ilCertificatePlugin::getInstance();
	}


	public function initILIAS() {
		chdir(substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/Customizing')));
		require_once 'include/inc.ilias_version.php';
		require_once 'Services/Component/classes/class.ilComponent.php';

		if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.1.999')) {
			require_once './Services/Cron/classes/class.ilCronStartUp.php';
			$ilCronStartup = new ilCronStartUp($_SERVER['argv'][3], $_SERVER['argv'][1], $_SERVER['argv'][2]);
			$ilCronStartup->initIlias();
			$ilCronStartup->authenticate();
		} else {
			require_once 'Services/Context/classes/class.ilContext.php';
			ilContext::init(ilContext::CONTEXT_CRON);
			require_once 'Services/Authentication/classes/class.ilAuthFactory.php';
			ilAuthFactory::setContext(ilAuthFactory::CONTEXT_CRON);
			require_once './include/inc.header.php';
		}

		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php';
		require_once './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php';
		require_once './Services/Tracking/classes/class.ilTrQuery.php';
		require_once './Services/Tracking/classes/class.ilLPStatusFactory.php';

		// fix for some stupid ilias init....
		global $DIC;
		if (!$DIC["ilSetting"]) {
			$DIC["ilSetting"] = new ilSessionMock();
		}
	}


	public function run() {
		/** @var srCertificate $cert */
		$certs = srCertificate::where(array( 'status' => srCertificate::STATUS_NEW ))->get();
		foreach ($certs as $cert) {
			// Force a reload of the members. If there are parallel cronjobs, only continue if status is still NEW
			$cert->read();
			if ($cert->getStatus() != srCertificate::STATUS_NEW) {
				continue;
			}
			$cert->generate();
		}

		// Also check for certificates with status DRAFT. They should be changed to NEW if the course is passed and the last access is more than xx minutes
		$certs = srCertificate::where(array( 'status' => srCertificate::STATUS_DRAFT ))->get();
		foreach ($certs as $cert) {
			$cert->read();
			if ($cert->getStatus() != srCertificate::STATUS_DRAFT) {
				continue;
			}
			$max_diff_lp_seconds = $this->pl->config('max_diff_lp_seconds');
			if ($max_diff_lp_seconds) {
				if ($last_access = $this->getLastLPStatus($cert)) {
					$diff = time() - $last_access;
					if ($diff > $max_diff_lp_seconds) {
						$cert->setStatus(srCertificate::STATUS_NEW);
						$cert->update();
					}
				}
			} else {
				// If the setting max_diff_lp_seconds is "0", the NEW status is set anyway
				$cert->setStatus(srCertificate::STATUS_NEW);
				$cert->update();
			}
		}
	}


	/**
	 *
	 */
	public function logout() {
		global $DIC;
		$ilAuth = $DIC["ilAuthSession"];
		$ilAuth->logout();
	}


	/**
	 * Get timestamp of the last_status according to LP
	 *
	 * @param srCertificate $cert
	 *
	 * @return int|null
	 */
	protected function getLastLPStatus(srCertificate $cert) {
		$ref_id = $cert->getDefinition()->getRefId();
		$obj_id = ilObject::_lookupObjectId($ref_id);
		$lp_data = ilTrQuery::getObjectsDataForUser($cert->getUserId(), $obj_id, $ref_id, '', '', 0, 9999, NULL, array( 'last_access' ));
		$last_status = NULL;
		foreach ($lp_data['set'] as $data) {
			if ($data['type'] == 'crs') {
				$last_status = $data['last_access'];
				break;
			}
		}

		return (int)$last_status;
	}
}

class ilSessionMock {

	public function get($what, $default) {
		return $default;
	}
}
