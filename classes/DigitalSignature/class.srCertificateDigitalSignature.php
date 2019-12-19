<?php
/**
 * Class srCertificateDigitalSignature
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class srCertificateDigitalSignature {

    const KEYTYPE_PRIVATE = 'private';
    const KEYTYPE_PUBLIC = 'public';

    const KEY_PATH = 'cert_keys';
    const PRIVATE_KEY_FILE = 'cert_sig.key';
    const PUBLIC_KEY_FILE = 'cert_sig.pub';

    /**
     * @param $key_type
     * @return string
     * @throws ilException
     */
    public static function getPathOf($key_type) {
        $data_dir = ILIAS_DATA_DIR . DIRECTORY_SEPARATOR . CLIENT_ID . DIRECTORY_SEPARATOR . self::KEY_PATH . DIRECTORY_SEPARATOR;
        switch ($key_type) {
            case self::KEYTYPE_PRIVATE:
                return $data_dir . self::PRIVATE_KEY_FILE;
            case self::KEYTYPE_PUBLIC:
                return $data_dir . self::PUBLIC_KEY_FILE;
            default:
                throw new ilException('Invalid keytype: ' . $key_type);
        }
    }

    /**
     * @param $data
     * @return string
     */
    public static function encryptData($data) {
        $key = openssl_get_privatekey('file://' . self::getPathOf(self::KEYTYPE_PRIVATE));
        openssl_private_encrypt($data, $encrypted, $key);
        return base64_encode($encrypted);
    }

    /**
     * @param $signature
     * @return mixed
     */
    public static function decryptSignature($signature) {
        $key = openssl_get_publickey('file://' . self::getPathOf(self::KEYTYPE_PUBLIC));
        openssl_public_decrypt(base64_decode($signature), $decrypted, $key);
        return $decrypted;
    }


    /**
     * @param $cert srCertificate
     *
     * @return string
     */
    public static function getSignatureForCertificate($cert)
    {
        $second_part = " - "
            . $cert->getValidFrom() . " - "
            . ($cert->getUser()->getLastname() == "" ? "" : $cert->getUser()->getLastname() . ", ")
            . $cert->getUser()->getFirstname();
        $allowed_name_length = 244 - strlen($second_part);
        $course_name = ilObjCourse::_lookupTitle(ilObjCourse::_lookupObjectId($cert->getDefinition()->getRefId()));
        if (strlen($course_name) > $allowed_name_length) {
            $course_name = substr($course_name, 0, $allowed_name_length - 3) . "...";
        }
        $data = $course_name . $second_part;

        return self::encryptData($data);
    }

}