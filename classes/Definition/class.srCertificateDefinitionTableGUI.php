<?php
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificate.php');

/**
 * GUI-Class srCertificateDefinitionGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 **/
class srCertificateDefinitionTableGUI extends ilTable2GUI
{

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var array
     */
    protected $columns = array(
        'firstname',
        'lastname',
        'valid_from',
        'valid_to',
        'file_version',
    );


    /**
     * @var srCertificateDefinition
     */
    protected $definition;


    public function __construct($a_parent_obj, $a_parent_cmd, srCertificateDefinition $definition)
    {
        global $ilCtrl;
        $this->setId('cert_definition_table_' . (int)$_GET['ref_id']);
        $this->setPrefix('cert_definition_table');
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->pl = new ilCertificatePlugin();
        $this->ctrl = $ilCtrl;
        $this->definition = $definition;
        $this->setRowTemplate('tpl.definition_row.html', $this->pl->getDirectory());
        $this->addColumn("", "", "1", true);
        $this->initColumns();
        $this->addColumn($this->pl->txt('actions'));
        $this->setSelectAllCheckbox("cert_id[]");
        $this->addMultiCommand("downloadCertificates", "Download as ZIP");
        $this->setExportFormats(array(self::EXPORT_EXCEL));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->buildData();
    }

    /**
     * @param array $a_set
     */
    public function fillRow($a_set)
    {
        $this->tpl->setVariable('FIRST_NAME', $a_set['firstname']);
        $this->tpl->setVariable('LAST_NAME', $a_set['lastname']);
        $this->tpl->setVariable('VALID_FROM', date('d.m.Y', strtotime($a_set['valid_from'])));
        if ($a_set['valid_to']) {
            $this->tpl->setVariable('VALID_TO', date('d.m.Y', strtotime($a_set['valid_to'])));
        }
        $this->tpl->setVariable('FILE_VERSION', $a_set['file_version']);
        if ($a_set['status'] == srCertificate::STATUS_PROCESSED) {
            $this->tpl->setVariable('CERT_ID', $a_set['id']);
            $this->tpl->setVariable('ACTIONS', $this->buildActionMenu($a_set)->getHTML());
        }
    }


    /**
     * @param $worksheet
     * @param int $row
     */
    public function fillHeaderExcel($worksheet, &$row)
    {
        $col = 0;
        foreach ($this->columns as $column) {
            $worksheet->writeString($row, $col, $this->pl->txt($column));
            $col++;
        }
    }

    /**
     * @param object $worksheet
     * @param int $row
     * @param array $record
     */
    public function fillRowExcel($worksheet, &$row, $record)
    {
        $col = 0;
        foreach ($this->columns as $column) {
            $worksheet->writeString($row, $col, $record[$column]);
            $col++;
        }
    }


    /**
     * Build action menu for certificates
     * Note: ATM we only support download, but other actions are possible: delete, new version of certificate
     *
     * @param array $a_set
     * @return ilAdvancedSelectionListGUI
     */
    protected function buildActionMenu(array $a_set)
    {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($a_set['id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameterByClass('srcertificatedefinitiongui', 'cert_id', $a_set['id']);
        $alist->addItem('Download', 'download', $this->ctrl->getLinkTargetByClass('srcertificatedefinitiongui', 'downloadCertificate'));
        return $alist;
    }

    /**
     * Add columns
     */
    protected function initColumns()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($this->pl->txt($column), $column);
        }
    }

    /**
     * Get data to display in the table
     */
    protected function buildData()
    {
        $options = array(
            'filters' => array('definition_id' => $this->definition->getId(), 'active' => 1),
        );
        $data = srCertificate::getCertificateData($options);
        $this->setData($data);
    }

}
