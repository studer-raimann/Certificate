<?php

require_once('class.srCertificateGUI.php');
require_once('class.srCertificateUserTableGUI.php');

/**
 * Class srCertificateAdministrationGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateUserGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateUserGUI extends srCertificateGUI {

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->tpl->setTitle($this->pl->txt('my_certificates'));
    }

    /**
     *  Download certificate
     */
    public function downloadCertificate()
    {
        if ($cert_id = (int) $_GET['cert_id']) {
            /** @var srCertificate $cert */
            $cert = srCertificate::find($cert_id);
            if ($cert->getUserId() == $this->user->getId() && $cert->getDefinition()->getDownloadable()) {
                $cert->download();
            }
        }
        $this->index();
    }


    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        return true;
    }

    /**
     * Build action menu for a record asynchronous
     *
     */

    protected function buildActions()
    {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($_GET['id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameter($this, 'cert_id', $_GET['id']);
        $alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, 'downloadCertificate'));
        echo $alist->getHTML(true);exit;
    }

    /**
     * @param $cmd
     * @return srCertificateTableGUI
     */
    protected function getTable($cmd)
    {
        $options = array();
        if (in_array($cmd, array('resetFilter', 'applyFilter'))) {
            $options['build_data'] = false;
        }

        return new srCertificateUserTableGUI($this, $cmd, $this->user, $options);
    }
}