# Dotdigital SMS for Magento 2 (Magento Open Source and Adobe Commerce)
[![Packagist Version](https://img.shields.io/packagist/v/dotdigital/dotdigital-magento2-extension-sms?color=green&label=stable)](https://github.com/dotmailer/dotmailer-magento2-extension-sms/releases)
[![license](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE.md)

## Overview
This module provides support for Transactional SMS notifications to Magento merchants. It automates SMS notifications on new order confirmation, order update, new shipment, shipment update and new credit memo.
  
## Requirements
- An active Dotdigital account with the SMS pay-as-you-go service enabled.
- Dotdigital extension versions:
  - `Dotdigitalgroup_Email` 4.23.2+
- PHP 7.4+
- Magento 2.3.7+
  - Magento 2.3.0-2.3.6 are compatible up to version 1.5.x

## Version history
Please see our [Changelog](CHANGELOG.md) or the [Releases](https://github.com/dotmailer/dotmailer-magento2-extension-sms/releases) page.

## Activation
- This module is included in our core extension. Please refer to [these instructions](https://github.com/dotmailer/dotmailer-magento2-extension#installation) to install via the Magento Marketplace.
- Ensure you have set valid API credentials in **Configuration > Dotdigital > Account Settings**
- Head to **Configuration > Dotdigital > Transactional SMS** for configuration.

## CLI
- This module provides a CLI command to sync historical SMS subscribers to Dotdigital.
- Run `bin/magento dotdigital:sync SmsSubscriber`.
- Normally, the module uses message queues to subscribe and unsubscribe SMS subscribers. This command is only necessary if you reset your data in the case of running `dotdigital:migrate` or switching Dotdigital accounts.

## Credits
This module features an option to enable international telephone number validation. Our supporting code uses a version of the [International Telephone Input](https://github.com/jackocnr/intl-tel-input) JavaScript plugin. We've also borrowed some components from this [MaxMage Magento module](https://github.com/MaxMage/international-telephone-input). Kudos and thanks!
