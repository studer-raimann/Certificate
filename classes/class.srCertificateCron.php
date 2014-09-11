<?php

$cron = new srCertificateCron($_SERVER['argv']);
$cron->run();

/**
 * srCertificateCreatePdfCron
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 *
 * Use the following command for the cronjob:
 * /usr/bin/php /[ILIAS-Absolute-Path]/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.srCertificateCron.php [adminuser] [adminpwd] [client_id]
 * /Applications/MAMP/bin/php/php5.4.10/bin/php /Applications/MAMP/htdocs/sat_43/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.srCertificateCron.php [adminuser] [adminpwd] [client_id]
 * @version
 */
class srCertificateCron
{

    const DEBUG = false;

    const MAX_DIFF_LP_SECONDS = 28800;

    /**
     * @var Ilias
     */
    protected $ilias;

    /**
     * @param array $data
     */
    function __construct($data)
    {
        $_COOKIE['ilClientId'] = $data[3];
        $_POST['username'] = $data[1];
        $_POST['password'] = $data[2];
        $this->initILIAS();

        global $ilDB, $ilUser, $ilCtrl, $ilLog, $ilias;
        if (self::DEBUG) {
            $ilLog->write('Auth passed for async Certificate');
        }
        /**
         * @var $ilDB   ilDB
         * @var $ilUser ilObjUser
         * @var $ilCtrl ilCtrl
         */
        $this->db = $ilDB;
        $this->user = $ilUser;
        $this->ctrl = $ilCtrl;
        $this->ilias = $ilias;
    }


    public function initILIAS()
    {
        chdir(substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/Customizing')));
        require_once('include/inc.ilias_version.php');
        require_once('Services/Component/classes/class.ilComponent.php');
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999')) {
            require_once './Services/Context/classes/class.ilContext.php';
            ilContext::init(ilContext::CONTEXT_WEB);
            require_once './Services/Init/classes/class.ilInitialisation.php';
            ilInitialisation::initILIAS();
        } else {
            $_GET['baseClass'] = 'ilStartUpGUI';
            require_once('./include/inc.get_pear.php');
            require_once('./include/inc.header.php');
        }
        require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
        require_once('./Services/Mail/classes/class.ilMimeMail.php');
        require_once("./Services/Tracking/classes/class.ilTrQuery.php");
        require_once("./Services/Tracking/classes/class.ilLPStatusFactory.php");
    }


    public function run()
    {
        /** @var srCertificate $cert */
        $certs = srCertificate::where(array('status' => srCertificate::STATUS_NEW))->get();
        foreach ($certs as $cert) {
            // Force a reload of the members. If there are parallel cronjobs, only continue if status is still NEW
            $cert->read();
            if ($cert->getStatus() != srCertificate::STATUS_NEW) {
                continue;
            }
            if ($cert->generate()) {
                // Check for notifications
                if ($notification = $cert->getDefinition()->getNotification()) {
                    $receivers = explode(',', $notification);
                    $this->sendNotification($cert, $receivers);
                }
                // Check for user notification
                if ($cert->getDefinition()->getNotificationUser()) {
                    $this->sendNotificationUser($cert);
                }
            }
        }

        // Also check for certificates with status DRAFT. They should be changed to NEW if the course is passed and the last access is more than xx minutes
        $certs = srCertificate::where(array('status' => srCertificate::STATUS_DRAFT))->get();
        foreach ($certs as $cert) {
            $cert->read();
            if ($cert->getStatus() != srCertificate::STATUS_DRAFT) {
                continue;
            }
            if ($last_access = $this->getLastLPStatus($cert)) {
                $diff = time() - $last_access;
                if ($diff > self::MAX_DIFF_LP_SECONDS) {
                    $cert->setStatus(srCertificate::STATUS_NEW);
                    $cert->update();
                }
            }
        }
    }


    /**
     * Get timestamp of the last_status according to LP
     *
     * @param srCertificate $cert
     * @return int|null
     */
    protected function getLastLPStatus(srCertificate $cert)
    {
        $ref_id = $cert->getDefinition()->getRefId();
        $obj_id = ilObject::_lookupObjectId($ref_id);
        $lp_data = ilTrQuery::getObjectsDataForUser($cert->getUserId(), $obj_id, $ref_id, '', '', 0, 9999, null, array('last_access'));
        $last_status = null;
        foreach ($lp_data['set'] as $data) {
            if ($data['type'] == 'crs') {
                $last_status = $data['last_access'];
                break;
            }
        }
        return (int)$last_status;
    }


    /**
     * Send a notification
     *
     * @param srCertificate $cert
     * @param array $receivers
     */
    protected function sendNotification(srCertificate $cert, array $receivers)
    {
        $fullname = $cert->getUser()->getFullname();
        $ref_id = $cert->getDefinition()->getRefId();
        $obj_title = ilObject::_lookupTitle(ilObject::_lookupObjectId($ref_id));

        $subject = "New certificate generated for user $fullname";
        $body = "Hi,\n\n";
        $body .= "A new certificate was generated:\n\n";
        $body .= "User: $fullname\n";
        $body .= "Course: $obj_title\n";
        $body .= "Valid until: " . date('d.m.Y', strtotime($cert->getValidTo())) . "\n";
//        $body .= "Filename: " . $cert->getFilename(true) . "\n\n";
        $body .= "The certificate is attached in this email";

        $mail = new ilMimeMail();
        $mail->To($receivers);
        $from = $this->ilias->getSetting('mail_external_sender_noreply');
        if ($from) {
            $mail->From($from);
        }
        $mail->Subject($subject);
        $mail->Body($body);
        $mail->Attach($cert->getFilePath());
        $mail->Send();
    }

    /**
     * Send notification to the user
     *
     * @param srCertificate $cert
     */
    protected function sendNotificationUser(srCertificate $cert)
    {
        $user = $cert->getUser();
        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $fullname = $user->getFullname();
        $ref_id = $cert->getDefinition()->getRefId();
        $obj_title = ilObject::_lookupTitle(ilObject::_lookupObjectId($ref_id));

        $subject = "New certificate generated for course {$obj_title}";
        $body = "Hi,\n\n";
        $body .= "A new certificate was generated for you:\n\n";
        $body .= "User: $fullname\n";
        $body .= "Course: $obj_title\n";
        $body .= "Valid until: " . date('d.m.Y', strtotime($cert->getValidTo())) . "\n";
        $body .= "The certificate is attached in this email";

        $mail = new ilMimeMail();
        $mail->To($user->getEmail());
        $from = $this->ilias->getSetting('mail_external_sender_noreply');
        if ($from) {
            $mail->From($from);
        }
        $mail->Subject($subject);
        $mail->Body($body);
        $mail->Attach($cert->getFilePath());
        $mail->Send();
    }

}

?>
