<#1>
	<?php
	require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');
	$pl = new ilCertificatePlugin();
	$conf = $pl->getConfigObject();
	$conf->initDB();
	?>
<#2>
    <?php
    /*
     * Create tables
     */
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateType.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Placeholder/class.srCertificatePlaceholder.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Placeholder/class.srCertificatePlaceholderValue.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinitionSetting.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateTypeSetting.php');

    srCertificateType::installDB();
    srCertificateDefinition::installDB();
    srCertificatePlaceholder::installDB();
    srCertificatePlaceholderValue::installDB();
    srCertificate::installDB();
    srCertificateTypeSetting::installDB();
    srCertificateDefinitionSetting::installDB();
?>
<#3>
    <?php
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Certificate/class.srCertificate.php');
    srCertificate::updateDB();
    ?>
<#4>
    <?php

    /*
    * Add new setting "notification_user" to certificate types and every existing definitions
    */

    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Definition/class.srCertificateDefinition.php');
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/Type/class.srCertificateType.php');

    $types = srCertificateType::get();
    /** @var srCertificateType $type */
    foreach ($types as $type) {
        if (is_null($type->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER))) {
            $setting = new srCertificateTypeSetting();
            $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);
            $setting->setDefaultValue(0);
            $setting->setEditableIn($type->getAvailableObjects());
            $setting->setTypeId($type->getId());
            $setting->create();
        }
    }

    $definitions = srCertificateDefinition::get();
    /** @var srCertificateDefinition $def */
    foreach ($definitions as $def) {
        if (is_null($def->getSettingByIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER))) {
            $setting = new srCertificateDefinitionSetting();
            $setting->setIdentifier(srCertificateTypeSetting::IDENTIFIER_NOTIFICATION_USER);
            $setting->setValue(0);
            $setting->setDefinitionId($def->getId());
            $setting->create();
        }
    }

    ?>
<#5>
    <?php
    /*
     * Add default values for new config settings
     */
    require_once('./Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/Certificate/classes/class.ilCertificatePlugin.php');
    $pl = new ilCertificatePlugin();
    $conf = $pl->getConfigObject();
    $conf->setValue('str_format_date', ilCertificatePlugin::DEFAULT_DATE_FORMAT);
    $conf->setValue('str_format_datetime', ilCertificatePlugin::DEFAULT_DATETIME_FORMAT);
    $conf->setValue('path_hook_class', ilCertificatePlugin::DEFAULT_PATH_HOOK_CLASS);
    $conf->initDB();
    ?>