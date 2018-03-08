<?php

require_once('class.srCertificateGUI.php');

/**
 * Class srCertificateAdministrationGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy srCertificateAdministrationGUI : ilRouterGUI, ilUIPluginRouterGUI
 */
class srCertificateAdministrationGUI extends srCertificateGUI {

	const CMD_CALL_BACK = 'callBack';
	const CMD_RETRY_GENERATION = 'retryGeneration';
	const CMD_UNDO_CALL_BACK = 'undoCallBack';


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->tpl->setTitle($this->pl->txt('administrate_certificates'));
	}


	/**
	 * Check permissions
	 */
	protected function checkPermission() {
		$allowed_roles = ilCertificateConfig::getX('roles_administrate_certificates');

		return $this->rbac->isAssignedToAtLeastOneGivenRole($this->user->getId(), json_decode($allowed_roles, true));
	}


	protected function getTable($cmd) {
		$options = array( 'newest_version_only' => false );
		if (in_array($cmd, array( self::CMD_RESET_FILTER, self::CMD_APPLY_FILTER ))) {
			$options = array_merge($options, array( 'build_data' => false ));
		}

		return new srCertificateTableGUI($this, $cmd, $options);
	}


	protected function performCommand($cmd) {
		/** @var srCertificate $certificate */
		$certificate = srCertificate::find((int)$_GET['cert_id']);
		switch ($cmd) {
			case self::CMD_CALL_BACK:
				$this->callBack($certificate);
				break;
			case self::CMD_UNDO_CALL_BACK:
				$this->undoCallBack($certificate);
				break;
			case self::CMD_RETRY_GENERATION:
				$this->retryGeneration($certificate);
				break;
		}
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function callBack(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_CALLED_BACK);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_called_back'), true);
		$this->ctrl->redirect($this, self::CMD_INDEX);
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function undoCallBack(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_PROCESSED);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_undo_called_back'), true);
		$this->ctrl->redirect($this, self::CMD_INDEX);
	}


	/**
	 * @param srCertificate $certificate
	 */
	protected function retryGeneration(srCertificate $certificate) {
		$certificate->setStatus(srCertificate::STATUS_NEW);
		$certificate->update();
		ilUtil::sendSuccess($this->pl->txt('msg_retry_generation'), true);
		$this->ctrl->redirect($this, self::CMD_INDEX);
	}


	/**
	 * Build action menu for a record asynchronous
	 *
	 */
	protected function buildActions() {
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId((int)$_GET['cert_id']);
		$alist->setListTitle($this->pl->txt('actions'));
		$this->ctrl->setParameter($this, 'cert_id', (int)$_GET['cert_id']);

		switch ($_GET['status']) {
			case srCertificate::STATUS_CALLED_BACK:
				$alist->addItem($this->pl->txt('undo_callback'), 'undoCallback', $this->ctrl->getLinkTarget($this, self::CMD_UNDO_CALL_BACK));
				break;
			case srCertificate::STATUS_FAILED:
				$alist->addItem($this->pl->txt('retry'), 'retry', $this->ctrl->getLinkTarget($this, self::CMD_RETRY_GENERATION));
				break;
			case srCertificate::STATUS_PROCESSED:
				$alist->addItem($this->pl->txt('download'), 'download', $this->ctrl->getLinkTarget($this, self::CMD_DOWNLOAD_CERTIFICATE));
				$alist->addItem($this->pl->txt('call_back'), 'call_back', $this->ctrl->getLinkTarget($this, self::CMD_CALL_BACK));
				break;
		}

		echo $alist->getHTML(true);
		exit;
	}
}