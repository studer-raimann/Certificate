Certificate
===========

This plugin offers an additional Certificate service for ILIAS.

Features
---------
* Multiple Certificate types/layouts
* Custom placeholders
* Multiple languages
* Certificates (pdf files) are stored and not always generated dynamically
* Revisions of certificate files
* Rendering the templates with the standard ILIAS PDF service or Jasper Reports (currently only JasperReport is implemented)

Dependencies
------------
CertificateEvent plugin

Installation
------------
Start at your ILIAS root directory
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/
git clone https://github.com/studer-raimann/Certificate.git
```
As ILIAS administrator go to "Administration > Plugins" and install/activate the plugin.
