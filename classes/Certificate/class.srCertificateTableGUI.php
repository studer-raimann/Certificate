<?php
require_once('./Customizing/global/plugins/Libraries/ActiveRecord/Views/Index/class.arIndexTableGUI.php');

/**
 * Class srCertificateTableGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateTableGUI extends ilTable2GUI{

    /**
     * @var array
     */
    protected static $columns = array(
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


    public function __construct($a_parent_obj, $a_parent_cmd = "", $a_template_context = "")
    {
        global $ilCtrl;

        $this->setId('srCertificateTableGUI');
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_template_context);
        $this->pl = new ilCertificatePlugin();
        $this->ctrl = $ilCtrl;
        $this->setRowTemplate('tpl.cert_row.html', $this->pl->getDirectory());
        $this->addColumns();
        $this->setSelectAllCheckbox("cert_id[]");
        $this->addMultiCommand("downloadCertificates", "Download as ZIP");
        $this->setExportFormats(array(self::EXPORT_EXCEL));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->initFilter();
        $this->buildData();
    }


    /**
     * Add filter items
     */
    public function initFilter()
    {
        $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('cert_id'), 'id'));
        $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('firstname'), 'firstname'));
        $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('lastname'), 'lastname'));
        $this->addFilterItemWithValue(new ilTextInputGUI($this->pl->txt('crs_title'), 'crs_title'));

        $item = new ilDateTimeInputGUI($this->pl->txt('valid_from'), 'valid_from');
        $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
        $this->addFilterItemWithValue($item);

        $item = new ilDateTimeInputGUI($this->pl->txt('valid_to'), 'valid_to');
        $item->setMode(ilDateTimeInputGUI::MODE_INPUT);
        $this->addFilterItemWithValue($item);

        $item = new ilSelectInputGUI($this->pl->txt('cert_type'), 'type_id');
        $options = array('' => '') + srCertificateType::getArray('id', 'title');
        $item->setOptions($options);
        $this->addFilterItemWithValue($item);

        $item = new ilCheckboxInputGUI($this->pl->txt('only_newest_version'), 'active');
        $this->addFilterItemWithValue($item);
    }


    protected function fillRow($a_set)
    {
        foreach (self::$columns as $column) {
            $value = (is_null($a_set[$column])) ? '' : $a_set[$column];
            if ($column == 'id') {
                $this->tpl->setCurrentBlock('CERT_ID');
                $this->tpl->setVariable('VALUE', $value);
                $this->tpl->parseCurrentBlock();
            }
            $this->tpl->setCurrentBlock('COL');
            $this->tpl->setVariable('VALUE', $value);
            $this->tpl->parseCurrentBlock();
        }
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
        $this->addColumn("", "", "1", true);
        foreach (self::$columns as $column) {
            $this->addColumn($this->pl->txt($column), $column);
        }
        $this->addColumn($this->pl->txt('actions'));
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
        $data = srCertificate::getCertificateData($filters);
        $this->setData($data);
    }


}