<?php
$cron = new srCertificateCron($_SERVER['argv']);
try {
    $cron->run();
} catch (Exception $e) {
}
$cron->logout();    // this is necessary if the prevention of simulataneous logins is active

/**
 * srCertificateCreatePdfCron
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * Use the following command for the cronjob:
 * /usr/bin/php /[ILIAS-Absolute-Path]/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.srCertificateCron.php [adminuser] [adminpwd] [client_id]
 */
class srCertificateCron
{

    const DEBUG = false;

    /**
     * srCertificateCron constructor.
     * @param $data
     * @throws arException
     */
    function __construct($data)
    {
        global $DIC;
        $_COOKIE['ilClientId'] = $data[3];
        $_POST['username'] = $data[1];
        $_POST['password'] = $data[2];
        $this->initILIAS();

        if (self::DEBUG) {
            $DIC["ilLog"]->write('Auth passed for async Certificate');
        }

        require_once __DIR__ . '/../vendor/autoload.php';
    }

    public function run()
    {
        if (!defined("ILIAS_HTTP_PATH")) {
            define("ILIAS_HTTP_PATH", ilUtil::_getHttpPath());
        }
        $srCertificateCronjob = new srCertificateCronjob();
        $srCertificateCronjob->run();
    }

    public function initILIAS()
    {
        chdir(substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/Customizing')));
        require_once 'include/inc.ilias_version.php';
        require_once 'Services/Component/classes/class.ilComponent.php';

        require_once './Services/Cron/classes/class.ilCronStartUp.php';
        $ilCronStartup = new ilCronStartUp($_SERVER['argv'][3], $_SERVER['argv'][1], $_SERVER['argv'][2]);
        $ilCronStartup->initIlias();
        $ilCronStartup->authenticate();

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

    /**
     *
     */
    public function logout()
    {
        global $DIC;
        $ilAuth = $DIC["ilAuthSession"];
        $ilAuth->logout();
    }
}

class ilSessionMock
{

    public function get($what, $default)
    {
        return $default;
    }
}
