/* global define */
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
                            this.message = result.errorMessage;
                        }
                    } catch {
                        this.message = errorMap[0];
                        isValid = false;
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
