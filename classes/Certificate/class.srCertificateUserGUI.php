<?php

require_once('class.srCertificateGUI.php');

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
            if ($cert->getUserId() == $this->user->getId()) {
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
        $options = array(
            'user_id' => $this->user->getId(),
            'columns' => array('id', 'crs_title', 'valid_from', 'valid_to', 'file_version', 'cert_type'),
        );
        if (in_array($cmd, array('resetFilter', 'applyFilter'))) {
            $options['build_data'] = false;
        }

        return new srCertificateTableGUI($this, $cmd, $options);
    }
}