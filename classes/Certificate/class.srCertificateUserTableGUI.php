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
    protected $columns = array('id', 'crs_title', 'valid_from', 'valid_to', 'file_version', 'cert_type');

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
     * @param array $a_set
     * @return ilAdvancedSelectionListGUI|null
     */
    protected function buildActions(array $a_set)
    {
        // Check that the definition allows for certificate download
        /** @var srCertificateDefinition $definition */
        $definition = srCertificateDefinition::find((int) $a_set['definition_id']);
        if ($definition && $definition->getDownloadable()) {
            return parent::buildActions($a_set);
        }

        return null;
    }

}