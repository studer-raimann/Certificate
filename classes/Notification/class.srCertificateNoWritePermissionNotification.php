<?php

/**
 * Class srCertificateNoWritePermissionNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateNoWritePermissionNotification extends srCertificateEmailNotification
{

    /**
     * @param srCertificate $certificate
     * @param string $email
     */
    public function __construct(srCertificate $certificate, $email = '')
    {
        parent::__construct($certificate, $email);
        $this->setEmail(ilSetting::_lookupValue('common', 'admin_email'));
        $this->setSubject($this->pl->txt('writeperm_failed_subject'));
        $parser = srCertificatePlaceholdersParser::getInstance();
        $body = $parser->parse($this->pl->txt('writeperm_failed_message'), $this->certificate->getPlaceholders());
        $this->setBody($body);
    }

}