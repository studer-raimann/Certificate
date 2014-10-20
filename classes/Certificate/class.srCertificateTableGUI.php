<?php
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.srCertificate.php');

/**
 * Class srCertificateTableGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateTableGUI extends ilTable2GUI{

    /**
     * All available columns
     *
     * @var array
     */
    protected static $default_columns = array(
        'id',
        'firstname',
        'lastname',
        'crs_title',
        'valid_from',
        'valid_to',
        'file_version',
        'cert_type'
    );

    /**
     * Stores columns to display
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Stores available actions
     *
     * @var array
     */
    protected $actions = array();

    /**
     * Stores available multiple actions
     *
     * @var array
     */
    protected $actions_multi = array();

    /**
     * True if filter is showed
     *
     * @var bool
     */
    protected $show_filter = true;

    /**
     * @var int
     */
    protected $definition_id = 0;

    /**
     * @var bool
     */
    protected $newest_version_only = true;

    /**
     * @var array
     */
    protected $filter_names = array();

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    /**
     * @var ilCtrl
     */
    protected $ctrl;


    /**
     * Options array can contain the following key/value pairs
     * - show_filter : True if filtering data is possible
     * - columns : Array of columns to display
     * - definition_id: ID of a definition  -> shows certificates only from this definition
     * - newest_version_only : True to display the newest versions of certificates only
     * - actions : Array of possible actions, currently possible: array('download')
     * - actions_multi: Array of possible multi-actions, atm: array('download_zip')
     *
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param array $options
     */
    public function __construct($a_parent_obj, $a_parent_cmd = "", array $options=array())
    {
        global $ilCtrl;

        $ref_id = (isset($_GET['ref_id'])) ? $_GET['ref_id'] : '';
        $this->setId("srCertificateTableGUI_{$ref_id}_{$a_parent_cmd}");
        parent::__construct($a_parent_obj, $a_parent_cmd, "");

        $_options = array(
            'show_filter' => true,
            'columns' => self::$default_columns,
            'definition_id' => 0,
            'newest_version_only' => true,
            'actions' => array('download'),
            'actions_multi' => array('download_zip')
        );
        $options = array_merge($_options, $options);

        $this->columns = $options['columns'];
        $this->show_filter = $options['show_filter'];
        $this->actions = $options['actions'];
        $this->actions_multi = $options['actions_multi'];
        $this->definition_id = $options['definition_id'];
        $this->newest_version_only = $options['newest_version_only'];
        $this->pl = new ilCertificatePlugin();
        $this->ctrl = $ilCtrl;
        $this->setRowTemplate('tpl.cert_row.html', $this->pl->getDirectory());
        if (count($this->actions_multi)) {
            $this->addColumn("", "", "1", true);
            $this->setSelectAllCheckbox("cert_id[]");
            $this->addMultiCommand("downloadCertificates", $this->pl->txt('download_zip'));
        }
        $this->addColumns();
        if (count($this->actions)) {
            $this->addColumn($this->pl->txt('actions'));
        }
        $this->setExportFormats(array(self::EXPORT_EXCEL));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        if ($this->show_filter) {
            $this->initFilter();
        }
        $this->buildData();
    }


    /**
     * Add filter items
     *
     */
    public function initFilter()
    {
        if (in_array('id', $this->columns)) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('cert_id'), 'id'));
        if (in_array('firstname', $this->columns)) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('firstname'), 'firstname'));
        if (in_array('lastname', $this->columns)) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('lastname'), 'lastname'));
        if (in_array('crs_title', $this->columns)) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('crs_title'), 'crs_title'));

        if (in_array('valid_from', $this->columns)) {
            $item = new ilDateTimeInputGUI($this->pl->txt('valid_from'), 'valid_from');
            $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
            $this->addFilterItemWithValue($item);
        }

        if (in_array('valid_to', $this->columns)) {
            $item = new ilDateTimeInputGUI($this->pl->txt('valid_to'), 'valid_to');
            $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
            $this->addFilterItemWithValue($item);
        }

        if (in_array('cert_type', $this->columns)) {
            $item = new ilSelectInputGUI($this->pl->txt('cert_type'), 'type_id');
            $options = array('' => '') + srCertificateType::getArray('id', 'title');
            $item->setOptions($options);
            $this->addFilterItemWithValue($item);
        }

        $item = new ilCheckboxInputGUI($this->pl->txt('only_newest_version'), 'active');
        $this->addFilterItemWithValue($item);
    }


    /**
     * @param array $a_set
     */
    protected function fillRow(array $a_set)
    {
        foreach ($this->columns as $k => $column) {
            $value = (is_null($a_set[$column])) ? '' : $a_set[$column];
            // For checkboxes in first column
            if (count($this->actions_multi) && $k == 0) {
                $this->tpl->setCurrentBlock('CERT_ID');
                $this->tpl->setVariable('VALUE', $value);
                $this->tpl->parseCurrentBlock();
            }
            $this->tpl->setCurrentBlock('COL');
            $this->tpl->setVariable('VALUE', $value);
            $this->tpl->parseCurrentBlock();

            // Download action is only possible if status is processed
            if (count($this->actions) && $a_set['status'] == srCertificate::STATUS_PROCESSED) {
                $this->tpl->setCurrentBlock('ACTIONS');
                $this->tpl->setVariable('ACTIONS', $this->buildActions($a_set)->getHTML());
                $this->tpl->parseCurrentBlock();
            }
        }
    }

    /**
     * @param object $a_worksheet
     * @param int $a_row
     * @param array $a_set
     */
    protected function fillRowExcel($a_worksheet, &$a_row, $a_set)
    {
        $col = 0;
        foreach ($this->columns as $column) {
            $value = (is_null($a_set[$column])) ? '' : $a_set[$column];
            $a_worksheet->write($a_row, $col, strip_tags($value));
            $col++;
        }
    }


    /**
     * Build action menu for a record
     *
     * @param array $a_set
     * @return ilAdvancedSelectionListGUI
     */
    protected function buildActions(array $a_set) {
        $alist = new ilAdvancedSelectionListGUI();
        $alist->setId($a_set['id']);
        $alist->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameter($this->parent_obj, 'cert_id', $a_set['id']);
        $alist->addItem('Download', 'download', $this->ctrl->getLinkTarget($this->parent_obj, 'downloadCertificate'));
        return $alist;
    }


    /**
     * Add filter items
     *
     * @param $item
     */
    protected function addFilterItemWithValue($item) {
        /**
         * @var $item ilSelectInputGUI
         */
        $this->addFilterItem($item);
        $item->readFromSession();
        switch (get_class($item)) {
            case 'ilSelectInputGUI':
                $value = $item->getValue();
                break;
            case 'ilCheckboxInputGUI':
                $value = $item->getChecked();
                break;
            case 'ilDateTimeInputGUI':
                $value = $item->getDate();
                break;
            default:
                $value = $item->getValue();
                break;
        }
        if ($value) {
            $this->filter_names[$item->getPostVar()] = $value;
        }
    }


    /**
     * Add columns to table
     *
     */
    protected function addColumns()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($this->pl->txt($column), $column);
        }
    }


    /**
     * Get data from model based on filter
     *
     */
    protected function buildData()
    {
        $filters = $this->filter_names;
        if (isset($filters['valid_from'])) {
            $filters['valid_from'] = date('Y-m-d', $filters['valid_from']->getUnixTime());
        }
        if (isset($filters['valid_to'])) {
            $filters['valid_to'] = date('Y-m-d', $filters['valid_to']->getUnixTime());
        }
        if ($this->definition_id) {
            $filters['definition_id'] = $this->definition_id;
        }
        if ($this->newest_version_only && !$this->show_filter) {
            $filters['active'] = 1;
        }
        $this->setExternalSorting(true);
        $this->setExternalSegmentation(true);
        $this->setDefaultOrderField($this->columns[0]);
        $this->determineLimit();
        $this->determineOffsetAndOrder();
        $options = array(
            'filters' => $filters,
            'count' => true,
            'limit' => array($this->getOffset(), $this->getLimit()),
            'sort' => array($this->getOrderField() => $this->getOrderDirection()),
        );
        $count = srCertificate::getCertificateData($options);
        $data = srCertificate::getCertificateData(array_merge($options, array('count' => false)));
        $this->setMaxCount($count);
        $this->setData($data);
    }


}