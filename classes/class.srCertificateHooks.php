<?php

/**
 * Class srCertificateHooks
 *
 * This class offers methods that are called by the plugin in different situations.
 * Each installation can implement a subclass and overwrite the methods to process custom logic.
 * The location of this subclass can be defined in the plugin config, typically this class is stored
 * in the Customizing folder of the installation.
 *
 * If the plugin detects a valid subclass, the methods are executed on an instance of the subclass.
 * Otherwise, hooks are executed on an object of this class.
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateHooks {

	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;


	public function __construct(ilCertificatePlugin $plugin) {
		$this->pl = $plugin;
	}


	/**
	 * Process any custom logic to a placeholder
	 *
	 * Example: Add string in front of ID for certificates from type 3
	 *          if ($cert->getDefinition()->getType()->getId() == 3) {
	 *              $placeholders['CERT_ID'] = 'myString' . $cert->getId();
	 *          }
	 *          return $placeholders;
	 *
	 * @param array $placeholders
	 * @param srCertificate $cert
	 * @return array
	 */
	public function processPlaceholders(srCertificate $cert, array $placeholders) {
		return $placeholders;
	}


	/**
	 * Return a custom date format string for an date/time identifier
	 *
	 * Example: Change format of the DATE_COMPLETED placeholder for every certificate
	 *          if ($identifier == 'DATE_COMPLETED') {
	 *              return 'Y.m.d.customized';
	 *          }
	 *          return null
	 *
	 * @param \srCertificate $cert
	 * @param $identifier
	 * @internal param $value
	 * @return string
	 */
	public function formatDate(srCertificate $cert, $identifier) {
		return null;
	}
}