<?php
require_once(dirname(dirname(__FILE__)) . '/class.ilCertificatePlugin.php');

/**
 * srCertificateTemplateType
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
abstract class srCertificateTemplateType
{

    const TEMPLATE_TYPE_JASPER = 1;
    const TEMPLATE_TYPE_HTML = 2;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $template_filename = '';

    /**
     * @var ilLog
     */
    protected $log;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    public function __construct()
    {
        global $ilLog;
        $this->log = $ilLog;
        $this->pl = new ilCertificatePlugin();
        // Concrete classes must set their properties here...
    }


    // Public

    /**
     * Generate the report for given certificate
     *
     * @param srCertificate $certificate
     * @return bool
     */
    abstract public function generate(srCertificate $certificate);


    /**
     * Return false if the template type is not available for rendering certificates
     *
     * @return bool
     */
    public function isAvailable()
    {
        return true;
    }

    // Getters & Setters

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $template_filename
     */
    public function setTemplateFilename($template_filename)
    {
        $this->template_filename = $template_filename;
    }

    /**
     * @return string
     */
    public function getTemplateFilename()
    {
        return $this->template_filename;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}

?>
