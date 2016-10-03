<?php
require_once("./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php");
require_once(dirname(__FILE__) . '/class.srCertificateType.php');

/**
 * Table class srCertificateTypeSettingsTableGUI
 *
 * @author            Stefan Wanzenried <sw@studer-raimann.ch>
 * @version           $Id:
 **/
class srCertificateTypePlaceholdersTableGUI extends ilTable2GUI
{

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
        'identifier',
        'max_characters',
        'mandatory',
        'editable_in',
    );


    /**
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param srCertificateType $type
     */
    public function __construct($a_parent_obj, $a_parent_cmd, srCertificateType $type)
    {
        global $ilCtrl, $ilToolbar;
        $this->setId('cert_type_placeholders');
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->type = $type;
        $this->pl = ilCertificatePlugin::getInstance();
        $this->ctrl = $ilCtrl;
        $this->toolbar = $ilToolbar;
        $this->setRowTemplate('tpl.type_placeholders_row.html', $this->pl->getDirectory());
        $this->initColumns();
        $this->addColumn($this->pl->txt('actions'));
        $this->setFormAction($this->ctrl->getFormAction($a_parent_obj));
        $this->toolbar->addButton($this->pl->txt('add_new_placeholder'), $this->ctrl->getLinkTargetByClass('srcertificatetypegui', 'addPlaceholder'));
        $this->buildData();
        $this->setTitle($this->pl->txt('custom_placeholders'));
    }


    /**
     * @param array $a_set
     */
    public function fillRow($a_set)
    {
        $skip_fields = array('id');
        foreach ($a_set as $k => $v) {
            if (in_array($k, $skip_fields)) {
                continue;
            }
            $this->tpl->setCurrentBlock('td');
            $this->tpl->setVariable('TD_VALUE', $v);
            $this->tpl->parseCurrentBlock();
        }
        $this->tpl->setVariable('ACTIONS', $this->buildActionMenu($a_set)->getHTML());
    }


    /**
     * Build action menu
     *
     * @param array $a_set
     * @return ilAdvancedSelectionListGUI
     */
    protected function buildActionMenu(array $a_set)
    {
        $list = new ilAdvancedSelectionListGUI();
        $list->setId($a_set['identifier']);
        $list->setListTitle($this->pl->txt('actions'));
        $this->ctrl->setParameterByClass('srcertificatetypegui', 'type_id', $this->type->getId());
        $this->ctrl->setParameterByClass('srcertificatetypegui', 'placeholder_id', $a_set['id']);
        $list->addItem($this->lng->txt('edit'), 'edit', $this->ctrl->getLinkTargetByClass('srcertificatetypegui', 'editPlaceholder'));
        $list->addItem($this->lng->txt('delete'), 'delete', $this->ctrl->getLinkTargetByClass('srcertificatetypegui', 'confirmDeletePlaceholder'));

        return $list;
    }


    /**
     * Add columns
     */
    protected function initColumns()
    {
        foreach ($this->columns as $column) {
            $this->addColumn($this->pl->txt($column), $column);
        }
        foreach ($this->type->getLanguages() as $lang_code) {
            $this->addColumn(sprintf($this->pl->txt('default_value_lang'), $lang_code), "default_value_{$lang_code}");
            $this->addColumn(sprintf($this->pl->txt('label_lang'), $lang_code), "label_{$lang_code}");
        }
    }


    /**
     * Get settings
     */
    protected function buildData()
    {
        $data = array();
        /** @var $placeholder srCertificatePlaceholder */
        foreach ($this->type->getPlaceholders() as $placeholder) {
            $row = array();
            $row['id'] = $placeholder->getId();
            $row['identifier'] = $placeholder->getIdentifier();
            $row['max_characters'] = $placeholder->getMaxCharactersValue();
            $row['mandatory'] = (int) $placeholder->getIsMandatory();
            $row['editable_in'] = implode(',', $placeholder->getEditableIn());
            foreach ($this->type->getLanguages() as $lang_code) {
                $row["default_value_{$lang_code}"] = $placeholder->getDefaultValue($lang_code);
                $row["label_{$lang_code}"] = $placeholder->getLabel($lang_code);
            }
            $data[] = $row;
        }
        $this->setData($data);
    }

}
