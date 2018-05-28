<?php

/**
 * Class srCertificateUserNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateUserNotification extends srCertificateEmailNotification {

	/**
	 * @param srCertificate $certificate
	 * @param string        $email
	 */
	public function __construct(srCertificate $certificate, $email = '') {
		parent::__construct($certificate, $email);
		$this->setEmail($certificate->getUser()->getEmail());
		$parser = srCertificatePlaceholdersParser::getInstance();
		$subject = $parser->parse($this->pl->config('notification_user_subject'), $certificate->getPlaceholders());
		$body = $parser->parse($this->pl->config('notification_user_body'), $certificate->getPlaceholders());
		$this->setSubject($subject);
		$this->setBody($body);
		$this->setAttachCertificate(true);
	}
}