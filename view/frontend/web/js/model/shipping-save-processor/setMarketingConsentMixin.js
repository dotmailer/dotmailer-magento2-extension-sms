define([
    'jquery',
    'mage/utils/wrapper',
    'underscore'
], function ($, wrapper, _) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);

            const MarketingConsentCheckbox = $('[name="dd_consent[dd_sms_marketing_consent_checkbox]"]');
            const MarketingConsentTelephoneInput = $('[name="dd_consent[dd_sms_marketing_consent_telephone]"]');

            if(MarketingConsentCheckbox.is(':disabled')) {
                return payload;
            }

            /* eslint-disable camelcase */
            payload.addressInformation.extension_attributes = _.extend(
                payload.addressInformation.extension_attributes || {},
                {
                    dd_sms_marketing_consent_checkbox: MarketingConsentCheckbox.is(':checked'),
                    dd_sms_marketing_consent_telephone: MarketingConsentTelephoneInput.val(),
                }
            );
            /* eslint-enable camelcase */
            return payload;
        });
    };
});
