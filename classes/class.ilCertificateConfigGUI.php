<?php

include_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('class.ilCertificateConfig.php');

/**
 * CourseCertificate Configuration
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilCertificateConfigGUI extends ilPluginConfigGUI
{

    /**
     * @var ilCertificateConfig
     */
    protected $object;
    /**q
     *
     * @var array
     */
    protected $fields = array();
    /**
     * @var string
     */
    protected $table_name = '';


    function __construct()
    {
        global $ilCtrl, $tpl, $ilTabs;
        /**
         * @var $ilCtrl ilCtrl
         * @var $tpl    ilTemplate
         * @var $ilTabs ilTabsGUI
         */
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
        $this->tabs = $ilTabs;
        $this->pl = new ilCertificatePlugin();
        $this->object = new ilCertificateConfig($this->pl->getConfigTableName());
    }


    /**
     * @return array
     */
    public function getFields()
    {
        $this->fields = array(
// SW: Not yet implemented for first release
//            'async' => array(
//                'type' => 'ilCheckboxInputGUI',
//                'info' => false,
//                'subelements' => array(
//                    'username' => array(
//                        'type' => 'ilTextInputGUI',
//                    ),
//                    'password' => array(
//                        'type' => 'ilTextInputGUI',
//                    ),
//                    'client' => array(
//                        'type' => 'ilTextInputGUI',
//                    )
//                )
//            ),
//            'signature' => array(
//                'type' => 'ilCheckboxInputGUI',
//                'info' => false,
//                'subelements' => array(
//                    'privatekey' => array(
//                        'type' => 'ilTextInputGUI',
//                    ),
//                    'publickey' => array(
//                        'type' => 'ilTextInputGUI',
//                    ),
//                )
//            ),
            'course_templates' => array(
                'type' => 'ilCheckboxInputGUI',
                'info' => true,
                'subelements' => array(
                    'ref_ids' => array(
                        'type' => 'ilTextareaInputGUI',
                        'info' => true,
                    ),
                )
            ),
            'time_format_utc' => array(
                'type' => 'ilCheckboxInputGUI',
                'info' => true,
            ),
            'str_format_date' => array(
                'type' => 'ilTextInputGUI',
                'info' => true,
            ),
            'str_format_datetime' => array(
                'type' => 'ilTextInputGUI',
                'info' => true,
            ),
            'path_hook_class' => array(
                'type' => 'ilTextInputGUI',
                'info' => true,
            )
        );

        return $this->fields;
    }


    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }


    /**
     * @return ilCertificateConfig
     */
    public function getObject()
    {
        return $this->object;
    }


    /**
     * Handles all commmands, default is 'configure'
     */
    function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
            case 'save':
            case 'svn':
                $this->$cmd();
                break;
        }
    }


    /**
     * Configure screen
     */
    function configure()
    {
        $this->initConfigurationForm();
        $this->getValues();
        $this->tpl->setContent($this->form->getHTML());
    }


    public function getValues()
    {
        foreach ($this->getFields() as $key => $item) {
            $values[$key] = $this->object->getValue($key);
            if (is_array($item['subelements'])) {
                foreach ($item['subelements'] as $subkey => $subitem) {
                    $values[$key . '_' . $subkey] = $this->object->getValue($key . '_' . $subkey);
                }
            }
        }
        $this->form->setValuesByArray($values);
    }


    /**
     * @return ilPropertyFormGUI
     */
    public function initConfigurationForm()
    {
        global $lng, $ilCtrl;
        include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
        $this->form = new ilPropertyFormGUI();
        foreach ($this->getFields() as $key => $item) {
            $field = new $item['type']($this->pl->txt($key), $key);
            if ($item['info']) {
                $field->setInfo($this->pl->txt($key . '_info'));
            }
            if (is_array($item['subelements'])) {
                foreach ($item['subelements'] as $subkey => $subitem) {
                    $subfield = new $subitem['type']($this->pl->txt($key . '_' . $subkey), $key . '_' . $subkey);
                    if ($subitem['info']) {
                        $subfield->setInfo($this->pl->txt($key . '_' . $subkey . '_info'));
                    }
                    $field->addSubItem($subfield);
                }
            }
            $this->form->addItem($field);
        }
        $this->form->addCommandButton('save', $lng->txt('save'));
        $this->form->setTitle($this->pl->txt('configuration'));
        $this->form->setFormAction($ilCtrl->getFormAction($this));

        return $this->form;
    }


    public function save()
    {
        global $tpl, $ilCtrl;
        $this->initConfigurationForm();
        if ($this->form->checkInput()) {
            foreach ($this->getFields() as $key => $item) {
                $this->object->setValue($key, $this->form->getInput($key));
                if (is_array($item['subelements'])) {
                    foreach ($item['subelements'] as $subkey => $subitem) {
                        $this->object->setValue($key . '_' . $subkey, $this->form->getInput($key . '_' . $subkey));
                    }
                }
            }
            ilUtil::sendSuccess($this->pl->txt('conf_saved'));
            $ilCtrl->redirect($this, 'configure');
        } else {
            $this->form->setValuesByPost();
            $tpl->setContent($this->form->getHtml());
        }
    }
}

?>
