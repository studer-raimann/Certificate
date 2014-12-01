<?php

require_once('class.srCertificateGUI.php');

/**
 * Class srCertificateAdministrationGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateAdministrationGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateAdministrationGUI extends srCertificateGUI
{

    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        $allowed_roles = ilCertificateConfig::get('roles_administrate_certificates');
        return $this->rbac->isAssignedToAtLeastOneGivenRole($this->user->getId(), json_decode($allowed_roles, true));
    }

}