/* global define */
define([
    'jquery',
    'jquery/validate',
    'ddTelephoneValidation'
], function ($, jqueryValidate, phoneValidate) {

    const errorMap = phoneValidate.getErrorMap (),
        validatorObj = {
            validate: function (value, element) {

                let isValid = false;

                try {
                    const elementId = $(element).attr ('id'),

                     result = phoneValidate.validate(elementId);

                    isValid = result.isValid;
                    if (!isValid) {
                        $.validator.messages['validate-phone-number'] = result.errorMessage;
                    }
                } catch {
                    $.validator.messages['validate-phone-number'] = errorMap[0];
                    isValid = false;
                }

                return isValid;
            }
        },
        withConsentValidator = {
            validate: function (value, element) {

                let isValid = false;

                const hasProvidedConsent = $('#dd_sms_marketing_consent_checkbox').is (':checked');

                if (!hasProvidedConsent && !value) {
                    return true;
                }

                try {
                    const elementId = $(element).attr ('id'),
                     result = phoneValidate.validate(elementId);

                    isValid = result.isValid;
                    if (!isValid) {
                        $.validator.messages['validate-phone-number-with-checkbox'] = result.errorMessage;
                    }
                } catch {
                    $.validator.messages['validate-phone-number-with-checkbox'] = errorMap[0];
                    isValid = false;
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
