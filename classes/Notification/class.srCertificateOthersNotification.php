<?php

/**
 * Class srCertificateOthersNotification
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateOthersNotification extends srCertificateEmailNotification
{

    /**
     * @param srCertificate $certificate
     * @param string        $email
     */
    public function __construct(srCertificate $certificate, $email = '')
    {
        parent::__construct($certificate, $email);
        $parser = srCertificatePlaceholdersParser::getInstance();
        $subject = $parser->parse($this->pl->config('notification_others_subject'), $certificate->getPlaceholders());
        $body = $parser->parse($this->pl->config('notification_others_body'), $certificate->getPlaceholders());
        $this->setSubject($subject);
        $this->setBody($body);
        $this->setAttachCertificate(true);
    }
}