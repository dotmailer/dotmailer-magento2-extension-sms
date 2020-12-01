define([
    'jquery',
    'intlTelInput'
], function ($, intlTelInput) {
    'use strict';

    return function (validator) {

        const errorMap = ["Invalid telephone number", "Invalid country code", "Telephone number is too short", "Telephone number is too long", "Invalid telephone number"];

        let validatorObj = {
            message: '',
            validate: function (value, params, additionalParams) {
                let countryCodeClass = $(".iti__selected-flag .iti__flag").attr('class');

                if (countryCodeClass === undefined) {
                    this.message = errorMap[1];
                    return false;
                }

                countryCodeClass = countryCodeClass.split(' ')[1];
                let countryCode = countryCodeClass.split('__')[1];
                let isValid = intlTelInputUtils.isValidNumber(value, countryCode);

                if (!isValid) {
                    this.message = errorMap[
                        intlTelInputUtils.getValidationError(value, countryCode)
                        ];
                }

                return isValid;
            },
        }

        validator.addRule(
            'validate-phone-number',
            validatorObj.validate,
            $.mage.__(validatorObj.message)
        );

        return validator;
    };
});
