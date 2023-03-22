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

            let hasProvidedConsent = $('[name="dd_consent[dd_sms_consent_checkbox]"]').is(':checked');
            let consentTelephone = $('[name="dd_consent[dd_sms_consent_telephone]"]').val();

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
