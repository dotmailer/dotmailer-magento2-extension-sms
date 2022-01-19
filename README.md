# Dotdigital SMS for Magento 2
[![Packagist Version](https://img.shields.io/packagist/v/dotdigital/dotdigital-magento2-extension-sms?color=green&label=stable)](https://github.com/dotmailer/dotmailer-magento2-extension-sms/releases)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Overview
This module provides support for Transactional SMS notifications to Magento merchants. It automates SMS notifications on new order confirmation, order update, new shipment, shipment update and new credit memo.
  
## Requirements
- An active Dotdigital account with the SMS pay-as-you-go service enabled.
- Available from Magento 2.3+
- Requires Dotdigital extension versions:
  - `Dotdigitalgroup_Email` 4.14.0+
  
## Activation
- This module is included in all our metapackages. Please refer to [these instructions](https://github.com/dotmailer/dotmailer-magento2-extension#installation) to install via the Magento Marketplace.
- Ensure you have set valid API credentials in **Configuration > Dotdigital > Account Settings**
- Head to **Configuration > Dotdigital > Transactional SMS** for configuration.

## Credits
This module features an option to enable international telephone number validation. Our supporting code uses a version of the [International Telephone Input](https://github.com/jackocnr/intl-tel-input) JavaScript plugin. We've also borrowed some components from this [MaxMage Magento module](https://github.com/MaxMage/international-telephone-input). Kudos and thanks!

## Changelog

### 1.3.0

##### What's new
- This module has been renamed `dotdigital/dotdigital-magento2-extension-sms`.

##### Improvements
- We've added a new plugin to provide additional configuration values to our integration insight data cron.
- `setup_version` has been removed from module.xml; in the Dashboard, we now use composer.json to provide the current active module version.
- Menus and ACL resources are now translatable. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension-sms/pull/5)
- We replaced our use of a custom `DateIntervalFactory`, instead using the native `\DateInterval`.

##### Bug fixes
- Duplicate SMS sends were being queued on some setups; we’ve added checks in our observers to prevent this happening.
- Sends relating to order ids longer than 5 digits would be queued for order_id 65535. This has been fixed with a schema update.

### 1.2.1 

##### Bug fixes
- Duplicate SMS sends were being queued on some setups; we’ve added checks in our observers to prevent this happening.
- Sends relating to order ids longer than 5 digits would be queued for order_id 65535. This has been fixed with a schema update.

### 1.2.0

###### What’s new
- We've added extra form fields to allow merchants to select the sender's from name in SMS messages.

###### Improvements
- We updated the structure and default sort order of our SMS Sends Report grid.
- In phone number validation, all error codes now resolve to an error message.

### 1.1.1

###### Bug fixes
- We've added some extra code to prevent customers from submitting telephone numbers without a country code.
- We fixed the positioning of the tooltip that is displayed alongside each SMS message textarea in the admin.

### 1.1.0

###### Bug fixes
- Our mixin for `Magento_Ui/js/form/element/abstract` now returns an object. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension-sms/pull/2)
- Our `telephoneValidatorAddress` mixin now returns the correct widget type. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension-sms/pull/3)

### 1.0.0
  
###### What’s new
- SMS notifications for new order confirmation, order update, new shipment, shipment update and new credit memo.
- SMS sender cron script to process and send queued SMS.
- Phone number validation in the customer account and at checkout.
- 'SMS Sends' report.
