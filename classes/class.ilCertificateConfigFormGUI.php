<?php

require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Form/classes/class.ilMultiSelectInputGUI.php');
require_once('class.ilCertificateConfig.php');

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
	 * @var ilLanguage
	 */
	protected $lng;


	/**
	 * @param ilCertificateConfigGUI $parent_gui
	 */
	public function __construct(ilCertificateConfigGUI $parent_gui) {
		global $ilCtrl, $lng;

		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->lng = $lng;
		$this->pl = ilCertificatePlugin::getInstance();
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
		global $rbacreview, $ilUser;

		$this->setTitle($this->txt('title'));

		// Course templates
		$item = new ilCheckboxInputGUI($this->txt('course_templates'), 'course_templates');
		$item->setInfo($this->txt('course_templates_info'));
		$subitem = new ilTextAreaInputGUI($this->txt('course_templates_ref_ids'), 'course_templates_ref_ids');
		$subitem->setInfo($this->txt('course_templates_ref_ids_info'));
		$item->addSubItem($subitem);
		$this->addItem($item);

		// UTC
		$item = new ilCheckboxInputGUI($this->txt('time_format_utc'), 'time_format_utc');
		$item->setInfo($this->txt('time_format_utc'));
		$this->addItem($item);

		// Date format
		$item = new ilTextInputGUI($this->txt('str_format_date'), 'str_format_date');
		$item->setInfo($this->txt('str_format_date_info'));
		$item->setRequired(true);
		$this->addItem($item);

		// Datetime format
		$item = new ilTextInputGUI($this->txt('str_format_datetime'), 'str_format_datetime');
		$item->setInfo($this->txt('str_format_datetime_info'));
		$item->setRequired(true);
		$this->addItem($item);

		// Max diff LP seconds
		$item = new ilNumberInputGUI($this->txt('max_diff_lp_seconds'), 'max_diff_lp_seconds');
		$item->setInfo($this->txt('max_diff_lp_seconds_info'));
		$this->addItem($item);

		// Hook class
		$item = new ilTextInputGUI($this->txt('path_hook_class'), 'path_hook_class');
		$item->setInfo($this->txt('path_hook_class_info'));
		$this->addItem($item);

		//Call Back email
		$item = new ilTextInputGUI($this->txt('callback_email'), 'callback_email');
		$item->setInfo($this->txt('callback_email_info'));
		$this->addItem($item);

		//disk space Warning
		$item = new ilTextInputGUI($this->txt('disk_space_warning'), 'disk_space_warning');
		$item->setInfo($this->txt('disk_space_warning_info'));
		$this->addItem($item);

		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->txt('permission_settings'));
		$this->addItem($section);

		/** @var ilRbacReview $rbacreview $roles */
		$roles = array();
		foreach ($rbacreview->getGlobalRoles() as $role_id) {
			$roles[$role_id] = ilObject::_lookupTitle($role_id);
		}

		// Administrate types
		$item = new ilMultiSelectInputGUI($this->txt('roles_administrate_certificate_types'), 'roles_administrate_certificate_types');
		$item->setOptions($roles);
		$item->setInfo($this->txt('roles_administrate_certificate_types_info'));
		$item->setWidth(272);
		$item->setHeight(165);
		$this->addItem($item);

		// Administrate certificates
		$item = new ilMultiSelectInputGUI($this->txt('roles_administrate_certificates'), 'roles_administrate_certificates');
		$item->setOptions($roles);
		$item->setInfo($this->txt('roles_administrate_certificates_info'));
		$item->setWidth(272);
		$item->setHeight(165);
		$this->addItem($item);

		// Jasper Reports
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle('Jasper Reports');
		$this->addItem($section);

		$item = new ilTextInputGUI($this->txt('jasper_locale'), 'jasper_locale');
		$item->setInfo($this->txt('jasper_locale_info'));
		$this->addItem($item);

		$item = new ilTextInputGUI($this->txt('jasper_path_java'), 'jasper_path_java');
		$this->addItem($item);

		// Notification
		$section = new ilFormSectionHeaderGUI();
		$section->setTitle($this->lng->txt('notifications'));
		$this->addItem($section);

		$item = new ilTextInputGUI($this->txt('notification_user_subject'), 'notification_user_subject');
		$this->addItem($item);

		$item = new ilTextAreaInputGUI($this->txt('notification_user_body'), 'notification_user_body');
		$item->setRows(10);
		$item->setCols(100);
		$this->addItem($item);

		$item = new ilTextInputGUI($this->txt('notification_others_subject'), 'notification_others_subject');
		$this->addItem($item);

		$item = new ilTextAreaInputGUI($this->txt('notification_others_body'), 'notification_others_body');
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
		$this->addCommandButton('save', $this->lng->txt('save'));
		$this->addCommandButton('cancel', $this->lng->txt('cancel'));
	}
}