define([
    'jquery',
    'jquery/validate',
    'Dotdigitalgroup_Sms/js/model/telephoneValidation',
    'Dotdigitalgroup_Sms/js/model/telephoneValidationError'
], function ($, jqueryValidate, phoneValidate, phoneErrorHandler) {
    'use strict';

    var errorMap = phoneErrorHandler.getErrorMap(),
         validatorObj = {
            validate: function (value) {
                let countryCodeClass = $('.iti__selected-flag .iti__flag').attr('class'),
                    countryCode,
                    isValid,
                    errorCode;

                countryCodeClass = countryCodeClass.split(' ')[1];

                if (countryCodeClass === undefined) {
                    $.validator.messages['validate-phone-number'] = errorMap[1];

                    return false;
                }

                isValid = phoneValidate(value, countryCodeClass);
                if (!isValid) {
                    countryCode = countryCodeClass.split('__')[1];
                    errorCode = phoneErrorHandler.getErrorCode(value, countryCode);

                    $.validator.messages['validate-phone-number'] = typeof errorMap[errorCode] === 'undefined' ?
                        errorMap[0] :
                        errorMap[errorCode];
                }

                return isValid;
            }
        },
        withConsentValidator = {
            validate: function (value) {
                let countryCodeClass = $('.iti__selected-flag .iti__flag').attr('class'),
                    countryCode,
                    isValid,
                    errorCode,
                    hasProvidedConsent = $('#dd_sms_consent_checkbox').is(':checked');

                if (!hasProvidedConsent) {
                    return true;
                }

                countryCodeClass = countryCodeClass.split(' ')[1];

                if (countryCodeClass === undefined) {
                    $.validator.messages['validate-phone-number-with-checkbox'] = errorMap[1];

                    return false;
                }

                countryCode = countryCodeClass.split('__')[1];
                isValid = window.intlTelInputUtils.isValidNumber(value, countryCode);

                if (!isValid) {
                    errorCode = window.intlTelInputUtils.getValidationError(value, countryCode);

                    // eslint-disable-next-line max-len
                    $.validator.messages['validate-phone-number-with-checkbox'] = typeof errorMap[errorCode] === 'undefined' ?
                        errorMap[0] :
                        errorMap[errorCode];
                }

                return isValid;
            }
        };

    $.validator.addMethod(
        'validate-phone-number',
        validatorObj.validate,
        $.validator.messages['validate-phone-number']
    );

    $.validator.addMethod(
        'validate-phone-number-with-checkbox',
        withConsentValidator.validate,
        $.validator.messages['validate-phone-number-with-checkbox']
    );

    return function (widget) {
        return widget;
    };
});
