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
class srCertificateUserGUI extends srCertificateGUI
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->tpl->setTitle($this->pl->txt('my_certificates'));
        $this->updateStatusFromDraftToNew();
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
                parent::downloadCertificate();
            }
        }
    }


    /**
     * Update any certificates in the draft status to new, in order to process them via cronjob
     */
    protected function updateStatusFromDraftToNew()
    {
        $certificates = srCertificate::where(array(
            'user_id' => $this->user->getId(),
            'status' => srCertificate::STATUS_DRAFT)
        )->get();
        foreach ($certificates as $certificate) {
            /** @var srCertificate $certificate */
            $certificate->setStatus(srCertificate::STATUS_NEW);
            $certificate->save();
        }
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
        // Download is only possible if certificate is processed
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId((int) $_GET['cert_id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameter($this, 'cert_id', (int) $_GET['cert_id']);
        $alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, 'downloadCertificate'));
        echo $alist->getHTML(true);
        exit;
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