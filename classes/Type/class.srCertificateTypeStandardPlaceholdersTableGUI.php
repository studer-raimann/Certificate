<?php
require_once(dirname(dirname(__FILE__)) . '/Placeholder/class.srCertificateStandardPlaceholders.php');

/**
 * Table class srCertificateTypeStandardPlaceholdersTableGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 **/
class srCertificateTypeStandardPlaceholdersTableGUI extends ilTable2GUI {

	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCertificatePlugin
	 */
	protected $pl;
	/**
	 * @var array
	 */
	protected $columns = array(
		'identifier',
		'description',
	);


	/**
	 * @param        $a_parent_obj
	 * @param string $a_parent_cmd
	 */
	public function __construct($a_parent_obj, $a_parent_cmd) {
		global $DIC;
		$this->setId('cert_type_standard_placeholders');
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->pl = ilCertificatePlugin::getInstance();
		$this->ctrl = $DIC->ctrl();
		$this->toolbar = $DIC->toolbar();
		$this->setRowTemplate('tpl.type_standard_placeholders_row.html', $this->pl->getDirectory());
		$this->initColumns();
		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
		$this->setTitle($this->pl->txt('standard_placeholders'));
		$this->setDescription($this->pl->txt('standard_placeholders_desc'));
		$this->buildData();
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('IDENTIFIER', $a_set['identifier']);
		$this->tpl->setVariable('DESCRIPTION', $a_set['description']);
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
		/** @var $placeholder srCertificatePlaceholder */
		foreach (srCertificateStandardPlaceholders::getStandardPlaceholders() as $identifier => $desc) {
			$row = array();
			$row['identifier'] = $identifier;
			$row['description'] = $desc;
			$data[] = $row;
		}
		$this->setData($data);
	}
}
