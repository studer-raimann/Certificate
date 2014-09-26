#Certificate

This plugin offers an additional Certificate service for ILIAS.

##Features

* Define multiple certificate types/layouts
* Custom placeholders in certificates
* Multiple languages
* Certificates (pdf files) are stored in the ILIAS data directory instead of getting generated dynamically
* Revision of files
* Rendering PDF certificates with the integraded PDF Service in ILIAS (>= 4.4) or with JasperReports

##Installation

Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
git clone https://github.com/studer-raimann/Certificate.git
```
Do not install or activate the plugin in the ILIAS Administration before having installed the following dependencies.

###Dependencies

The following plugin and services are needed in order to run the certificate plugin. Please make sure to install the mandatory plugins and services before installing the Certificate plugin.

**Mandatory**

* CertificateEvents (https://github.com/studer-raimann/CertificateEvents)
* *ILIAS < 4.5* ActiveRecord (https://github.com/studer-raimann/ActiveRecord)
* *ILIAS < 4.5* Router Service (https://github.com/studer-raimann/RouterService)
* *ILIAS <= 4.4* Jasper Report (https://github.com/studer-raimann/JasperReport)

In ILIAS >= 4.5, the Router and ActiveRecord service is already included in the core. The Jasper Report service is needed if the PDF files should be rendered with JasperReport. As an alternative in ILIAS >= 4.4, the integrated PDF service can be used. We recommend to use JasperSoft because it offers superior possibilites to create pretty certificate layouts. They can be for example generated with the "JasperSoft Studio" application, please visit https://community.jaspersoft.com/ for more informations.

**Optional**
* CtrlMainMenu (https://github.com/studer-raimann/CtrlMainMenu)

The CtrlMainMenu plugin allows you to modify the ILIAS menu and add custom menu entries. This plugin can be used to generate a main menu entry which links to the administration of certificate types.

### Patches

// TODO

Documentation
-------------

// TODO

