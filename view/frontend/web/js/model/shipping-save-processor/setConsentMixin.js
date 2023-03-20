/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper'
], function ($, wrapper) {
    'use strict';

    return function (payloadExtender) {

        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);

            var hasProvidedConsent = $('[name="custom_attributes[dd_sms_consent_checkbox]"]').is(':checked');
            var consentTelephone = $('[name="custom_attributes[dd_sms_consent_telephone]"]').val();

            payload.addressInformation.extension_attributes = _.extend(
                payload.addressInformation.extension_attributes || {},
                {
                    dd_sms_consent_checkbox: hasProvidedConsent,
                    dd_sms_consent_telephone: consentTelephone
                }
            );

            return payload;
        });
    };
});
