<?php

/**
 * Table class srCertificateTypeSettingsTableGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 **/
class srCertificateTypeSettingsTableGUI extends ilTable2GUI {

	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var srCertificateType
	 */
	protected $type;
	/**
	 * @var array
	 */
	protected $columns = array(
		'setting',
		'editable_in',
		'default_value',
	);


	/**
	 * @param                   $a_parent_obj
	 * @param string            $a_parent_cmd
	 * @param srCertificateType $type
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, srCertificateType $type) {
		global $DIC;
		$this->setId('cert_type_table_settings');
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->type = $type;
		$this->pl = ilCertificatePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->toolbar = $DIC->toolbar();
		$this->setRowTemplate('tpl.type_settings_row.html', $this->pl->getDirectory());
		$this->initColumns();
		$this->addColumn($this->pl->txt('actions'));
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->setTitle($this->pl->txt('standard_settings'));
		$this->buildData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('SETTING', $a_set['setting']);
		$this->tpl->setVariable('EDITABLE_IN', $a_set['editable_in']);
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
		$list->setId($a_set['identifier']);
		$list->setListTitle($this->pl->txt('actions'));
		$this->ctrl->setParameterByClass(srCertificateTypeGUI::class, 'type_id', $this->type->getId());
		$this->ctrl->setParameterByClass(srCertificateTypeGUI::class, 'identifier', $a_set['identifier']);
		$list->addItem($this->lng->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass(srCertificateTypeGUI::class, srCertificateTypeGUI::CMD_EDIT_SETTING));
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
		/** @var $setting srCertificateTypeSetting */
		foreach ($this->type->getSettings() as $setting) {
			$row = array();
			$row['identifier'] = $setting->getIdentifier();
			$row['setting'] = $this->pl->txt("setting_id_" . $setting->getIdentifier());
			$row['editable_in'] = implode(',', $setting->getEditableIn());
			$default_value = $setting->getValue();
			switch ($setting->getIdentifier()) {
				case srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE:
					$default_value = $this->pl->txt("setting_validity_{$default_value}");
					break;
				case srCertificateTypeSetting::IDENTIFIER_VALIDITY:
					$validity_type = $this->type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_VALIDITY_TYPE)->getValue();
					if ($default_value && $validity_type == srCertificateTypeSetting::VALIDITY_TYPE_DATE_RANGE) {
						$date_data = json_decode($default_value);
						$default_value = sprintf($this->pl->txt('validity_date_range'), $date_data->m, $date_data->d);
					}
					break;
			}
			$row['default_value'] = $default_value;
			$data[] = $row;
		}
		$this->setData($data);
	}
}
