<?php

/**
 * Interface srCertificateNotification
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
interface srCertificateNotification
{

    /**
     * Execute notification
     * @return mixed
     */
    public function notify();

    /**
     * @param string $subject
     */
    public function setSubject($subject);

    /**
     * @return mixed
     */
    public function getSubject();

    /**
     * @param string $body
     */
    public function setBody($body);

    /**
     * @return mixed
     */
    public function getBody();
}