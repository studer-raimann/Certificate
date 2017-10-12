<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class srCertificateParticipantsTableGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertificateParticipantsTableGUI extends ilTable2GUI {

	/**
	 * All available columns
	 *
	 * @var array
	 */
	protected static $columns = array(
		'firstname',
		'lastname',
		'passed_at',
	);
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilObjUser
	 */
	protected $user;
	/**
	 * @var srCertificateDefinition
	 */
	protected $definition;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;

	/**
	 * srCertificateParticipantsTableGUI constructor.
	 *
	 * @param        $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param srCertificateDefinition $definition
	 */
	public function __construct($a_parent_obj, $a_parent_cmd = "", $definition) {
		global $ilCtrl, $ilUser;
		$this->ctrl = $ilCtrl;
		$this->user = $ilUser;
		$this->definition = $definition;
		$this->pl = ilCertificatePlugin::getInstance();
		$this->setPrefix('cert_par_');
		$this->setId($definition->getId());
		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setRowTemplate('tpl.participants_row.html', $this->pl->getDirectory());
		$this->addColumns();

		$this->setFormAction($this->ctrl->getFormAction($this->parent_obj));
		$this->addMultiCommand('setDate', $this->pl->txt('set_date_and_create'));

		$this->parseData();
	}

	protected function parseData() {
		$ilCourseParticipants = new ilCourseParticipants(ilObject::_lookupObjectId($_GET['ref_id']));
		$participant_ids = $ilCourseParticipants->getParticipants();

		$data = array();
		foreach ($participant_ids as $usr_id) {
			$user_set = array();
			$ilObjUser = new ilObjUser($usr_id);

			$user_set['id'] = $usr_id;
			$user_set['firstname'] = $ilObjUser->getFirstname();
			$user_set['lastname'] = $ilObjUser->getLastname();

			$data[] = $user_set;
		}

		$this->setData($data);
	}


	/**
	 * Add columns to table
	 *
	 */
	protected function addColumns()
	{
		// Multi actions
		$this->addColumn("", "", "1", true);

		// Main columns
		foreach (self::$columns as $column) {
			$this->addColumn($this->pl->txt($column), $column);
		}

		// Actions column
		$this->addColumn($this->pl->txt('actions'), '', '', true);
	}


	protected function fillRow($a_set) {
		$this->tpl->setCurrentBlock('CHECKBOXES');
		$this->tpl->setVariable('VALUE', $a_set['id']);
		$this->tpl->parseCurrentBlock();

		$utc = ilCertificateConfig::get('time_format_utc');
		$date_function = ($utc)? 'gmdate' : 'date';

		foreach (self::$columns as $k => $column) {
			$value = (is_null($a_set[$column])) ? '' : $a_set[$column];

			if ($column == 'passed_at') {
				/** @var srCertificate $cert */
				$cert = srCertificate::where(array(
					'active' => 1,
					'user_id' => $a_set['id']
				))->first();

				if ($cert) {
					$time = strtotime($cert->getValidFrom());
					switch ($this->user->getDateFormat()) {
						case ilCalendarSettings::DATE_FORMAT_DMY:
							$value = $date_function('d.m.Y', $time);
							break;
						case ilCalendarSettings::DATE_FORMAT_MDY:
							$value = $date_function('m/d/Y', $time);
							break;
					}
				} else {
					$value = $this->pl->txt('not_passed_yet');
				}
			}

			// Set value
			$this->tpl->setCurrentBlock('COL');
			$this->tpl->setVariable('VALUE', $value);
			$this->tpl->parseCurrentBlock();

		}
		// Actions
		$this->ctrl->setParameter($this->parent_obj, 'user_id', $a_set['id']);
		$actions = new ilAdvancedSelectionListGUI();
		$actions->setId('action_list_' . $a_set['id']);
		$actions->setListTitle($this->pl->txt('actions'));
		$actions->addItem($this->pl->txt('set_date_and_create'), 'setDate', $this->ctrl->getLinkTarget($this->parent_obj, 'setDate'));

		$this->tpl->setCurrentBlock('ACTIONS');
		$this->tpl->setVariable('ACTIONS', $actions->getHTML());
		$this->tpl->parseCurrentBlock();
	}
}