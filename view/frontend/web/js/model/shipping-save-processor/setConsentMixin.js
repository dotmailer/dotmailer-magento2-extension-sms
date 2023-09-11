define([
    'jquery',
    'mage/utils/wrapper',
    'underscore'
], function ($, wrapper, _) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);

            let hasProvidedConsent = $('[name="dd_consent[dd_sms_consent_checkbox]"]').is(':checked'),
             consentTelephone = $('[name="dd_consent[dd_sms_consent_telephone]"]').val();

            /* eslint-disable camelcase */
            payload.addressInformation.extension_attributes = _.extend(
                payload.addressInformation.extension_attributes || {},
                {
                    dd_sms_consent_checkbox: hasProvidedConsent,
                    dd_sms_consent_telephone: consentTelephone
                }
            );
            /* eslint-enable camelcase */
            return payload;
        });
    };
});
