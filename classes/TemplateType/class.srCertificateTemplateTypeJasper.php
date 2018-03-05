<?php
require_once('class.srCertificateTemplateType.php');

/**
 * srCertificateTemplateTypeJasper
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateTemplateTypeJasper extends srCertificateTemplateType
{

    const JASPER_CLASS = './Customizing/global/plugins/Libraries/JasperReport/classes/class.JasperReport.php';

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
        return is_file(self::JASPER_CLASS);
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
            throw new ilException("Generating certificates with TemplateTypeJasper is only available if the JasperReport service is installed");
        }
        require_once(self::JASPER_CLASS);
        $template = $cert->getDefinition()->getType()->getCertificateTemplatesPath(true);
        // A template is required, so quit early if it does not exist for some reason
        if (!is_file($template)) {
            return false;
        }
        $placeholders = $cert->getPlaceholders();
        try {
            $defined_placeholders = $this->parseDefinedPlaceholders($template);
        } catch (Exception $e) {
            // XML is not valid
            return false;
        }
        // Only send defined placeholders to jasper, otherwise the template file is not considered as valid
        $placeholders = array_intersect_key($placeholders, $defined_placeholders);
        $placeholders = $this->nl2br($placeholders);
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
	        return rename($from, $to);
        } catch (JasperReportException $e) {
            $this->log->write("srCertificateTemplyteTypeJasper::generate() Report file of certificate with ID {$cert->getId()} was not created by Jasper: " . implode(', ', $e->getErrors()));
            return false;
        }
    }


    /**
     * Parse the placeholders defined in the jasper report template.
     *
     * @param $template
     * @return array
     */
    protected function parseDefinedPlaceholders($template)
    {
        $xml = new SimpleXMLElement(file_get_contents($template));
        $defined_params = array();
        foreach ($xml->parameter as $param) {
            foreach ($param->attributes() as $k => $v) {
                if ($k == 'name') $defined_params[(string)$v] = '';
            }
        }
        return $defined_params;
    }


    /**
     * Convert newlines in values to br, so Japser Report can handle newlines with <br> tags
     *
     * @param array $placeholders
     * @return array
     */
    protected function nl2br(array $placeholders = array())
    {
        foreach ($placeholders as $k => $v) {
            $placeholders[$k] = nl2br($v);
        }
        return $placeholders;
    }


}
