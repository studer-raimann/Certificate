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

}