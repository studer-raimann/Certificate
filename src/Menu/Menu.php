<?php

namespace srag\Plugins\Certificate\Menu;

use ilCertificatePlugin;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\AbstractBaseItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticPluginMainMenuProvider;
use ILIAS\UI\Component\Symbol\Icon\Standard;
use ilUIPluginRouterGUI;
use srag\DIC\Certificate\DICTrait;
use srCertificateAdministrationGUI;
use srCertificateTypeGUI;
use srCertificateUserGUI;

/**
 * Class Menu
 *
 * @package srag\Plugins\Certificate\Menu
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class Menu extends AbstractStaticPluginMainMenuProvider
{

    use DICTrait;

    const PLUGIN_CLASS_NAME = ilCertificatePlugin::class;


    /**
     * @inheritDoc
     */
    public function getStaticTopItems() : array
    {
        return [
            $this->symbol($this->mainmenu->topParentItem($this->if->identifier(ilCertificatePlugin::PLUGIN_ID . "_top"))
                ->withTitle(self::plugin()->translate("certificates"))
                ->withAvailableCallable(function () : bool {
                    return self::plugin()->getPluginObject()->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return self::plugin()->getPluginObject()->isActive();
                }))
        ];
    }


    /**
     * @inheritDoc
     */
    public function getStaticSubItems() : array
    {
        $parent = $this->getStaticTopItems()[0];

        return [
            $this->symbol($this->mainmenu->link($this->if->identifier(ilCertificatePlugin::PLUGIN_ID . "_my_certificates"))
                ->withParent($parent->getProviderIdentification())
                ->withTitle(self::plugin()->translate("my_certificates"))
                ->withAction(self::dic()->ctrl()->getLinkTargetByClass([
                    ilUIPluginRouterGUI::class,
                    srCertificateUserGUI::class
                ]))
                ->withAvailableCallable(function () : bool {
                    return self::plugin()->getPluginObject()->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return (new srCertificateUserGUI())->checkPermission();
                })),
            $this->symbol($this->mainmenu->link($this->if->identifier(ilCertificatePlugin::PLUGIN_ID . "_admin_certificates"))
                ->withParent($parent->getProviderIdentification())
                ->withTitle(self::plugin()->translate("admin_certificates"))
                ->withAction(self::dic()->ctrl()->getLinkTargetByClass([
                    ilUIPluginRouterGUI::class,
                    srCertificateAdministrationGUI::class
                ]))
                ->withAvailableCallable(function () : bool {
                    return self::plugin()->getPluginObject()->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return (new srCertificateAdministrationGUI())->checkPermission();
                })),
            $this->symbol($this->mainmenu->link($this->if->identifier(ilCertificatePlugin::PLUGIN_ID . "_certificate_types"))
                ->withParent($parent->getProviderIdentification())
                ->withTitle(self::plugin()->translate("certificate_types"))
                ->withAction(self::dic()->ctrl()->getLinkTargetByClass([
                    ilUIPluginRouterGUI::class,
                    srCertificateTypeGUI::class
                ]))
                ->withAvailableCallable(function () : bool {
                    return self::plugin()->getPluginObject()->isActive();
                })
                ->withVisibilityCallable(function () : bool {
                    return (new srCertificateTypeGUI())->checkPermission();
                }))
        ];
    }


    /**
     * @param AbstractBaseItem $entry
     *
     * @return AbstractBaseItem
     */
    protected function symbol(AbstractBaseItem $entry) : AbstractBaseItem
    {
        if (self::version()->is6()) {
            $entry = $entry->withSymbol(self::dic()->ui()->factory()->symbol()->icon()->standard(Standard::CERT, ilCertificatePlugin::PLUGIN_NAME)->withIsOutlined(true));
        }

        return $entry;
    }
}
