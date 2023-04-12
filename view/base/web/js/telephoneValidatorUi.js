define([
    'jquery',
    'ko',
    'ddTelephoneValidation',
    'ddTelephoneValidationError'
], function ($, ko, phoneValidate, phoneErrorHandler) {
    'use strict';

    return function (validator) {

        var errorMap = phoneErrorHandler.getErrorMap(),

         validatorObj = {
            message: '',

            /**
             * @param {String} value
             * @param {*} params
             * @param {Object} additionalParams
             */
            validate: function (value, params, additionalParams) {
                var target = $('#' + additionalParams.uid),
                    countryCodeClass = target.parent().find('.iti__selected-flag .iti__flag').attr('class'),
                    countryCode,
                    isValid = false,
                    errorCode;

                if (countryCodeClass === undefined || countryCodeClass.indexOf(' ') === -1) {
                    this.message = errorMap[1];

                    return false;
                }

                isValid = phoneValidate(value, countryCodeClass);

                if (!isValid) {
                    countryCodeClass = countryCodeClass.split(' ')[1];
                    countryCode = countryCodeClass.split('__')[1];
                    errorCode = phoneErrorHandler.getErrorCode(value, countryCode);
                    this.message = typeof errorMap[errorCode] === 'undefined' ?
                        errorMap[0] :
                        errorMap[errorCode];
                }

                // Ensure that changing the flag always updates the model
                ko.utils.triggerEvent(target[0], 'change');

                return isValid;
            }
        };

        validator.addRule(
            'validate-phone-number-with-checkbox',
            validatorObj.validate,
            $.mage.__(validatorObj.message)
        );

        return validator;
    };
});
