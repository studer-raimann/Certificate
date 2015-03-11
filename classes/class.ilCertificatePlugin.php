<?php

// Include ActiveRecord base class, in ILIAS >= 4.5 use ActiveRecord from Core
if (is_file('./Services/ActiveRecord/class.ActiveRecord.php')) {
    require_once('./Services/ActiveRecord/class.ActiveRecord.php');
} elseif (is_file('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php')) {
    require_once('./Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php');
}

require_once('./Services/UIComponent/classes/class.ilUserInterfaceHookPlugin.php');
require_once('class.ilCertificateConfig.php');
require_once('class.srCertificateHooks.php');
require_once('./Services/Mail/classes/class.ilMail.php');

/**
 * Certificate Plugin
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilCertificatePlugin extends ilUserInterfaceHookPlugin
{

    /**
     * Name of class that can implement hooks
     */
    const CLASS_NAME_HOOKS = 'srCertificateCustomHooks';

    /**
     * Default path for hook class (can be changed in plugin config)
     */
    const DEFAULT_PATH_HOOK_CLASS = './Customizing/global/Certificate/';

    /**
     * Default formats (can be changed in plugin config)
     */
    const DEFAULT_DATE_FORMAT = 'Y-m-d';
    const DEFAULT_DATETIME_FORMAT = 'Y-m-d, H:i';
    const DEFAULT_DISK_SPACE_WARNING = 10;

    /**
     * Default permission settings
     */
    const DEFAULT_ROLES_ADMINISTRATE_CERTIFICATES = '["2"]';
    const DEFAULT_ROLES_ADMINISTRATE_CERTIFICATE_TYPES = '["2"]';

    /**
     * @var srCertificateHooks
     */
    protected $hooks;

    /**
     * This will be ilRouterGUI for ILIAS <= 4.4.x if the corresponding Router service is installed
     * and ilUIPluginRouterGUI for ILIAS >= 4.5.x
     *
     * @var string
     */
    protected static $base_class;

    /**
     * @var ilCertificatePlugin
     */
    protected static $instance;

    /**
     * @var bool
     */
    protected static $disk_space_warning_sent = false;



    /**
     * @return ilCertificatePlugin
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }


    /**
     * @return string
     */
    public function getPluginName()
    {
        return 'Certificate';
    }


    /**
     * Get a config value
     *
     * @param string $name
     * @return string|null
     */
    public function config($name)
    {
        return ilCertificateConfig::get($name);
    }


    /**
     * Get Hooks object
     *
     * @return srCertificateHooks
     */
    public function getHooks()
    {
        if (is_null($this->hooks)) {
            $class_name = self::CLASS_NAME_HOOKS;
            $path = ilCertificateConfig::get('path_hook_class');
            if (substr($path, -1) !== '/') {
                $path .= '/';
            }
            $file = $path . "class.{$class_name}.php";
            if (is_file($file)) {
                require_once($file);
                $object = new $class_name($this);
            } else {
                $object = new srCertificateHooks($this);
            }
            $this->hooks = $object;
        }
        return $this->hooks;
    }


    /**
     * Check if course is a "template course"
     * This method returns true if the given ref-ID is a children of a category defined in the plugin options
     *
     * @param int $ref_id Ref-ID of the object to check
     * @return bool
     */
    public function isCourseTemplate($ref_id)
    {
        global $tree;

        if (ilCertificateConfig::get('course_templates') && ilCertificateConfig::get('course_templates_ref_ids')) {
            // Course templates enabled -> check if given ref_id is defined as template
            $ref_ids = explode(',', ilCertificateConfig::get('course_templates_ref_ids'));
            /** @var $tree ilTree */
            $parent_ref_id = $tree->getParentId($ref_id);
            return in_array($parent_ref_id, $ref_ids);
        }
        return false;
    }


    /**
     * Check if preconditions are given to use this plugin
     *
     * @return bool
     */
    public function checkPreConditions()
    {
        global $ilPluginAdmin;

        /** @var $ilPluginAdmin ilPluginAdmin */
        $exists = $ilPluginAdmin->exists(IL_COMP_SERVICE, 'EventHandling', 'evhk', 'CertificateEvents');
        $active = $ilPluginAdmin->isActive(IL_COMP_SERVICE, 'EventHandling', 'evhk', 'CertificateEvents');
        return (self::getBaseClass() && $exists && $active);
    }


    /**
     * Don't activate plugin if preconditions are not given
     *
     * @return bool
     */
    protected function beforeActivation()
    {
        if ( ! $this->checkPreConditions()) {
            ilUtil::sendFailure("You need to install the 'CertificateEvents' plugin");
            return false;
        }
        return true;
    }


    /**
     * Returns in what class the command/ctrl chain should start for this plugin.
     * Return value is ilRouterGUI for ILIAS <= 4.4.x, ilUIPluginRouterGUI for ILIAS >= 4.5, of false otherwise
     *
     * @return bool|string
     */
    public static function getBaseClass()
    {
        if ( ! is_null(self::$base_class)) {
            return self::$base_class;
        }

        global $ilCtrl;
        if ($ilCtrl->lookupClassPath('ilUIPluginRouterGUI')) {
            self::$base_class = 'ilUIPluginRouterGUI';
        } elseif ($ilCtrl->lookupClassPath('ilRouterGUI')) {
            self::$base_class = 'ilRouterGUI';
        } else {
            self::$base_class = false;
        }

        return self::$base_class;
    }


    public function sendMail($type, $cert){
        switch($type){
            case 'callback':
                $this->sendCallBackNotification($cert);
                break;
            case 'disk_space_warning':
                $this->sendDiskSpaceWarning($cert);
                break;
            case 'no_space_left':
                $this->sendNoSpaceLeftNotification($cert);
                break;
            case 'not_writeable':
                $this->sendNotWriteableNotification($cert);
                break;
        }
    }

    /**
     * @param $cert srCertificate
     */
    protected function sendCallBackNotification($cert){
        if($address = ilCertificateConfig::get('callback_email')){
            $this->loadLanguageModule();
            $mail = new ilMail(ANONYMOUS_USER_ID);
            $subject = $this->txt('callback_email_subject');
            $message = $this->txt('callback_email_message');
            $message .= $this->getCertDetailsForMail($cert);
            $mail->sendMail($address, '', '', $subject, $message, array(), array("system"));
        }
    }

    /**
     * @param $cert srCertificate
     */
    protected  function sendDiskSpaceWarning($cert){
        $admin_address = ilSetting::_lookupValue('common', 'admin_email');
        $mail = new ilMail(ANONYMOUS_USER_ID);
        $subject = $this->txt('disk_space_warning_mail_subject');
        $message = $this->txt('disk_space_warning_mail_message');
        $message .= "\n\n Free disk space left: " . disk_free_space($cert->getCertificatePath()) . " Bytes";
        $message .= ilMail::_getInstallationSignature();
        $mail->sendMail($admin_address, '', '', $subject, $message, array(), array("system"));
        self::$disk_space_warning_sent = true;
    }

    /**
     * @param $cert srCertificate
     */
    protected function sendNoSpaceLeftNotification($cert){
        $admin_address = ilSetting::_lookupValue('common', 'admin_email');
        $mail = new ilMail(ANONYMOUS_USER_ID);
        $subject = $this->txt('no_space_left_subject');
        $message = $this->txt('no_space_left_message');
        $message .= $this->getCertDetailsForMail($cert);
        $mail->sendMail($admin_address, '', '', $subject, $message, array(), array("system"));
    }

    /**
     * @param $cert srCertificate
     */
    protected function sendNotWriteableNotification($cert){
        $admin_address = ilSetting::_lookupValue('common', 'admin_email');
        $mail = new ilMail(ANONYMOUS_USER_ID);
        $subject = $this->txt('writeperm_failed_subject');
        $message = $this->txt('writeperm_failed_message');
        $message .= $this->getCertDetailsForMail($cert);
        $mail->sendMail($admin_address, '', '', $subject, $message, array(), array("system"));
    }

    /**
     * @param $cert srCertificate
     * @return string
     */
    protected function getCertDetailsForMail($cert){
        $message = "\n\n Certificate ID: " . $cert->getid();
        $message .= "\n User Login: " . $cert->getUser()->getLogin();
        $message .= "\n User Name: " . $cert->getUser()->getFullname();
        $message .= "\n File Name: " . $cert->getFilename();
        $message .= "\n File Version: " . $cert->getFileVersion();
        $message .= ilMail::_getInstallationSignature();
        return $message;
    }


    /**
     * @param boolean $disk_space_warning_sent
     */
    public static function setDiskSpaceWarningSent($disk_space_warning_sent)
    {
        self::$disk_space_warning_sent = $disk_space_warning_sent;
    }

    /**
     * @return boolean
     */
    public static function getDiskSpaceWarningSent()
    {
        return self::$disk_space_warning_sent;
    }


}
