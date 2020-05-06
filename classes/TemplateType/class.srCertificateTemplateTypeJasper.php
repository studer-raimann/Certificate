<?php

use srag\JasperReport\Certificate\JasperReport;
use srag\JasperReport\Certificate\JasperReportException;

/**
 * srCertificateTemplateTypeJasper
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateTemplateTypeJasper extends srCertificateTemplateType
{

    /**
     * srCertificateTemplateTypeJasper constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId(self::TEMPLATE_TYPE_JASPER);
        $this->setTitle('Jasper Report');
        $this->setDescription('Templates with Jasper Reports, requires the Jasper Reports Library');
        $this->setTemplateFilename('template.jrxml');
        $this->setValidSuffixes(array('jrxml'));
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * Generate the report for given certificate
     * @param srCertificate $cert
     * @return bool
     * @throws ilException
     */
    public function generate(srCertificate $cert)
    {
        if (!$this->isAvailable()) {
            throw new ilException("Generating certificates with TemplateTypeJasper is only available if the JasperReport service is installed");
        }
        $template = $cert->getType()->getCertificateTemplatesPath(true);
        // A template is required, so quit early if it does not exist for some reason
        if (!is_file($template)) {
            throw new srCertificateException('No template file found for cert type with id=' . $cert->getDefinition()->getType()->getId());
        }

        $placeholders = $cert->getPlaceholders();
        $defined_placeholders = $this->parseDefinedPlaceholders($template); // can throw an xml exception

        // Only send defined placeholders to jasper, otherwise the template file is not considered as valid
        $placeholders = array_intersect_key($placeholders, $defined_placeholders);
        $placeholders = $this->nl2br($placeholders, false);
        $report = new JasperReport($template, $cert->getFilename(false));
        if ($locale = $this->pl->config('jasper_locale')) {
            $report->setLocale($this->pl->config('jasper_locale'));
        }
        if ($java = $this->pl->config('jasper_path_java')) {
            $report->setPathJava($java);
        }
        $report->setDataSource(JasperReport::DATASOURCE_EMPTY);
        $report->setParameters($placeholders);
        try {
            $report->generateOutput();
            $report_file = $report->getOutputFile();
            // Move pdf to correct certificate location
            $cert_path = $cert->getCertificatePath();
            if (!file_exists($cert_path)) {
                ilUtil::makeDirParents($cert_path);
            }
            $from = $report_file . '.pdf';
            $to = $cert->getFilePath();

            //return ilUtil::moveUploadedFile($from, '', $to, false, 'rename');
            if (!rename($from, $to)) {
                throw new srCertificateException("renaming certificate from '$from' to '$to' failed");
            }
        } catch (JasperReportException $e) {
            throw new srCertificateException("srCertificateTemplyteTypeJasper::generate() Report file of certificate with ID {$cert->getId()} was not created by Jasper: "
                . implode(', ', $e->getErrors()));
        }
    }

    /**
     * Parse the placeholders defined in the jasper report template.
     * @param $template
     * @return array
     */
    protected function parseDefinedPlaceholders($template)
    {
        $xml = new SimpleXMLElement(file_get_contents($template));
        $defined_params = array();
        foreach ($xml->parameter as $param) {
            foreach ($param->attributes() as $k => $v) {
                if ($k == 'name') {
                    $defined_params[(string) $v] = '';
                }
            }
        }

        return $defined_params;
    }

    /**
     * Convert newlines in values to br, so Japser Report can handle newlines with <br> tags
     * @param array $placeholders
     * @return array
     */
    protected function nl2br(array $placeholders = array())
    {
        foreach ($placeholders as $k => $v) {
            $placeholders[$k] = nl2br($v, false);
        }

        return $placeholders;
    }
}
