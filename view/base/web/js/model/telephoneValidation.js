/* global define, document, window */
define([], function () {

    // Error codes returned by intl-tel-input's getValidationError function
    let errorMap = [
        'Invalid telephone number', // "IS_POSSIBLE": 0,
        'Invalid country code', // "INVALID_COUNTRY_CODE": 1,
        'Telephone number is too short', // "TOO_SHORT": 2,
        'Telephone number is too long', // "TOO_LONG": 3,
        'Invalid telephone number', // "IS_POSSIBLE_LOCAL_ONLY": 4,
        'Invalid telephone number' // "INVALID_LENGTH": 5,
    ];

    function getInstance(elementId) {
        const element = document.getElementById(elementId),
            instance = window.intlTelInput.getInstance(element);

        if (!element) {
            throw new Error('Element not found: ' + elementId);
        }

        if (!instance) {
            throw new Error('intlTelInput instance not found for: ' + elementId);
        }
        return instance;
    }

    return {
        getErrorMap: function () {
            return errorMap;
        },
        validate: function (elementId) {
            const itiInstance = getInstance(elementId),
                isValid = itiInstance.isValidNumberPrecise();

            return {
                isValid: isValid,
                errorCode: isValid ? null : itiInstance.getValidationError(),
                errorMessage: isValid ? null : errorMap[itiInstance.getValidationError()] ?? 'Invalid telephone number'
            };
        }
    };
});
