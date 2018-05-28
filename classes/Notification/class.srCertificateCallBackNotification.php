<?php

/**
 * Class srCertificateCallBackNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateCallBackNotification extends srCertificateEmailNotification {

	/**
	 * @param srCertificate $certificate
	 * @param string        $email
	 */
	public function __construct(srCertificate $certificate, $email = '') {
		parent::__construct($certificate, $email);
		$this->setSubject($this->pl->txt('callback_email_subject'));
		$parser = srCertificatePlaceholdersParser::getInstance();
		$body = $parser->parse($this->pl->txt('callback_email_message'), $this->certificate->getPlaceholders());
		$this->setBody($body);
	}
}