<?php

/**
 * srCertificateTemplateTypeFactory
 *
 * @author  Stefan Wanzenried <sw@studer-raimann.ch>
 * @version
 */
class srCertificateTemplateTypeFactory
{

    protected static $object_cache = array();

    /**
     * Get the concrete TemplateType class of a known id
     *
     * @param $id
     * @return null|srCertificateTemplateTypeJasper|srCertificateTemplateTypeHtml
     * @throws Exception
     */
    public static function getById($id)
    {
        if (isset(self::$object_cache[$id])) {
            return self::$object_cache[$id];
        } else {
            $object = NULL;
            switch ($id) {
                case srCertificateTemplateType::TEMPLATE_TYPE_JASPER:
                    $object = new srCertificateTemplateTypeJasper();
                    break;
                case srCertificateTemplateType::TEMPLATE_TYPE_HTML:
                    $object = new srCertificateTemplateTypeHtml();
                    break;
                default:
                    throw new Exception("Unrecognized template type: Template type with id $id not implemented");
            }
            self::$object_cache[$id] = $object;
            return $object;
        }
    }

}
