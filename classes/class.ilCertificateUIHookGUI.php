<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');

/**
 * User interface hook class for CourseCertificate-Plugin
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilCertificateUIHookGUI extends ilUIHookPluginGUI {

	/**
	 * @var ilCtrl
	 */
	public $ctrl;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;
	/**
	 * @var null
	 */
	protected static $ref_is_crs = null;


	public function __construct() {
		global $ilCtrl, $ilUser, $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->pl = ilCertificatePlugin::getInstance();
		$this->user = $ilUser;
		$this->access = $ilAccess;
	}


	/**
	 * @param $a_comp
	 * @param $a_part
	 * @param array $a_par
	 */
	public function modifyGUI($a_comp, $a_part, $a_par = array()) {
		/**
		 * @var $ilTabsGUI ilTabsGUI
		 */
		if ($a_part == 'tabs' && isset($_GET['ref_id']) && self::$ref_is_crs !== false) {
			// ATM only display certificate tab in courses
			if (self::$ref_is_crs === null && ilObject::_lookupType((int)$_GET['ref_id'], true) != 'crs' || $_GET['admin_mode']) {
				self::$ref_is_crs = false;

				return;
			}
			self::$ref_is_crs = true;
			// User needs write access to course to see the tab 'certificate'
			if ($this->access->checkAccess('write', '', (int)$_GET['ref_id'])) {
				$ilTabsGUI = $a_par['tabs'];
				$this->ctrl->setParameterByClass('srCertificateDefinitionGUI', 'ref_id', $_GET['ref_id']);
				$link = $this->ctrl->getLinkTargetByClass(array( ilCertificatePlugin::getBaseClass(), 'srCertificateDefinitionGUI' ));
				$ilTabsGUI->addTarget('certificate', $link, 'show', 'srCertificateDefinitionGUI');
			}
		}
	}
}
