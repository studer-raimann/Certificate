<?php

require_once('class.srCertificateGUI.php');

/**
 * Class srCertificateAdministrationGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateAdministrationGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateAdministrationGUI extends srCertificateGUI {

    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        // TODO This permission is checked based on role configuration in plugin
        return true;
    }

}