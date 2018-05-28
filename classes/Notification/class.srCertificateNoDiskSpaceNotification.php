<?php

/**
 * Class srCertificateNoDiskSpaceNotification
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateNoDiskSpaceNotification extends srCertificateEmailNotification {

	/**
	 * @param srCertificate $certificate
	 */
	public function __construct(srCertificate $certificate) {
		parent::__construct($certificate);
		$this->setEmail(ilSetting::_lookupValue('common', 'admin_email'));
		$this->setSubject($this->pl->txt('no_space_left_subject'));
		$parser = srCertificatePlaceholdersParser::getInstance();
		$body = $parser->parse($this->pl->txt('no_space_left_message'), $this->certificate->getPlaceholders());
		$this->setBody($body);
	}
}