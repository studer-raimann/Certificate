<?php

/**
 * Class srLPObjectsSelectorExplorer
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srLPObjectsSelectorExplorer extends ilRepositorySelectorExplorerGUI
{

    /**
     * srLPObjectsSelectorExplorer constructor.
     * @param        $a_parent_obj
     * @param        $a_parent_cmd
     * @param null   $a_selection_gui
     * @param string $a_selection_cmd
     * @param string $a_selection_par
     * @param string $a_id
     */
    public function __construct(
        $a_parent_obj,
        $a_parent_cmd,
        $a_selection_gui = null,
        $a_selection_cmd = "selectObject",
        $a_selection_par = "sel_ref_id",
        $a_id = "rep_exp_sel"
    ) {
        parent::__construct($a_parent_obj, $a_parent_cmd, $a_selection_gui, $a_selection_cmd,
            $a_selection_par, $a_id);
        $this->setRootId(filter_input(INPUT_GET, 'ref_id'));
        $this->setSelectMode(srCertParticipationCertificate::F_CONDITION_OBJECT_VALUE_REF_IDS, true);
    }

    /**
     * @param array $a_node
     * @return bool
     */
    function isNodeVisible($a_node)
    {
        $lp = ilCourseLP::getInstance(ilObjCourse::_lookupObjectId($this->getRootId()))->getCollectionInstance();
        $LP_items = is_null($lp) ? [] : $lp->getPossibleItems($this->getRootId());
        return in_array($a_node['child'], $LP_items);
    }

}