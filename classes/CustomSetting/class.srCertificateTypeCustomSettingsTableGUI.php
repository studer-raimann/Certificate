<?php
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once(dirname(dirname(__FILE__)) . '/Type/class.srCertificateType.php');

/**
 * Table class srCertificateTypeCustomSettingsTableGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 **/
class srCertificateTypeCustomSettingsTableGUI extends ilTable2GUI {

	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var srCertificateType
	 */
	protected $type;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var array
	 */
	protected $columns = array(
		'identifier',
		'editable_in',
		'custom_setting_type',
		'default_value',
	);


	/**
	 * @param                   $a_parent_obj
	 * @param string            $a_parent_cmd
	 * @param srCertificateType $type
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, srCertificateType $type) {
		global $DIC;
		$this->type = $type;
		$this->setPrefix('cert_type_custom_settings');
		$this->setId($type->getId());
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->pl = ilCertificatePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->setRowTemplate('tpl.type_custom_settings_row.html', $this->pl->getDirectory());
		$this->initColumns();
		$this->addColumn($this->pl->txt('actions'));
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->setTitle($this->pl->txt('custom_settings'));
		$this->buildData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('IDENTIFIER', $a_set['identifier']);
		$this->tpl->setVariable('EDITABLE_IN', $a_set['editable_in']);
		$this->tpl->setVariable('SETTING_TYPE', $a_set['setting_type']);
		$this->tpl->setVariable('DEFAULT_VALUE', $a_set['default_value']);
		$this->tpl->setVariable('ACTIONS', $this->buildActionMenu($a_set)->getHTML());
	}


	/**
	 * Build action menu
	 *
	 * @param array $a_set
	 *
	 * @return ilAdvancedSelectionListGUI
	 */
	protected function buildActionMenu(array $a_set) {
		$list = new ilAdvancedSelectionListGUI();
		$list->setId($a_set['id']);
		$list->setListTitle($this->pl->txt('actions'));
		$this->ctrl->setParameterByClass(srCertificateTypeGUI::class, 'type_id', $this->type->getId());
		$this->ctrl->setParameterByClass(srCertificateTypeGUI::class, 'custom_setting_id', $a_set['id']);
		$list->addItem($this->lng->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass(srCertificateTypeGUI::class, srCertificateTypeGUI::CMD_EDIT_CUSTOM_SETTING));
		$list->addItem($this->lng->txt('delete'), 'delete', $this->ctrl->getLinkTargetByClass(srCertificateTypeGUI::class, srCertificateTypeGUI::CMD_CONFIRM_DELETE_CUSTOM_SETTING));
		$this->ctrl->clearParametersByClass(srCertificateTypeGUI::class);

		return $list;
	}


	/**
	 * Add columns
	 */
	protected function initColumns() {
		foreach ($this->columns as $column) {
			$this->addColumn($this->pl->txt($column), $column);
		}
	}


	/**
	 * Get settings
	 */
	protected function buildData() {
		$data = array();
		/** @var $setting srCertificateCustomTypeSetting */
		foreach ($this->type->getCustomSettings() as $setting) {
			$row = array();
			$row['id'] = $setting->getId();
			$row['identifier'] = $setting->getIdentifier();
			$row['editable_in'] = implode(',', $setting->getEditableIn());
			$row['setting_type'] = $this->pl->txt('custom_setting_type_' . $setting->getSettingTypeId());
			$row['default_value'] = $setting->getValue();
			$data[] = $row;
		}
		$this->setData($data);
	}
}
