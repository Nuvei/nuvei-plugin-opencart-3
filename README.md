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
1. If you have installed version of the plugin before v2.0:
  1.1. From Extensions > Modifications disable Nuvei modification, then Clear and Refresh from the buttons.
  1.2. From Extensions > Extensions find Nuvei Checkout and uninstall it.
2. Download the plugin from Release page or from main branch.
3. Extract the archive and upload the folders "admin", "catalog" and "system" to the OC install root directory.
5. Install again Nuvei Checkout. Then in plugin' settings "Help Tools" check if the version is correct, also check in Extensions > Events for events starting with "nuvei_".

## Documentations
For more information, please check the plugin [guide](https://docs.nuvei.com/documentation/plugins-docs/open-cart/).

## Support
Please contact our Technical Support (tech-support@nuvei.com) for any questions and difficulties.
