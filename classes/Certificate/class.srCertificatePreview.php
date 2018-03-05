<?php
require_once('class.srCertificate.php');

/**
 * Class srCertificatePreview
 *
 * This class represents a temporary certificate and is not stored in the database. It serves to
 * create a preview file of a certificate that can be downloaded.
 *
 * How to create and download a preview certificate of a given definition:
 *
 *      $preview = new srCertificatePreview();
 *      $preview->setDefinitionId($a_definition_id);
 *      $preview->generate();
 *      $preview->download();
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificatePreview extends srCertificate {

	/**
	 * Filename of preview certificate
	 *
	 */
	const PREVIEW_FILENAME = 'cert_preview.pdf';
	/**
	 * @var int
	 */
	protected $definition_id = 0;
	/**
	 * Temporary directory where the preview certificate pdf is stored
	 *
	 * @var string
	 */
	protected $temp_dir = '';


	public function __construct($id = 0) {
		global $DIC;
		parent::__construct(0);
		$this->setUserId($DIC->user()->getId());
		$this->filename = self::PREVIEW_FILENAME;
	}


	public function update() {
		throw new srCertificateException("Can't update CertificatePreview object because it exists only temporary");
	}


	public function create() {
		throw new srCertificateException("Can't create CertificatePreview object because it exists only temporary");
	}


	public function delete() {
		throw new srCertificateException("Can't delete CertificatePreview object because it exists only temporary");
	}


	/**
	 * Generate the preview certificate
	 *
	 * @param bool $force
	 *
	 * @throws srCertificateException
	 * @return bool|void
	 */
	public function generate($force = false) {
		if (!$this->getDefinitionId()) {
			throw new srCertificateException("srCertificatePreview needs definition id before generating preview file");
		}
		$cert_type = $this->definition->getType();
		$template_type = srCertificateTemplateTypeFactory::getById($cert_type->getTemplateTypeId());

		return $template_type->generate($this);
	}


	/**
	 * Load anonymized placholders
	 *
	 * @param bool $anonymized
	 */
	protected function loadPlaceholders($anonymized = false) {
		parent::loadPlaceholders(true);
	}


	/**
	 * Download and remove file afterwards
	 *
	 * @param bool $exit_after
	 */
	public function download($exit_after = true) {
		ilUtil::deliverFile($this->getFilePath(), self::PREVIEW_FILENAME, '', '', true, $exit_after);
	}


	/**
	 * Remove temp directory
	 */
	public function __destruct() {
		ilUtil::delDir($this->temp_dir);
	}


	/**
	 * Create a temporary path to store the preview
	 *
	 * @return string
	 */
	public function getCertificatePath() {
		if (!$this->temp_dir) {
			$tmpdir = ilUtil::ilTempnam();
			ilUtil::makeDir($tmpdir);
			$this->temp_dir = $tmpdir;
		}

		return $this->temp_dir;
	}


	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->getCertificatePath() . DIRECTORY_SEPARATOR . self::PREVIEW_FILENAME;
	}


	/**
	 * @return string
	 */
	public function getTempDir() {
		return $this->temp_dir;
	}
}