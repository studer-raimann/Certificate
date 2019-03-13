<?php

/**
 * Class ilCertificateConfigFormGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ilCertificateConfigFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilCertificateConfigGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilRbacReview
	 */
	protected $rbacreview;


	/**
	 * @param ilCertificateConfigGUI $parent_gui
	 */
	public function __construct(ilCertificateConfigGUI $parent_gui) {
		global $DIC;
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->ctrl = $DIC->ctrl();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->rbacreview = $DIC->rbac()->review();
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initForm();
	}


	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function txt($field) {
		return $this->pl->txt('admin_form_' . $field);
	}


	protected function initForm() {
		$this->setTitle($this->txt('title'));

		// Course templates
		$item = new ilCheckboxInputGUI($this->txt(ilCertificateConfig::COURSE_TEMPLATES), ilCertificateConfig::COURSE_TEMPLATES);
		$item->setInfo($this->txt(ilCertificateConfig::COURSE_TEMPLATES . '_info'));
		$subitem = new ilTextAreaInputGUI($this->txt(ilCertificateConfig::COURSE_TEMPLATES_REF_IDS), ilCertificateConfig::COURSE_TEMPLATES_REF_IDS);
		$subitem->setInfo($this->txt(ilCertificateConfig::COURSE_TEMPLATES_REF_IDS . '_info'));
		$item->addSubItem($subitem);
		$this->addItem($item);

		// UTC
		$item = new ilCheckboxInputGUI($this->txt(ilCertificateConfig::TIME_FORMAT_UTC), ilCertificateConfig::TIME_FORMAT_UTC);
		$item->setInfo($this->txt(ilCertificateConfig::TIME_FORMAT_UTC));
		$this->addItem($item);

		// Date format
		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::DATE_FORMAT), ilCertificateConfig::DATE_FORMAT);
		$item->setInfo($this->txt(ilCertificateConfig::DATE_FORMAT . '_info'));
		$item->setRequired(true);
		$this->addItem($item);

		// Datetime format
		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::DATETIME_FORMAT), ilCertificateConfig::DATETIME_FORMAT);
		$item->setInfo($this->txt(ilCertificateConfig::DATETIME_FORMAT . '_info'));
		$item->setRequired(true);
		$this->addItem($item);

		// Max diff LP seconds
		$item = new ilNumberInputGUI($this->txt(ilCertificateConfig::MAX_DIFF_LP_SECONDS), ilCertificateConfig::MAX_DIFF_LP_SECONDS);
		$item->setInfo($this->txt(ilCertificateConfig::MAX_DIFF_LP_SECONDS . '_info'));
		$this->addItem($item);

		// Hook class
		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::PATH_HOOK_CLASS), ilCertificateConfig::PATH_HOOK_CLASS);
		$item->setInfo(sprintf($this->txt(ilCertificateConfig::PATH_HOOK_CLASS . '_info'), ilCertificatePlugin::CLASS_NAME_HOOKS, ilCertificatePlugin::DEFAULT_PATH_HOOK_CLASS));
		$this->addItem($item);

		//Call Back email
		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::CALLBACK_EMAIL), ilCertificateConfig::CALLBACK_EMAIL);
		$item->setInfo($this->txt(ilCertificateConfig::CALLBACK_EMAIL . '_info'));
		$this->addItem($item);

		//disk space Warning
		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::DISK_SPACE_WARNING), ilCertificateConfig::DISK_SPACE_WARNING);
		$item->setInfo($this->txt(ilCertificateConfig::DISK_SPACE_WARNING . '_info'));
		$this->addItem($item);

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('permission_settings'));
		$this->addItem($section);

		/** @var ilRbacReview $rbacreview $roles */
		$roles = array();
		foreach ($this->rbacreview->getGlobalRoles() as $role_id) {
			$roles[$role_id] = ilObject::_lookupTitle($role_id);
		}

		// Administrate types
		$item = new ilMultiSelectInputGUI($this->txt(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATE_TYPES), ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATE_TYPES);
		$item->setOptions($roles);
		$item->setInfo($this->txt(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATE_TYPES . '_info'));
		$item->setWidth(272);
		$item->setHeight(165);
		$this->addItem($item);

		// Administrate certificates
		$item = new ilMultiSelectInputGUI($this->txt(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATES), ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATES);
		$item->setOptions($roles);
		$item->setInfo($this->txt(ilCertificateConfig::ROLES_ADMINISTRATE_CERTIFICATES . '_info'));
		$item->setWidth(272);
		$item->setHeight(165);
		$this->addItem($item);

		// Jasper Reports
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle('Jasper Reports');
		$this->addItem($section);

		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::JASPER_LOCALE), ilCertificateConfig::JASPER_LOCALE);
		$item->setInfo($this->txt(ilCertificateConfig::JASPER_LOCALE . '_info'));
		$this->addItem($item);

		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::JASPER_JAVA_PATH), ilCertificateConfig::JASPER_JAVA_PATH);
		$this->addItem($item);

		// Notification
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('notifications'));
		$this->addItem($section);

		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::NOTIFICATIONS_USER_SUBJECT), ilCertificateConfig::NOTIFICATIONS_USER_SUBJECT);
		$this->addItem($item);

		$item = new ilTextAreaInputGUI($this->txt(ilCertificateConfig::NOTIFICATIONS_USER_BODY), ilCertificateConfig::NOTIFICATIONS_USER_BODY);
		$item->setRows(10);
		$item->setCols(100);
		$this->addItem($item);

		$item = new ilTextInputGUI($this->txt(ilCertificateConfig::NOTIFICATIONS_OTHERS_SUBJECT), ilCertificateConfig::NOTIFICATIONS_OTHERS_SUBJECT);
		$this->addItem($item);

		$item = new ilTextAreaInputGUI($this->txt(ilCertificateConfig::NOTIFICATIONS_OTHERS_BODY), ilCertificateConfig::NOTIFICATIONS_OTHERS_BODY);
		$item->setRows(10);
		$item->setCols(100);
		$this->addItem($item);

		$this->addCommandButtons();
	}


	public function fillForm() {
		$array = array();
		foreach ($this->getItems() as $item) {
			$this->getValuesForItem($item, $array);
		}
		$this->setValuesByArray($array);
	}


	/**
	 * @param ilFormPropertyGUI $item
	 * @param                   $array
	 *
	 * @internal param $key
	 */
	private function getValuesForItem($item, &$array) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			$array[$key] = ilCertificateConfig::getX($key);
			if ($item instanceof ilMultiSelectInputGUI) {
				$array[$key] = json_decode($array[$key], true);
			}
			if (self::checkForSubItem($item)) {
				foreach ($item->getSubItems() as $subitem) {
					$this->getValuesForItem($subitem, $array);
				}
			}
		}
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->checkInput()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			$this->saveValueForItem($item);
		}

		return true;
	}


	/**
	 * @param  ilFormPropertyGUI $item
	 */
	private function saveValueForItem($item) {
		if (self::checkItem($item)) {
			$key = $item->getPostVar();
			if ($item instanceof ilMultiSelectInputGUI) {
				ilCertificateConfig::setX($key, json_encode($this->getInput($key)));
			} else {
				ilCertificateConfig::setX($key, $this->getInput($key));
			}

			if (self::checkForSubItem($item)) {
				foreach ($item->getSubItems() as $subitem) {
					$this->saveValueForItem($subitem);
				}
			}
		}
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkForSubItem($item) {
		return !$item instanceof ilFormSectionHeaderGUI AND !$item instanceof ilMultiSelectInputGUI;
	}


	/**
	 * @param $item
	 *
	 * @return bool
	 */
	public static function checkItem($item) {
		return !$item instanceof ilFormSectionHeaderGUI;
	}


	protected function addCommandButtons() {
		$this->addCommandButton(ilCertificateConfigGUI::CMD_SAVE, $this->pl->txt('save'));
		$this->addCommandButton(ilCertificateConfigGUI::CMD_CANCEL, $this->pl->txt('cancel'));
	}
}
