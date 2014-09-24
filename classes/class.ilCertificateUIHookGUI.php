<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */
include_once('./Services/UIComponent/classes/class.ilUIHookPluginGUI.php');

/**
 * User interface hook class for CourseCertificate-Plugin
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilCertificateUIHookGUI extends ilUIHookPluginGUI
{

    /**
     * @var ilCtrl
     */
    public $ctrl;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @var ilAccessHandler
     */
    protected $access;

    function __construct()
    {
        global $ilCtrl, $tpl, $ilUser, $ilAccess;
        $this->ctrl = $ilCtrl;
        $this->pl = new ilCertificatePlugin();
        $this->user = $ilUser;
        $this->access = $ilAccess;
    }


    function modifyGUI($a_comp, $a_part, $a_par = array())
    {
        include_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Placeholder/class.srCertificatePlaceholderValue.php');
        /**
         * @var $ilTabsGUI ilTabsGUI
         */
        if ($a_part == 'tabs' && isset($_GET['ref_id'])) {
            // ATM only display certificate tab in courses
            if (ilObject::_lookupType((int)$_GET['ref_id'], true) != 'crs') return;
            // User needs write access to course to see the tab 'certificate'
            if ($this->access->checkAccess('write', '', (int)$_GET['ref_id'])) {
                $ilTabsGUI = $a_par['tabs'];
                $this->ctrl->setParameterByClass('srCertificateDefinitionGUI', 'ref_id', $_GET['ref_id']);
                $link = $this->ctrl->getLinkTargetByClass(array(ilCertificatePlugin::getBaseClass(), 'srCertificateDefinitionGUI'));
                $ilTabsGUI->addTarget('certificate', $link, 'show', 'srCertificateDefinitionGUI');
            }
        }
    }

}

?>
