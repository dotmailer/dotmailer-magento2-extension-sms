/* global define */
define([
    'jquery',
    'mage/utils/wrapper',
    'underscore'
], function ($, wrapper, _) {

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);

            if (!window.checkoutConfig.ddSmsTransactionalConsentExtensionAttributeAvailable) {
                return payload;
            }

            const hasProvidedTransactionalConsentInput =
                $('[name="dd_consent[dd_sms_transactional_consent_checkbox]"]');

            if (!hasProvidedTransactionalConsentInput.length || hasProvidedTransactionalConsentInput.is(':disabled')) {
                return payload;
            }

            /* eslint-disable camelcase */
            payload.addressInformation.extension_attributes = _.extend(
                payload.addressInformation.extension_attributes || {},
                {
                    dd_sms_transactional_consent_checkbox: hasProvidedTransactionalConsentInput.is(':checked')
                }
            );
            /* eslint-enable camelcase */
            return payload;
        });
    };
});
