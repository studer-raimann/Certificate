<?php

/**
 * Interface srCertificateSetting
 */
interface srCertificateSettingInterface
{

    /**
     * @param $value
     */
    public function setValue($value);

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return mixed
     */
    public function getIdentifier();
}