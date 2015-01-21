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