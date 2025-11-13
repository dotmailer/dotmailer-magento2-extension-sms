### 2.7.3

#### Bug fixes

- We have fixed a race condition that could occur at checkout when saved addresses take longer than expected to load.

### 2.7.2

#### Improvements

- We added "SMS Subscriber Enabled" and "Add SMS Subscribers To" to Integration Insight data.

#### Bug fixes

- Our telephone input validator was loading at checkout even if the setting was disabled; this has been fixed.
- Updated telephone numbers for SMS subscribers will now be published to Dotdigital even without a subscription status change.
- We prevented a potential fatal error when subscribing to SMS while the main Dotdigital connector was disabled.
- The on-blur behaviour of the SMS consent telephone input is now aligned with other telephone inputs.

### 2.7.1

##### Bug fixes
- We fixed a console error related to the SMS consent telephone input at checkout.
- The enabled check in the NewShipmentRestPlugin now checks at website level.

### 2.7.0

##### What's new
- We added a new plugin to send New Shipment transactional SMS for merchants creating shipments via the REST API.

##### Bug fixes
- We fixed an issue with phone numbers containing dashes being stored incorrectly in `email_contact`.

### 2.6.2

##### Improvements
- `{{ entity_id }}` is added to the available template variables for transactional SMS.
- We removed implicitly null types for compatibility with PHP 8.4.
- We fixed some MFTF test annotation errors.

### 2.6.1

##### Improvements
- A list for SMS Subscribers is now created when merchants run ‘Setup Integration’ on the admin Accounts page.

### 2.6.0

##### Improvements
- Consent can now only be enabled if consent text is provided.
- We removed an overloaded method in the `RestoreEmailContactTableSmsSubscribers` data migration.

##### Bug fixes
- We fixed a DI compile error on PHP 7.4.

### 2.5.0

##### What's new
- We have added an `isRequired()` method to the `TelephoneCheckoutConfig` class for use in Hyva Checkout.

##### Improvements
- Keywords are no longer listed as possible 'From name' originators for transactional SMS.
- The `ddg_automation_sms` cron job now schedules ahead for 10 minutes.

### 2.4.0

##### What's new
- SMS subscribes and unsubscribes are now handled using message queues.

##### Improvements
- All observer code is wrapped in try/catch statements.
- We added a plugin to allow SMS Subscriber bulk imports to be processed after reset.

##### Bug fixes
- We added an extra check that a contact exists before calling methods on it.

### 2.3.3

##### Improvements
- The telephone input for consent at checkout now loads with the correct config.

### 2.3.2

##### Improvements
- We added some missing configs to the Integration Insights data export for this module.

##### Bug fixes
- We now properly handle when address telephone numbers are not required, and not submitted, at checkout.
- We fixed an issue with incorrect scope when triggering transactional SMS sends from the admin.

### 2.3.1

##### What's new
- The consent telephone input at checkout now accurately mirrors values set in the shipping telephone input.

### 2.3.0

##### What's new
- We made some templating changes to support our SMS Hyvä compatibility module.

### 2.2.2

##### Bug fixes
- We fixed telephone input validation at checkout in Magento 2.4.7.

### 2.2.1

##### Bug fixes
- Our requirement for `psr/log` has been updated for Magento 2.4.7.

### 2.2.0

##### What's new
- SMS consent can now be enabled in different contexts (in account registration and at checkout).
- We added marketing message templates for SMS Sign-up and New Account SMS Sign-Up.
- We added a template variable to allow sending of generated coupon codes in marketing SMS.
- The `email_sms_order_queue` table has been renamed to `email_sms_message_queue`.

##### Improvements
- We moved some job checking code from the cron process into an admin controller.

### 2.1.0

##### What's new
- Merchants can now activate and test Transactional SMS on Dotdigital sandbox accounts.

### 2.0.1

##### Improvements
- We updated our customer register observer to allow for cases when the `account_controller` arg has not been passed.

### 2.0.0

##### Bug fixes
- We updated our `customer_account_create.xml` layout to remove a hard dependency on `Magento_LoginAsCustomerAssistance`.
- We restored a missing default value for the SMS Subscribers list.
- We fixed a bug with broken checkouts for orders without a status.
- We fixed a JS bug at checkout relating to our `telephoneValidatorCheckout` mixin.

### 1.7.2

##### Improvements
- Merchants will now be prevented from using the same list for SMS subscriber sync that they have used to sync customers, subscribers or guests.

### 1.7.1

##### Bug fixes
- `NewsletterManageIndexPlugin` has been changed to an 'after' plugin.

### 1.7.0

##### Improvements
- We fixed all outstanding PHPStan errors (level 2).
- We refactored some controller classes (and controller plugins) to replace usage of `$this→getRequest()`.

### 1.6.0

##### What's new
- Customers and guests can now subscribe using mobile numbers at registration and checkout.
- Customers can manage their subscription and subscribed mobile number in their account.
- Magento admins can manage customer SMS subscriptions and mobile numbers in the customer admin view.
- Consent can be captured for SMS subscriptions.
- SMS subscribers are synced to a specific list in Dotdigital via the new V3 API.
- SMS subscribers who unsubscribe or resubscribe outside of Magento will have their subscription status updated in Magento.
- The module now requires PHP 7.4+ and Magento 2.3.7+.

##### Improvements
- The module's code has been fully aligned with Magento's latest coding standards.
- We've removed some Magento license headers from places in our code.

##### Bug fixes
- We fixed a problem with the character counter for transactional SMS templates.
- We fixed an action group reference in the TelephoneNumberPreValidationTest MFTF test.

### 1.5.0

##### Improvements
- We replaced usages of `SearchResultsFactory` and `SearchResults` classes with `SearchResultsInterfaceFactory` and `SearchResultsInterface` respectively.

### 1.4.2

##### Bug fixes
- Order notifications for virtual products will now fall back to the billing address phone number in the absence of a shipping address phone number.
- We spotted an issue with the phone number field appearing twice at checkout; this has been fixed.

### 1.4.1

##### Bug fixes
- We fixed a CSS error that displayed incorrect country flags in the telephone input selector.

### 1.4.0

##### What's new
- If phone number validation is enabled, customers with stored, non-international phone numbers will be asked to update their phone number at checkout.

##### Improvements
- We updated the flag PNG files for the telephone input country selector.

### 1.3.2

##### Improvements
- We’ve updated our code for compatibility with PHP 8.1.
- PHP 7.2 is now a minimum requirement for running this module.

### 1.3.1

##### Bug fixes
- We fixed an incorrect class import in one of our unit tests.

### 1.3.0

##### What's new
- This module has been renamed `dotdigital/dotdigital-magento2-extension-sms`.

##### Improvements
- We've added a new plugin to provide additional configuration values to our integration insight data cron.
- `setup_version` has been removed from module.xml; in the Dashboard, we now use composer.json to provide the current active module version.
- Menus and ACL resources are now translatable. [External contribution](https://github.com/dotmailer/dotmailer-magento2-extension-sms/pull/5)
- We replaced our use of a custom `DateIntervalFactory`, instead using the native `\DateInterval`.

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
