<?php
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('class.srCertificate.php');
require_once('./Services/Table/classes/class.ilTable2GUI.php');

/**
 * Class srCertificateTableGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateTableGUI extends ilTable2GUI
{

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
     * @var array
     */
    protected $options = array();

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
     * @var ilObjUser
     */
    protected $user;


    /**
     * Options array can contain the following key/value pairs
     * - show_filter : True if filtering data is possible
     * - columns : Array of columns to display
     * - definition_id: ID of a definition  -> shows certificates only from this definition
     * - user_id: ID of a user -> shows certificates only from the given user
     * - newest_version_only : True to display the newest versions of certificates only
     * - actions : Array of possible actions, currently possible: atm array('download')
     * - actions_multi: Array of possible multi-actions, atm: array('download_zip')
     *
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param array $options
     */
    public function __construct($a_parent_obj, $a_parent_cmd = "", array $options=array())
    {
        global $ilCtrl, $ilUser;

        $_options = array(
            'show_filter' => true,
            'columns' => self::$default_columns,
            'definition_id' => 0,
            'user_id' => 0,
            'newest_version_only' => true,
            'actions' => array('download'),
            'actions_multi' => array('download_zip')
        );
        $this->options = array_merge($_options, $options);
        $this->setPrefix('cert_');
        $this->setId($this->getOption('user_id') . '_' . $this->getOption('definition_id'));
        $this->columns = $this->getOption('columns');
        $this->pl = new ilCertificatePlugin();
        $this->ctrl = $ilCtrl;
        $this->user = $ilUser;

        parent::__construct($a_parent_obj, $a_parent_cmd, "");

        $this->setRowTemplate('tpl.cert_row.html', $this->pl->getDirectory());
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->addColumns();
        $this->setExportFormats(array(self::EXPORT_EXCEL));
        if ($this->getOption('show_filter')) {
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
        if ($this->isColumnSelected('id')) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('cert_id'), 'id'));
        if ($this->isColumnSelected('firstname')) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('firstname'), 'firstname'));
        if ($this->isColumnSelected('lastname')) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('lastname'), 'lastname'));
        if ($this->isColumnSelected('crs_title')) $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('crs_title'), 'crs_title'));

        if ($this->isColumnSelected('valid_from')) {
            $item = new ilDateTimeInputGUI($this->pl->txt('valid_from'), 'valid_from');
            $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
            $this->addFilterItemWithValue($item);
        }

        if ($this->isColumnSelected('valid_to')) {
            $item = new ilDateTimeInputGUI($this->pl->txt('valid_to'), 'valid_to');
            $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
            $this->addFilterItemWithValue($item);
        }

        if ($this->isColumnSelected('cert_type')) {
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
            if (count($this->getOption('actions_multi')) && $column == 'id') {
                $this->tpl->setCurrentBlock('CHECKBOXES');
                $this->tpl->setVariable('VALUE', $value);
                $this->tpl->parseCurrentBlock();
            }

            if ($this->isColumnSelected($column)) {

                // Format dates
                if (in_array($column, array('valid_from', 'valid_to'))) {
                    switch ($this->user->getDateFormat()) {
                        case ilCalendarSettings::DATE_FORMAT_DMY:
                            $value = date('d.m.Y', strtotime($value));
                            break;
                        case ilCalendarSettings::DATE_FORMAT_MDY:
                            $value = date('m/d/Y', strtotime($value));
                            break;
                    }
                }

                // Set value
                $this->tpl->setCurrentBlock('COL');
                $this->tpl->setVariable('VALUE', $value);
                $this->tpl->parseCurrentBlock();

            }
        }

        // Download action is only possible if status is processed
        if (count($this->getOption('actions'))) {
            $actions = ($a_set['status'] == srCertificate::STATUS_PROCESSED) ? $this->buildActions($a_set)->getHTML() : '&nbsp;';
            $this->tpl->setCurrentBlock('ACTIONS');
            $this->tpl->setVariable('ACTIONS', $actions);
            $this->tpl->parseCurrentBlock();
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
            if ($this->isColumnSelected($column)) {
                $worksheet->writeString($row, $col, $this->pl->txt($column));
                $col++;
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
            if ($this->isColumnSelected($column)) {
                $value = (is_null($a_set[$column])) ? '' : $a_set[$column];
                $a_worksheet->write($a_row, $col, strip_tags($value));
                $col++;
            }
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
        // Multi actions
        if (count($this->getOption('actions_multi'))) {
            $this->addColumn("", "", "1", true);
            $this->setSelectAllCheckbox("cert_id[]");
            $this->addMultiCommand("downloadCertificates", $this->pl->txt('download_zip'));
        }

        // Main columns
        foreach ($this->columns as $column) {
            if (in_array($column, self::$default_columns) && $this->isColumnSelected($column)) {
                $this->addColumn($this->pl->txt($column), $column);
            }
        }

        // Actions column
        if (count($this->getOption('actions'))) {
            $this->addColumn($this->pl->txt('actions'));
        }
    }


    /**
     * @return array
     */
    public function getSelectableColumns()
    {
        $columns = array();
        foreach ($this->columns as $column) {
            $columns[$column] = array('txt' => $this->pl->txt($column), 'default' => true);
        }

        return $columns;
    }


    /**
     * Get data from model based on filter
     *
     */
    protected function buildData()
    {
        $filters = $this->filter_names;
        if ($this->getOption('definition_id')) $filters['definition_id'] = $this->definition_id;
        if ($this->getOption('newest_version_only') && ! $this->getOption('show_filter')) $filters['active'] = 1;
        if ($this->getOption('user_id')) $filters['user_id'] = $this->user_id;

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


    /**
     * Get option
     *
     * @param $key
     * @return mixed
     */
    protected function getOption($key)
    {
        return (isset($this->options[$key])) ? $this->options[$key] : null;
    }

}