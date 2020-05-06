<?php

/**
 * Class srCertificateCronjob
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertificateCronjob
{

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
     * @var ilLogger
     */
    protected $log;

    /**
     * srCertificateCronjob constructor.
     */
    public function __construct()
    {
        global $DIC;
        $this->db = $DIC->database();
        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->log = $DIC->logger()->root();
        $this->ilias = $DIC["ilias"];
        $this->pl = ilCertificatePlugin::getInstance();
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        $this->generateCertificates();
        $this->subscribeToSuccessorCourses();
    }

    /**
     * @throws Exception
     */
    protected function generateCertificates()
    {
        /** @var srCertificate $cert */
        $certs = srCertificate::where(array('status' => srCertificate::STATUS_NEW))->get();
        foreach ($certs as $cert) {
            // Force a reload of the members. If there are parallel cronjobs, only continue if status is still NEW
            $cert->read();
            if ($cert->getStatus() != srCertificate::STATUS_NEW) {
                continue;
            }
            try {
                $cert->generate();
            } catch (Exception $e) {
                $this->log->log("Failed to generate certificate with ID {$cert->getId()}, message: " . $e->getMessage(),
                    ilLogLevel::ERROR);
                throw $e;
            }
        }

        // Also check for certificates with status DRAFT. They should be changed to NEW if the course is passed and the last access is more than xx minutes
        $certs = srCertificate::where(array('status' => srCertificate::STATUS_DRAFT))->get();
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
    protected function subscribeToSuccessorCourses()
    {
        // fetch certificates which expired today (valid_to -> yesterday)
        $freshly_expired_certs = srCertificate::where(array(
                'active' => 1,
                'usage_type' => srCertificate::USAGE_TYPE_STANDARD,
                'valid_to' => date('Y-m-d', strtotime('yesterday'))
            )
        )->get();
        /** @var srCertificate $cert */
        foreach ($freshly_expired_certs as $cert) {
            $successor_crs_ref_id = $cert->getDefinition()->getSuccessorCourseRefId();
            if ($successor_crs_ref_id && !ilObjCourse::_exists($successor_crs_ref_id, true)) {
                $this->log->log(
                    "couldn't find successor course with ref_id=" . $successor_crs_ref_id .
                    ' for certificate definition of course with ref_id=' . $cert->getDefinition()->getRefId()
                );
                continue;
            } elseif (!$successor_crs_ref_id) {
                $this->log->log(
                    'no successor course defined for certificate definition of course with ref_id=' . $cert->getDefinition()->getRefId()
                );
                continue;
            }
            // subscribe
            $new_course = new ilObjCourse($successor_crs_ref_id);
            $new_course->getMembersObject()->add($cert->getUserId(), IL_CRS_MEMBER);
        }
    }

    /**
     * Get timestamp of the last_status according to LP
     * @param srCertificate $cert
     * @return int|null
     */
    protected function getLastLPStatus(srCertificate $cert)
    {
        $ref_id = $cert->getDefinition()->getRefId();
        $obj_id = ilObject::_lookupObjectId($ref_id);
        $lp_data = ilTrQuery::getObjectsDataForUser($cert->getUserId(), $obj_id, $ref_id, '', '', 0, 9999, null,
            array('last_access'));
        $last_status = null;
        foreach ($lp_data['set'] as $data) {
            if ($data['type'] == 'crs') {
                $last_status = $data['last_access'];
                break;
            }
        }

        return (int) $last_status;
    }

}