<?php
/**
 * srCertificateSignatureDefinition
 *
 * AR class to connect a signature to a definition
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 * @version
 */
class srCertificateSignatureDefinition extends ActiveRecord
{

    /**
     * MySQL Table-Name
     */
    const TABLE_NAME = 'cert_signature_def';

    /**
     * @var int ID of srCertificateDefinition
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     * @db_is_primary   true
     */
    protected $definition_id = 0;

    /**
     * @var int ID of srCertificateSignature
     *
     * @db_has_field    true
     * @db_fieldtype    integer
     * @db_length       8
     */
    protected $signature_id;


    /**
     * @return string
     * @description Return the Name of your Database Table
     * @deprecated
     */
    static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }

    /**
     * @param int $definition_id
     */
    public function setDefinitionId($definition_id)
    {
        $this->definition_id = $definition_id;
    }

    /**
     * @return int
     */
    public function getDefinitionId()
    {
        return $this->definition_id;
    }

    /**
     * @param int $signature_id
     */
    public function setSignatureId($signature_id)
    {
        $this->signature_id = $signature_id;
    }

    /**
     * @return int
     */
    public function getSignatureId()
    {
        return $this->signature_id;
    }

    public function getId(){
        return $this->getDefinitionId();
    }



}
