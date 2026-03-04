/* global define, window */
define([
    'jquery',
    'mage/utils/wrapper',
    'Dotdigitalgroup_Sms/js/view/consentCheckoutForm'
], function ($, wrapper, consentFormUiClass) {

    var mixins = {
        validateShippingInformation: function () {
            let countryCodeClass,
                isValid = true,
                consentForm = $('#consent-checkout-form');

            if (!consentForm.length) {
                return this._super(arguments);
            }

            countryCodeClass = consentForm.find('.iti__selected-country .iti__flag').attr('class');

            if (consentFormUiClass().isChecked() && typeof countryCodeClass !== 'undefined') {
                try {
                    isValid = window.intlTelInput.utils.isValidNumber(
                        consentFormUiClass().consentPhoneInput().value(),
                        countryCodeClass
                    );
                } catch {
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

