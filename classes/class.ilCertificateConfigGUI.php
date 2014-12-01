<?php

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('class.ilCertificateConfigFormGUI.php');
require_once('class.ilCertificateConfig.php');

/**
 * Class ilCertificateConfigGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilCertificateConfigGUI extends ilPluginConfigGUI
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
     * @var ilTemplate
     */
    protected $tpl;

    public function __construct()
    {
        global $ilCtrl, $tpl;

        $this->pl = ilCertificatePlugin::getInstance();
        $this->ctrl = $ilCtrl;
        $this->tpl = $tpl;
    }



//    /**
//     * @return array
//     */
//    public function getFields()
//    {
//        $this->fields = array(
//// SW: Not yet implemented for first release
////            'async' => array(
////                'type' => 'ilCheckboxInputGUI',
////                'info' => false,
////                'subelements' => array(
////                    'username' => array(
////                        'type' => 'ilTextInputGUI',
////                    ),
////                    'password' => array(
////                        'type' => 'ilTextInputGUI',
////                    ),
////                    'client' => array(
////                        'type' => 'ilTextInputGUI',
////                    )
////                )
////            ),
////            'signature' => array(
////                'type' => 'ilCheckboxInputGUI',
////                'info' => false,
////                'subelements' => array(
////                    'privatekey' => array(
////                        'type' => 'ilTextInputGUI',
////                    ),
////                    'publickey' => array(
////                        'type' => 'ilTextInputGUI',
////                    ),
////                )
////            ),
//            'course_templates' => array(
//                'type' => 'ilCheckboxInputGUI',
//                'info' => true,
//                'subelements' => array(
//                    'ref_ids' => array(
//                        'type' => 'ilTextareaInputGUI',
//                        'info' => true,
//                    ),
//                )
//            ),
//            'time_format_utc' => array(
//                'type' => 'ilCheckboxInputGUI',
//                'info' => true,
//            ),
//            'str_format_date' => array(
//                'type' => 'ilTextInputGUI',
//                'info' => true,
//            ),
//            'str_format_datetime' => array(
//                'type' => 'ilTextInputGUI',
//                'info' => true,
//            ),
//            'path_hook_class' => array(
//                'type' => 'ilTextInputGUI',
//                'info' => true,
//            )
//        );
//
//        return $this->fields;
//    }


    /**
     * @param $cmd
     */
    public function performCommand($cmd)
    {
        switch ($cmd) {
            case 'configure':
            case 'save':
                $this->$cmd();
                break;
        }
    }


    /**
     * Configure screen
     */
    public function configure()
    {
        $form = new ilCertificateConfigFormGUI($this);
        $form->fillForm();
        $this->tpl->setContent($form->getHTML());
    }


    /**
     * Save config
     */
    public function save()
    {
        $form = new ilCertificateConfigFormGUI($this);
        if ($form->saveObject()) {
            ilUtil::sendSuccess($this->pl->txt('todo'));
            $this->ctrl->redirect($this, 'configure');
        } else {
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
        }
    }


//    public function getValues()
//    {
//        foreach ($this->getFields() as $key => $item) {
//            $values[$key] = $this->object->getValue($key);
//            if (is_array($item['subelements'])) {
//                foreach ($item['subelements'] as $subkey => $subitem) {
//                    $values[$key . '_' . $subkey] = $this->object->getValue($key . '_' . $subkey);
//                }
//            }
//        }
//        $this->form->setValuesByArray($values);
//    }


//    /**
//     * @return ilPropertyFormGUI
//     */
//    public function initConfigurationForm()
//    {
//        global $lng, $ilCtrl;
//        include_once('Services/Form/classes/class.ilPropertyFormGUI.php');
//        $this->form = new ilPropertyFormGUI();
//        foreach ($this->getFields() as $key => $item) {
//            $field = new $item['type']($this->pl->txt($key), $key);
//            if ($item['info']) {
//                $field->setInfo($this->pl->txt($key . '_info'));
//            }
//            if (is_array($item['subelements'])) {
//                foreach ($item['subelements'] as $subkey => $subitem) {
//                    $subfield = new $subitem['type']($this->pl->txt($key . '_' . $subkey), $key . '_' . $subkey);
//                    if ($subitem['info']) {
//                        $subfield->setInfo($this->pl->txt($key . '_' . $subkey . '_info'));
//                    }
//                    $field->addSubItem($subfield);
//                }
//            }
//            $this->form->addItem($field);
//        }
//        $this->form->addCommandButton('save', $lng->txt('save'));
//        $this->form->setTitle($this->pl->txt('configuration'));
//        $this->form->setFormAction($ilCtrl->getFormAction($this));
//
//        return $this->form;
//    }
//
//
//    public function save()
//    {
//        global $tpl, $ilCtrl;
//        $this->initConfigurationForm();
//        if ($this->form->checkInput()) {
//            foreach ($this->getFields() as $key => $item) {
//                $this->object->setValue($key, $this->form->getInput($key));
//                if (is_array($item['subelements'])) {
//                    foreach ($item['subelements'] as $subkey => $subitem) {
//                        $this->object->setValue($key . '_' . $subkey, $this->form->getInput($key . '_' . $subkey));
//                    }
//                }
//            }
//            ilUtil::sendSuccess($this->pl->txt('conf_saved'));
//            $ilCtrl->redirect($this, 'configure');
//        } else {
//            $this->form->setValuesByPost();
//            $tpl->setContent($this->form->getHtml());
//        }
//    }
}
