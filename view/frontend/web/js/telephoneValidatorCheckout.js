/* global define, setTimeout */
define([
    'jquery',
    'ko',
    'ddTelephoneValidation'
], function ($, ko, phoneValidate) {

    return function (validator) {

        const errorMap = phoneValidate.getErrorMap(),
        validatorObj = {
            message: '',

            /**
             * @param {String} value
             * @param {*} params
             * @param {Object} additionalParams
             */
            validate: function (value, params, additionalParams) {
                let target = $('#' + additionalParams.uid),
                    isValid = false;

                try {
                    const result = phoneValidate.validate(additionalParams.uid);

                    isValid = result.isValid;
                    if (!isValid) {
                        validatorObj.message = result.errorMessage;
                    }
                } catch {
                    validatorObj.message = errorMap[0];
                    isValid = false;
                }

                // Ensure that changing the flag always updates the model
                ko.utils.triggerEvent(target[0], 'change');

                return isValid;
            }
        };

        (async () => {
            while (!$('.iti').length) { await new Promise(resolve => setTimeout(resolve, 1)); }
            validator.addRule(
                'validate-phone-number',
                validatorObj.validate,
                function () {
                    return $.mage.__(validatorObj.message);
                }
            );
        })();

        return validator;
    };
});
