<?php


/**
 * Class srCertificateUserTableGUI
 *
 * @author Stefan Wanzenried <sw@studer-raimann.ch>
 */
class srCertificateUserTableGUI extends srCertificateTableGUI
{

    /**
     * The user from whom the certificates are displayed
     *
     * @var ilObjUser
     */
    protected $_user;

    /**
     * @var array
     */
    protected $columns = array('id', 'crs_title', 'valid_from', 'valid_to', 'file_version', 'cert_type', 'status');

    /**
     * @param $a_parent_obj
     * @param string $a_parent_cmd
     * @param ilObjUser $user The user from whom the certificates are displayed
     * @param array $options
     */
    public function __construct($a_parent_obj, $a_parent_cmd = "", ilObjUser $user, array $options = array())
    {
        $this->_user = $user;
        $options['user_id'] = $user->getId();
        $options['columns'] = isset($options['columns']) ? $options['columns'] : $this->columns;
        parent::__construct($a_parent_obj, $a_parent_cmd, $options);
    }

    /**
     * @param $a_set
     * @return bool
     */
    protected function hasAction($a_set) {
        $definition = srCertificateDefinition::find((int) $a_set['definition_id']);
        return $definition && $definition->getDownloadable() && parent::hasAction($a_set);
    }

}
