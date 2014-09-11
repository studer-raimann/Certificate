<?php
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('class.srCertificate.php');

/**
 * TableGUI ilCourseCertificateTableGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id:
 */
class ilCertificateTableGUI extends ilTable2GUI
{

    /**
     * @var array
     */
    protected $table_headers;


    /**
     * @param ilCertificateGUI $a_parent_obj
     * @param string $a_parent_cmd
     */
    public function  __construct(ilCertificateGUI $a_parent_obj, $a_parent_cmd)
    {
        global $ilCtrl, $ilUser;
        parent::__construct($a_parent_obj, $a_parent_cmd);
        /**
         * @var $tpl    ilTemplate
         * @var $ilCtrl ilCtrl
         * @var $ilTabs ilTabsGUI
         */
        $this->parent_obj = $a_parent_obj;
        $this->ctrl = $ilCtrl;
        $this->pl = new ilCertificatePlugin();
        $this->setEnableTitle(false);
        $this->setRowTemplate('tpl.certificate_row.html', 'Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate');
        $this->setTitle($this->pl->txt("certificates"));
        $this->setEnableTitle(true);
        $this->setTableHeaders();
        $this->setDefaultOrderDirection("desc");
        $this->setDefaultOrderField("valid_until");
        $this->setFilterCols(6);
        $this->setFormAction($ilCtrl->getFormAction($this->parent_obj, 'applyFilter'));
        $this->initFilter();
        $this->setFilterCommand('applyFilter');
        $this->setData(srCertificate::_getAllCertificatesForUserId($ilUser->getId(), $this->filter));
    }


    public function initFilter()
    {
        // Title
        $input = new ilTextInputGUI($this->pl->txt('title'), 'title');
        // $input->setSubmitFormOnEnter(true);
        $this->addFilterItem($input);
        $input->readFromSession();
        $this->filter['title'] = $input->getValue();
        // Valid from
        $input = new ilDateTimeInputGUI($this->pl->txt('valid_from'), 'valid_from');
        $input->setMode(2);
        $this->addFilterItem($input);
        $input->readFromSession();
        $date = $input->getDate();
        /**
         * @var $date ilDateTime
         */
        if (is_object($date)) {
            $this->filter['valid_from'] = $date->getUnixTime();
        }
        // Valid to
        $input = new ilDateTimeInputGUI($this->pl->txt('valid_to'), 'valid_to');
        $input->setMode(2);
        $this->addFilterItem($input);
        $input->readFromSession();
        $date = $input->getDate();
        /**
         * @var $date ilDateTime
         */
        if (is_object($date)) {
            $this->filter['valid_to'] = $date->getUnixTime();
        }
        // Type
        $input = new ilSelectInputGUI($this->pl->txt('certificate_type'), 'type_id');
        $opt[-1] = $this->pl->txt("all");
        foreach (srCertificateType::_getAll() as $type) {
            /**
             * @var $type srCertificateType
             */
            $opt[$type->getId()] = $this->pl->txt('certificate_type_' . $type->getId()); //$type->getTitle();
        }
        $input->setOptions($opt);
        $this->addFilterItem($input);
        $input->readFromSession();
        $this->filter['type_id'] = $input->getValue();
    }


    protected function setTableHeaders()
    {
        $this->table_headers = array(
            array("text" => $this->pl->txt("certificate_type"), "sort_field" => "certificate_type"),
            array("text" => $this->pl->txt("title"), "sort_field" => "title"),
            array("text" => $this->pl->txt("valid_until"), "sort_field" => "valid_until"),
            array("text" => ""),
        );
        foreach ($this->table_headers as $table_header) {
            $this->addColumn($table_header["text"], $table_header["sort_field"], $table_header["width"], $table_header["is_checkbox_col"], $table_header["class"], $table_header["tooltip"]);
        }
    }


    /**
     * @param srCertificate $a_set
     */
    protected function fillRow($a_set)
    {
        $a_set = new srCertificate($a_set['id']);
        $this->tpl->setVariable('TITLE', $a_set->getTitle());
        $this->tpl->setVariable('STATUS', $a_set->getValidationstatus());
        $this->tpl->setVariable('VALID_UNTIL', $a_set->getValidTo() ? date('d.m.Y', $a_set->getValidTo()) : $this->pl->txt('month_unlimited'));
        $this->tpl->setVariable('TYPE', $this->pl->txt('certificate_type_' . $a_set->getType()));

        if ($a_set->getStatus() != srCertificate::STATUS_PROCESSED) {
            $this->tpl->setVariable('IN_PROCESS', $this->pl->txt('in_process'));
        } else {
            $this->tpl->setVariable('DOWNLOAD', $this->pl->txt('download'));
            $this->ctrl->setParameter($this->parent_obj, 'cert_id', $a_set->getId());
            $this->tpl->setVariable('DOWNLOAD_LINK', $this->ctrl->getLinkTarget($this->parent_obj, 'downloadCertificate'));
        }

    }
}

?>