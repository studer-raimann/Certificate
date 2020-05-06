<?php

/**
 * Class srCertificateDiskSpaceWarningNotification
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateDiskSpaceWarningNotification extends srCertificateEmailNotification
{

    /**
     * @param srCertificate $certificate
     */
    public function __construct(srCertificate $certificate)
    {
        parent::__construct($certificate);
        $this->setEmail(ilSetting::_lookupValue('common', 'admin_email'));
        $this->setSubject($this->pl->txt('disk_space_warning_mail_subject'));
        $this->setBody(nl2br(sprintf($this->pl->txt('disk_space_warning_mail_message'),
            disk_free_space($this->certificate->getCertificatePath()))));
    }
}