<?php

namespace srag\Plugins\Certificate\Cron;

use ilCertificatePlugin;
use ilCronJob;
use ilCronJobResult;
use srag\DIC\Certificate\DICTrait;
use srCertificateCronjob;

/**
 * Class CertificateJob
 *
 * @package srag\Plugins\Certificate\Cron
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class CertificateJob extends ilCronJob
{

    use DICTrait;

    const CRON_JOB_ID = ilCertificatePlugin::PLUGIN_ID;
    const PLUGIN_CLASS_NAME = ilCertificatePlugin::class;


    /**
     * CertificateJob constructor
     */
    public function __construct()
    {

    }


    /**
     * @inheritDoc
     */
    public function getId() : string
    {
        return self::CRON_JOB_ID;
    }


    /**
     * @inheritDoc
     */
    public function hasAutoActivation() : bool
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    public function hasFlexibleSchedule() : bool
    {
        return true;
    }


    /**
     * @inheritDoc
     */
    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_IN_MINUTES;
    }


    /**
     * @inheritDoc
     */
    public function getDefaultScheduleValue()/* : ?int*/
    {
        return 1;
    }


    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return ilCertificatePlugin::PLUGIN_NAME . ": " . self::plugin()->translate("cron_title");
    }


    /**
     * @inheritDoc
     */
    public function getDescription() : string
    {
        return self::plugin()->translate("cron_description");
    }


    /**
     * @inheritDoc
     */
    public function run() : ilCronJobResult
    {
        $result = new ilCronJobResult();

        $srCertificateCronjob = new srCertificateCronjob();
        $srCertificateCronjob->run();

        $result->setStatus(ilCronJobResult::STATUS_OK);

        return $result;
    }
}
