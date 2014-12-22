<?php

require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificate.php');
require_once('srCertificateNotification.php');

/**
 * Class srCertificateUserNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateEmailNotification implements srCertificateNotification
{

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var srCertificate
     */
    protected $certificate;

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var string
     */
    protected $subject = '';

    /**
     * @var ilMimeMail
     */
    protected $mailer;

    /**
     * @var bool
     */
    protected $attach_certificate = true;

    /**
     * @param string $email
     * @param srCertificate $certificate
     */
    function __construct($email, srCertificate $certificate)
    {
        $this->email = $email;
        $this->certificate = $certificate;
        $this->mailer = new ilMimeMail();
    }


    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }


    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }


    /**
     * @param $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }


    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }


    /**
     * @return boolean
     */
    public function isAttachCertificate()
    {
        return $this->attach_certificate;
    }


    /**
     * @param boolean $attach_certificate
     */
    public function setAttachCertificate($attach_certificate)
    {
        $this->attach_certificate = $attach_certificate;
    }


    /**
     * Execute notification
     *
     * @return mixed
     */
    public function notify()
    {
        $this->mailer->To($this->email);
        $from = $this->ilias->getSetting('mail_external_sender_noreply');
        if ($from) {
            $this->mailer->From($from);
        }
        $this->mailer->Subject($this->getSubject());
        $this->mailer->Body($this->getBody());
        if ($this->attach_certificate) {
            $this->mailer->Attach($this->certificate->getFilePath());
        }

        return $this->mailer->Send();
    }
}