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
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->tpl->setTitle($this->pl->txt('administrate_certificates'));
    }

    /**
     * Check permissions
     */
    protected function checkPermission()
    {
        $allowed_roles = ilCertificateConfig::get('roles_administrate_certificates');
        return $this->rbac->isAssignedToAtLeastOneGivenRole($this->user->getId(), json_decode($allowed_roles, true));
    }

    protected function getTable($cmd)
    {
        $options = array('newest_version_only' => false);
        if (in_array($cmd, array('resetFilter', 'applyFilter'))) {
            $options = array_merge($options, array('build_data' => false));
        }
        return new srCertificateTableGUI($this, $cmd, $options);
    }

    /**
     * Build action menu for a record asynchronous
     *
     */
    protected function buildActions() {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($_GET['id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameter($this, 'cert_id', $_GET['id']);

        switch($_GET['status'])
        {
            case srCertificate::STATUS_CALLED_BACK:
                $this->ctrl->setParameter($this, 'set_status', srCertificate::STATUS_PROCESSED);
                $alist->addItem($this->pl->txt('undo_callback'), 'undo_callback', $this->ctrl->getLinkTarget($this, 'setStatus'));
                break;
            case srCertificate::STATUS_FAILED:
                $this->ctrl->setParameter($this, 'set_status', srCertificate::STATUS_NEW);
                $alist->addItem($this->pl->txt('retry'), 'retry', $this->ctrl->getLinkTarget($this, 'setStatus'));
                break;
            case srCertificate::STATUS_PROCESSED:
                $alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, 'downloadCertificate'));
                $this->ctrl->setParameter($this, 'set_status', srCertificate::STATUS_CALLED_BACK);
                $alist->addItem($this->pl->txt('call_back'), 'call_back', $this->ctrl->getLinkTarget($this, 'setStatus'));
                break;
        }

        echo $alist->getHTML(true);exit;
    }


}