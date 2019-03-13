<?php

/**
 * srCertificateTemplateTypeHtml
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateTemplateTypeHtml extends srCertificateTemplateType {

	public function __construct() {
		parent::__construct();

		$this->setId(self::TEMPLATE_TYPE_HTML);
		$this->setTitle('HTML');
		$this->setDescription('Templates with basic HTML, rendered with the ILIAS PDF engine (TCPDF)');
		$this->setTemplateFilename('template.html');
		$this->setValidSuffixes(array( 'html' ));
	}


	/**
	 * @return bool
	 */
	public function isAvailable() {
		return true;
	}


	/**
	 * Generate the report for given certificate
	 *
	 * @param srCertificate $cert
	 *
	 * @throws ilException
	 */
	public function generate(srCertificate $cert) {
		$template = $cert->getDefinition()->getType()->getCertificateTemplatesPath(true);
		// A template is required, so quit early if it does not exist for some reason
		if (!is_file($template)) {
            throw new srCertificateException('No template file found for cert type with id=' . $cert->getDefinition()->getType()->getId());
		}
		// Get HTML markup by parsing the template and replace placeholders
		$markup = file_get_contents($template);
		$markup = srCertificatePlaceholdersParser::getInstance()->parse($markup, $cert->getPlaceholders());
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetMargins('20', '20', '20');
        $pdf->SetAutoPageBreak('auto', '20');
//            $pdf->setImageScale($config['image_scale']);  // I'm not sure if this is needed or what value should be given to it

        $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->setSpacesRE('/[^\S\xa0]/'); // Fixing unicode/PCRE-mess #17547

        $page = ' '.$markup;
        $pdf->AddPage();
        $pdf->writeHTML($page, true, false, true, false, '');

        $cert_path = $cert->getCertificatePath();
        if (!file_exists($cert_path)) {
            ilUtil::makeDirParents($cert_path);
        }

        $result = $pdf->Output($cert->getFilePath(), 'F'); // (I - Inline, D - Download, F - File)
	}
}
