<?php

/**
 * Class srCertificateNoWritePermissionNotification
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateNoWritePermissionNotification extends srCertificateEmailNotification
{

    /**
     * @param srCertificate $certificate
     * @param string        $email
     */
    public function __construct(srCertificate $certificate, $email = '')
    {
        parent::__construct($certificate, $email);
        $this->setEmail(ilSetting::_lookupValue('common', 'admin_email'));
        $this->setSubject($this->pl->txt('writeperm_failed_subject'));
        $parser = srCertificatePlaceholdersParser::getInstance();
        $body = $parser->parse(nl2br($this->pl->txt('writeperm_failed_message')),
            array_merge($this->certificate->getPlaceholders(),
                ['[[TARGET_DIR]]' => $certificate->getCertificatePath()]));
        $this->setBody($body);
    }
}