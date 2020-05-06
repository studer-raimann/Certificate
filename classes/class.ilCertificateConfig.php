<?php

/**
 * Class ilCertificateConfig
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class ilCertificateConfig extends ActiveRecord
{

    const TABLE_NAME = 'uihkcertificate_c';

    const COURSE_TEMPLATES = 'course_templates';
    const COURSE_TEMPLATES_REF_IDS = 'course_templates_ref_ids';
    const TIME_FORMAT_UTC = 'time_format_utc';
    const MAX_DIFF_LP_SECONDS = 'max_diff_lp_seconds';
    const CALLBACK_EMAIL = 'callback_email';
    const JASPER_LOCALE = 'jasper_locale';
    const JASPER_JAVA_PATH = 'jasper_path_java';
    const NOTIFICATIONS_USER_SUBJECT = 'notification_user_subject';
    const NOTIFICATIONS_USER_BODY = 'notification_user_body';
    const NOTIFICATIONS_OTHERS_SUBJECT = 'notification_others_subject';
    const NOTIFICATIONS_OTHERS_BODY = 'notification_others_body';
    const DATE_FORMAT = 'str_format_date';
    const DATETIME_FORMAT = 'str_format_datetime';
    const PATH_HOOK_CLASS = 'path_hook_class';
    const ROLES_ADMINISTRATE_CERTIFICATE_TYPES = 'roles_administrate_certificate_types';
    const ROLES_ADMINISTRATE_CERTIFICATES = 'roles_administrate_certificates';
    const DISK_SPACE_WARNING = 'disk_space_warning';

    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }

    /**
     * @return string
     * @deprecated
     */
    public static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }

    /**
     * @var array
     */
    protected static $cache = array();
    /**
     * @var array
     */
    protected static $cache_loaded = array();
    /**
     * @var bool
     */
    protected $ar_safe_read = false;
    /**
     * @var string
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           128
     */
    protected $name;
    /**
     * @var string
     * @db_has_field        true
     * @db_fieldtype        clob
     */
    protected $value;

    /**
     * @param $name
     * @return string
     */
    public static function getX($name)
    {
        if (!isset(self::$cache_loaded[$name])) {
            $obj = self::find($name);
            if ($obj === null) {
                self::$cache[$name] = null;
            } else {
                self::$cache[$name] = $obj->getValue();
            }
            self::$cache_loaded[$name] = true;
        }

        return self::$cache[$name];
    }

    /**
     * @param $name
     * @param $value
     * @return null
     */
    public static function setX($name, $value)
    {
        /**
         * @var $obj arConfig
         */
        $obj = self::findOrGetInstance($name);
        $obj->setValue($value);
        if (self::where(array('name' => $name))->hasSets()) {
            $obj->update();
        } else {
            $obj->create();
        }
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}