# Nuvei Plugin for OpenCart 3 with Simply Connect

## Description
Nuvei supports major international credit and debit cards enabling you to accept payments from your global customers. 

A wide selection of region-specific payment methods can help your business grow in new markets. Other popular payment methods, from mobile payments to e-wallets, can be easily implemented on your checkout page.

The correct payment methods at the checkout page can bring you global reach, help you increase conversions, and create a seamless experience for your customers.

## System Requirements
- OpenCart v3.x.x.x  
- Working PHP cURL module

## Nuvei Requirements
- Enabled DMNs into merchant settings.  
- Whitelisted plugin endpoint so the plugin can receive the DMNs. 
- On SiteID level "DMN  timeout" setting is recommendet to be not less than 20 seconds, 30 seconds is better.  
- If using the Rebilling plugin functionality, please provide the DMN endpoint to the Integration and Technical Support teams, so it can be added to the merchant configuration.

## Manual Installation
1. Download the plugin from Release page or form main branch.
1. Extract the archive and upload the folders "admin", "catalog" and "system" to the OC install root directory.
2. Check the version in "install.xml" and compare it with the version of installed Nuvei Modification if any. If the installed version is lower than the xml version, continue to the next step.
3. Zip the install.xml file and rename it to 'some_name.ocmod.zip'.
4. In the admin site, go to Extensions > Installer and upload the *.ocmod.zip file.
5. After successful installation, go to Modifications and refresh the cache with the blue button at the top right of the page.

## Documentations
For more information, please check the plugin [guide](https://docs.nuvei.com/documentation/plugins-docs/open-cart/).

## Support
Please contact our Technical Support (tech-support@nuvei.com) for any questions and difficulties.
