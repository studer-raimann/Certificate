<?php
require_once('./Modules/Course/classes/class.ilObjCourse.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('./Services/Tracking/classes/class.ilLearningProgress.php');
require_once("./Services/Tracking/classes/class.ilTrQuery.php");
require_once("./Services/Tracking/classes/class.ilLPStatusFactory.php");
require_once(dirname(dirname(__FILE__)) . '/Certificate/class.srCertificate.php');
require_once(dirname(dirname(__FILE__)) . '/class.ilCertificatePlugin.php');

/**
 * srCertificateStandardPlaceholder
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateStandardPlaceholders
{

    /**
     * Available Standard placeholders with identifier => description
     * @var array
     */
    protected static $placeholders = array(
        'USER_LOGIN' => 'Login',
        'USER_FULLNAME' => 'Full name of the user (title, firstname and lastname)',
        'USER_FIRSTNAME' => 'First name of the user',
        'USER_LASTNAME' => 'Last name of the user',
        'USER_TITLE' => 'Title of the user',
        'USER_BIRTHDAY' => 'Birthday of the user',
        'USER_INSTITUTION' => 'Institution of the user',
        'USER_DEPARTMENT' => 'Department of the user',
        'USER_STREET' => "Street of the user's address",
        'USER_CITY' => "City of the user's address",
        'USER_ZIPCODE' => "ZIP code of the user's address",
        'USER_COUNTRY' => "Country of the user's address",
        'TIMESTAMP' => 'Current date in milliseconds since 01.01.1970',
        'DATE' => 'Actual date',
        'DATETIME' => 'Actual date and time',
        'DATE_COMPLETED' => 'Date of completion',
        'DATETIME_COMPLETED' => 'Date and time of completion',
        'CERT_VALID_FROM' => 'From validity date of certificate',
        'CERT_VALID_TO' => 'To validity date of certificate',
        'CERT_ID' => 'Unique numerical ID of certificate',
        'COURSE_TITLE' => 'Title of course',
        'LP_FIRST_ACCESS' => 'Learning progress first access',
        'LP_LAST_ACCESS' => 'Learning progress last access',
        'LP_SPENT_SECONDS' => 'Learning progress time spent in course (seconds)',
        'LP_READ_COUNT' => 'Read count',
        'LP_STATUS' => 'Status code',
        'LP_AVG_PERCENTAGE' => 'Avg. percentage of course',
        'CERT_TEMPLATE_PATH' => 'Path where certificate template file and assets are stored'
    );


    /**
     * @var srCertificate
     */
    protected $cert;

    /**
     * @var ilCertificatePlugin
     */
    protected $pl;

    /**
     * Cache for the parsed placeholders
     * @var array
     */
    protected $parsed_placeholders;


    /**
     * @param srCertificate $cert
     */
    public function __construct(srCertificate $cert)
    {
        $this->cert = $cert;
        $this->pl = new ilCertificatePlugin();
    }


    /**
     * Get all the standard identifiers
     * @return array
     */
    public static function getPlaceholderIdentifiers()
    {
        return array_keys(self::$placeholders);
    }

    /**
     * Check if a given identifier is already reserved
     *
     * @param $identifier
     * @return bool
     */
    public static function isReservedIdentifier($identifier)
    {
        $identifiers = self::getPlaceholderIdentifiers();
        return in_array($identifier, $identifiers);
    }

    /**
     * @return array
     */
    public static function getStandardPlaceholders()
    {
        // TODO i18n
        return self::$placeholders;
    }

    /**
     * Return array containing the parsed (evaluated) standard placeholders
     *
     * @return array
     */
    public function getParsedPlaceholders()
    {
        if (!is_null($this->parsed_placeholders)) {
            return $this->parsed_placeholders;
        }

        $user = $this->cert->getUser();
        $cert = $this->cert;
        $utc = $this->pl->getConfigObject()->getValue('time_format_utc');

        // Initialize with empty values
        $this->parsed_placeholders = array();
        foreach (self::$placeholders as $k => $v) {
            $this->parsed_placeholders[$k] = '';
        }
        // TODO ATM only supported for courses, needs adjustments if plugin could be used for other object types
        /** @var ilObjCourse $course */
        $course = new ilObjCourse($cert->getDefinition()->getRefId());
        // TODO Add custom fields from course as placeholders

        // Build some Learning Progress information
        $passed_datetime = ilCourseParticipants::getDateTimeOfPassed($course->getId(), $user->getId());
        $lp_fields = array('first_access', 'last_access', 'percentage', 'status', 'read_count', 'spent_seconds', 'childs_spent_seconds');
        $lp_data = ilTrQuery::getObjectsDataForUser($user->getId(), $course->getId(), $course->getRefId(), '', '', 0, 9999, null, $lp_fields);
        $lp_avg = $this->buildAvgPercentageOfCourseObjects($lp_data);
        $lp_crs = array();
        $max_last_access = null;
        foreach ($lp_data['set'] as $v) {
            if ($v['type'] == 'crs') {
                $lp_crs = $v;
                $lp_crs['first_access'] = strtotime($v['first_access']); // First access is not stored as UNIX timestamp...
            }
            if ($v['last_access'] > $max_last_access) {
                $max_last_access = $v['last_access'];
            }
        }
        $lp_crs['last_access'] = $max_last_access;
        $lp_spent_time = $this->buildLpSpentTime($lp_crs);

        $this->parsed_placeholders = array_merge($this->parsed_placeholders,
            array(
                'USER_LOGIN' => $user->getLogin(),
                'USER_TITLE' => $user->getTitle(),
                'USER_FULLNAME' => $user->getFullname(),
                'USER_FIRSTNAME' => $user->getFirstname(),
                'USER_LASTNAME' => $user->getLastname(),
                'USER_BIRTHDAY' => $user->getBirthday(),
                'USER_INSTITUTION' => $user->getInstitution(),
                'USER_DEPARTMENT' => $user->getDepartment(),
                'USER_STREET' => $user->getStreet(),
                'USER_CITY' => $user->getCity(),
                'USER_ZIPCODE' => $user->getZipcode(),
                'USER_COUNTRY' => $user->getCountry(),
                'DATE' => $this->formatDate('DATE'),
                'DATETIME' => $this->formatDateTime('DATETIME'),
                'TIMESTAMP' => ($utc) ? strtotime(gmdate('Y-m-d H:i:s')) : time(),
                'DATE_COMPLETED' => $this->formatDate('DATE_COMPLETED', strtotime($passed_datetime)),
                'DATETIME_COMPLETED' => $this->formatDateTime('DATETIME_COMPLETED', strtotime($passed_datetime)),
                'CERT_VALID_FROM' => $this->formatDate('CERT_VALID_FROM', strtotime($cert->getValidFrom())),
                'CERT_VALID_TO' => $this->formatDate('CERT_VALID_TO', strtotime($cert->getValidTo())),
                'CERT_ID' => $cert->getId(),
                'COURSE_TITLE' => $course->getTitle(),
                'LP_FIRST_ACCESS' => $this->formatDateTime('LP_FIRST_ACCESS', (int)$lp_crs['first_access']),
                'LP_LAST_ACCESS' => $this->formatDateTime('LP_LAST_ACCESS', (int)$lp_crs['last_access']),
                'LP_SPENT_SECONDS' => $lp_crs['childs_spent_seconds'],
                'LP_SPENT_TIME' => $lp_spent_time,
                'LP_READ_COUNT' => $lp_crs['read_count'],
                'LP_STATUS' => $lp_crs['status'],
                'LP_AVG_PERCENTAGE' => $lp_avg,
                'CERT_TEMPLATE_PATH' => $this->cert->getDefinition()->getType()->getCertificateTemplatesPath(),
            )
        );
        return $this->parsed_placeholders;
    }

    /**
     * Helper function to get a formatted date.
     * This method checks the if the date should be calculated in GMT or UTC.
     * In addition, all values are passed to the hooks class allowing for custom modification.
     *
     * @param identifier
     * @param $timestamp
     * @return string
     */
    protected function formatDate($identifier, $timestamp = 0)
    {
        $timestamp = ($timestamp) ? $timestamp : time();
        $config = $this->pl->getConfigObject();
        $utc = $config->getValue('time_format_utc');
        $format = $config->getValue('str_format_date');
        // Check if a hook wants to modify the date format
        $format_custom = $this->pl->getHooks()->formatDate($this->cert, $identifier);
        if ($format_custom) {
            $format = $format_custom;
        }
        $value = ($utc) ? gmdate($format, $timestamp) : date($format, $timestamp);
        return $value;
    }

    /**
     * See formatDate() method
     *
     * @param $identifier
     * @param int $timestamp
     * @return string
     */
    protected function formatDateTime($identifier, $timestamp = 0)
    {
        $timestamp = ($timestamp) ? $timestamp : time();
        $config = $this->pl->getConfigObject();
        $utc = $config->getValue('time_format_utc');
        $format = $config->getValue('str_format_datetime');
        // Check if a hook wants to modify the date format
        $format_custom = $this->pl->getHooks()->formatDate($this->cert, $identifier);
        if ($format_custom) {
            $format = $format_custom;
        }
        $value = ($utc) ? gmdate($format, $timestamp) : date($format, $timestamp);
        return $value;
    }


    /**
     * Build a time string based on the spent seconds in the course
     *
     * @param $lp_data
     * @return string
     */
    protected function buildLpSpentTime($lp_data)
    {
        $seconds = (int)$lp_data['childs_spent_seconds'];
        $hours = (string)(floor($seconds / 3600));
        $mins = (string)(floor(($seconds - ($hours * 3600)) / 60));
        $secs = (string)(floor($seconds % 60));
        if (strlen($hours) == 1) $hours = '0' . $hours;
        if (strlen($mins) == 1) $mins = '0' . $mins;
        if (strlen($secs) == 1) $secs = '0' . $secs;
        $lp_spent_time = "$hours:$mins:$secs";
        return $lp_spent_time;
    }

    /**
     * Build average percentage of objects in course
     *
     * @param array $lp_data
     * @return float|null
     */
    protected function buildAvgPercentageOfCourseObjects(array $lp_data)
    {
        $count_objects = (int)$lp_data['cnt'];
        $avg = 0;
        $count_avg = 0;
        $return = null;
        if ($count_objects > 1) {
            // Course itself is in pos 0 so we count from pos 1
            for ($i = 0; $i < $count_objects; $i++) {
                // If there was no access to the object, don't count percentage
                if (is_null($lp_data['set'][$i]['first_access'])) {
                    continue;
                }
                // Don't count the course
                if ($lp_data['set'][$i]['type'] == 'crs') {
                    continue;
                }
                $avg += (int)$lp_data['set'][$i]['percentage'];
                $count_avg++;
            }
            $return = ($count_avg) ? $avg / ($count_avg) : null;
        }
        return $return;
    }

}