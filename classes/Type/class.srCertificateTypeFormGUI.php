<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Form-Class srCertificateTypeFormGUI
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version $Id:
 */
class srCertificateTypeFormGUI extends ilPropertyFormGUI {

	/**
	 * Width (px) applied to the multiselect inputs
	 */
	const WIDTH_MULTISELECT_INPUT = 277;
	/**
	 * @var srCertificateType
	 */
	protected $type;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilRbacReview
	 */
	protected $rbac;
	/**
	 * @var bool
	 */
	protected $isNew = false;
	/**
	 * @var
	 */
	protected $parent_gui;


	/**
	 * @param $parent_gui
	 * @param $type
	 */
	function __construct($parent_gui, srCertificateType $type) {
		global $DIC;
		parent::__construct();
		$this->parent_gui = $parent_gui;
		$this->type = $type;
		$this->tpl = $DIC->ui()->mainTemplate();
		$this->ctrl = $DIC->ctrl();
		$this->rbac = $DIC->rbac()->review();
		$this->pl = ilCertificatePlugin::getInstance();
		$this->lng = $DIC->language();
		$this->isNew = ($this->type->getId()) ? false : true;
		$this->lng->loadLanguageModule('meta');
		$this->initForm();
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		if ($this->isNew) {
			$this->type->create();
		} else {
			$this->type->update();
		}

		return true;
	}


	/**
	 * @return bool
	 */
	protected function fillObject() {
		$this->setValuesByPost();
		if (!$this->checkInput()) {
			return false;
		}
		$this->type->setTitle($this->getInput('title'));
		$this->type->setDescription($this->getInput('description'));
		$this->type->setLanguages($this->getInput('languages'));
		$this->type->setRoles($this->getInput('roles'));
		$this->type->setAvailableObjects($this->getInput('available_objects'));

		return true;
	}


	/**
	 * Init form
	 */
	protected function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$title = ($this->isNew) ? $this->pl->txt('add_new_type') : $this->pl->txt('edit_type');
		$this->setTitle($title);

		$item = new ilTextInputGUI($this->lng->txt('title'), 'title');
		$item->setRequired(true);
		$item->setValue($this->type->getTitle());
		$this->addItem($item);

		$item = new ilTextAreaInputGUI($this->lng->txt('description'), 'description');
		$item->setValue($this->type->getDescription());
		$this->addItem($item);

		$item = new ilMultiSelectInputGUI($this->lng->txt('languages'), 'languages');
		$item->setWidth(self::WIDTH_MULTISELECT_INPUT);
		$langs = $this->lng->getInstalledLanguages();
		$options = array();
		foreach ($langs as $lang_code) {
			$options[$lang_code] = $this->lng->txt("meta_l_{$lang_code}");
		}
		$item->setOptions($options);
		$item->setValue($this->type->getLanguages());
		$item->setRequired(true);
		$this->addItem($item);

		$item = new ilMultiSelectInputGUI($this->lng->txt('roles'), 'roles');
		$item->setWidth(self::WIDTH_MULTISELECT_INPUT);
		$roles = $this->rbac->getRolesByFilter(ilRbacReview::FILTER_ALL, 0, '');
		$options = array();
		$hide_roles = array( 14, 5 );
		foreach ($roles as $role) {
			if (strpos($role['title'], 'il_') === 0 || in_array($role['obj_id'], $hide_roles)) {
				// Don't show auto-generated roles. If this takes to much performance, write query...
				continue;
			}
			$options[$role['obj_id']] = $role['title'];
		}
		$item->setOptions($options);
		$item->setValue($this->type->getRoles());
		$item->setInfo($this->pl->txt('roles_info'));
		$this->addItem($item);

		$item = new ilMultiSelectInputGUI($this->pl->txt('available_objects'), 'available_objects');
		$item->setWidth(self::WIDTH_MULTISELECT_INPUT);
		$options = array();
		foreach (srCertificateType::getAllAvailableObjectTypes() as $type) {
			$options[$type] = $type;
		}
		$item->setOptions($options);
		$item->setValue($this->type->getAvailableObjects());
		$item->setRequired(true);
		$item->setInfo($this->pl->txt('available_objects_info'));
		$this->addItem($item);

		$this->addCommandButton('saveType', $this->lng->txt('save'));
	}
}

?>