<?php
require_once('class.srCertificateTemplateType.php');
require_once(dirname(dirname(__FILE__)) . '/Placeholder/class.srCertificatePlaceholdersParser.php');

/**
 * srCertificateTemplateTypeHtml
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateTemplateTypeHtml extends srCertificateTemplateType
{

    public function __construct()
    {
        $this->setId(self::TEMPLATE_TYPE_HTML);
        $this->setTitle('HTML');
        $this->setDescription('Templates with basic HTML, rendered with the ILIAS PDF engine (TCPDF)');
        $this->setTemplateFilename('template.html');
    }


    /**
     * @return bool
     */
    public function isAvailable()
    {
        // Only for ILIAS >= 4.4
        return (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.4.0'));
    }


    /**
     * Generate the report for given certificate
     *
     * @param srCertificate $cert
     * @throws ilException
     * @return bool
     */
    public function generate(srCertificate $cert)
    {
        if (!$this->isAvailable()) {
            throw new ilException("Generating certificates with TemplateTypeHtml is only available for ILIAS > 4.4");
        }

        require_once('./Services/PDFGeneration/classes/class.ilPDFGeneration.php');
        // Get HTML markup by parsing the template and replace placeholders
        $markup = file_get_contents($cert->getDefinition()->getType()->getCertificateTemplatesPath(true));
        $markup = srCertificatePlaceholdersParser::getInstance()->parse($markup, $cert->getPlaceholders());
        try {
            $job = new ilPDFGenerationJob();
            $job->setMarginLeft('20');
            $job->setMarginBottom('20');
            $job->setMarginRight('20');
            $job->setMarginTop('20');
            $job->setOutputMode('F'); // Save to disk
            $job->setFilename($cert->getFilePath());
            $job->addPage($markup);
            ilPDFGeneration::doJob($job);
            return true; // Method above gives no feedback so assume true -.-
        } catch (Exception $e) {
            $this->log->write("srCertificateTemplyteTypeHtml::generate() : " . $e->getMessage());
            return false;
        }
    }
}
