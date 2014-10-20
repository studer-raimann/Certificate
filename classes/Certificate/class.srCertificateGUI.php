<?php

require_once('class.srCertificate.php');
require_once('class.srCertificateTableGUI.php');

/**
 * Class srCertificateGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateGUI
{

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilTemplate
     */
    protected $tpl;

    public function __construct()
    {
        global $ilCtrl, $tpl;

        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->checkPermission();
    }

    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('index');
        switch ($cmd) {
            case 'index':
                $this->index();
                break;
            case 'applyFilter':
                $this->applyFilter();
                break;
            case 'resetFilter':
                $this->resetFilter();
                break;
            case 'downloadCertificate':
                $this->downloadCertificate();
                break;
            case 'downloadCertificates':
                $this->downloadCertificates();
                break;
        }
    }

    public function index()
    {
        $table = new srCertificateTableGUI($this, 'index');
        $this->tpl->setContent($table->getHTML());
    }

    public function applyFilter()
    {
        $table = new srCertificateTableGUI($this, 'index');
        $table->writeFilterToSession();
        $table->resetOffset();
        $this->index();
    }

    public function resetFilter()
    {
        $table = new srCertificateTableGUI($this, 'index');
        $table->resetOffset();
        $table->resetFilter();
        $this->index();
    }


    /**
     * Download a certificate
     *
     */
    public function downloadCertificate()
    {
        if ($cert_id = (int) $_GET['cert_id']) {
            /** @var srCertificate $cert */
            $cert = srCertificate::find($cert_id);
            $cert->download();
        }
        $this->index();
    }


    /**
     * Download multiple certificates as ZIP file
     *
     */
    public function downloadCertificates()
    {
        $cert_ids = (isset($_POST['cert_id'])) ? (array) $_POST['cert_id'] : array();
        srCertificate::downloadAsZip($cert_ids);
        $this->index();
    }


    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        // TODO How are the permissions checked for this GUI?
    }

}