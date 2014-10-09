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
        $this->setId(self::TEMPLATE_TYPE_JASPER);
        $this->setTitle('Jasper Report');
        $this->setDescription('Templates with Jasper Reports, requires the Jasper Reports Library');
        $this->setTemplateFilename('template.jrxml');
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
        $path_tpl = $cert->getDefinition()->getType()->getCertificateTemplatesPath(true);
        $placeholders = $cert->getPlaceholders();
        $defined_placeholders = $this->parseDefinedPlaceholders($path_tpl);
        // Only send defined placeholders to jasper, otherwise the template file is not considered as valid
        $placeholders = array_intersect_key($placeholders, $defined_placeholders);
        // TODO Also send empty values for placeholders defined in jasper template but not in definition???
        $placeholders = $this->nl2br($placeholders);
        $report = new JasperReport($path_tpl, $cert->getFilename(false));
        $report->setDataSource(JasperReport::DATASOURCE_EMPTY);
        $report->setParameters($placeholders);
        if ($report_file = $report->generateOutput()) {
            // Move pdf to correct certificate location
            $cert_path = $cert->getCertificatePath();
            if (!file_exists($cert_path)) {
                ilUtil::makeDirParents($cert_path);
            }
            $from = $report_file . '.pdf';
            $to = $cert->getFilePath();
            return ilUtil::moveUploadedFile($from, '', $to, false, 'rename');
        } else {
            $this->log->write("srCertificateTemplyteTypeJasper::generate() Report file was not created by Jasper");
        }
        return false;
    }


    /**
     * Parse the placeholders defined in the jasper report template.
     *
     * @param $path_tpl
     * @return array
     */
    protected function parseDefinedPlaceholders($path_tpl)
    {
        $xml = new SimpleXMLElement(file_get_contents($path_tpl));
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
