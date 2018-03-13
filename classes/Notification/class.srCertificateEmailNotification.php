<?php

/**
 * Class srCertificateUserNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateEmailNotification implements srCertificateNotification {

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
	protected $attach_certificate = false;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilSetting
	 */
	protected $settings;


	/**
	 * @param srCertificate $certificate
	 * @param string        $email
	 */
	function __construct(srCertificate $certificate, $email = '') {
		global $DIC;
		$this->email = $email;
		$this->certificate = $certificate;
		$this->mailer = new ilMimeMail();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->settings = $DIC["ilSetting"];
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}


	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}


	/**
	 * @param string $body
	 */
	public function setBody($body) {
		$this->body = $body;
	}


	/**
	 * @return string
	 */
	public function getBody() {
		return $this->body;
	}


	/**
	 * @param $subject
	 */
	public function setSubject($subject) {
		$this->subject = $subject;
	}


	/**
	 * @return string
	 */
	public function getSubject() {
		return $this->subject;
	}


	/**
	 * @return boolean
	 */
	public function isAttachCertificate() {
		return $this->attach_certificate;
	}


	/**
	 * @param boolean $attach_certificate
	 */
	public function setAttachCertificate($attach_certificate) {
		$this->attach_certificate = $attach_certificate;
	}


	/**
	 * Execute notification
	 *
	 * @return bool
	 */
	public function notify() {
		global $DIC;

		if (!$this->email) {
			return false;
		}
		$this->mailer->To($this->email);
		$from = $this->settings->get('mail_external_sender_noreply');
		if ($from) {
			if (ILIAS_VERSION_NUMERIC >= "5.3") {
				/** @var ilMailMimeSenderFactory $senderFactory */
				$senderFactory = $DIC["mail.mime.sender.factory"];

				$this->mailer->From($senderFactory->userByEmailAddress($from));
			} else {
				$this->mailer->From($from);
			}
		} else {
			if (ILIAS_VERSION_NUMERIC >= "5.3") {
				/** @var ilMailMimeSenderFactory $senderFactory */
				$senderFactory = $DIC["mail.mime.sender.factory"];

				$this->mailer->From($senderFactory->system());
			}
		}
		$this->mailer->Subject($this->getSubject());
		$this->mailer->Body($this->getBody());
		if ($this->attach_certificate) {
			$this->mailer->Attach($this->certificate->getFilePath());
		}

		$this->mailer->Send();

		return true;
	}
}