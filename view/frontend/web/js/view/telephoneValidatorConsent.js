define([
    'jquery',
    'mage/utils/wrapper',
    'Dotdigitalgroup_Sms/js/model/telephoneValidation',
    'Dotdigitalgroup_Sms/js/view/consentCheckoutForm'
], function ($, wrapper, validate, consentFormUiClass) {
    'use strict';

    var mixins = {
        validateShippingInformation: function () {
            let countryCodeClass,
                isValid = true,
                consentForm = $('#consent-checkout-form');

            if (!consentForm.length) {
                return this._super(arguments);
            }

            countryCodeClass = consentForm.find('.iti__selected-flag .iti__flag').attr('class');

            if (consentFormUiClass().isChecked() && typeof countryCodeClass !== 'undefined') {
                try {
                    isValid = validate(
                        consentFormUiClass().consentPhoneInput().value(),
                        countryCodeClass
                    );
                } catch (e) {
                    return false;
                }
            }
            if (isValid) {
                return this._super(arguments);
            }

            return false;
        }
    };

    return function (target) {
        return target.extend(mixins);
    };
});

