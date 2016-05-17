<?php

require_once(__DIR__ . '/class.srCertificateEmailNotification.php');
require_once(dirname(__DIR__) . '/Placeholder/class.srCertificatePlaceholdersParser.php');

/**
 * Class srCertificateCallBackNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateCallBackNotification extends srCertificateEmailNotification
{

    /**
     * @param srCertificate $certificate
     */
    public function __construct(srCertificate $certificate)
    {
        parent::__construct($certificate);
        $this->setEmail($this->pl->config('callback_email'));
        $this->setSubject($this->pl->txt('callback_email_subject'));
        $parser = srCertificatePlaceholdersParser::getInstance();
        $body = $parser->parse($this->pl->txt('callback_email_message'), $this->certificate->getPlaceholders());
        $this->setBody($body);
    }

}