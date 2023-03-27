define([], function () {
    'use strict';

    let errorMap = [
        'Invalid telephone number',
        'Invalid country code',
        'Telephone number is too short',
        'Telephone number is too long',
        'Invalid telephone number'
    ];

    return {
        getErrorMap: function () {
            return errorMap;
        },

        getErrorCode: function (value, countryCode) {
            return window.intlTelInputUtils.getValidationError(value, countryCode);
        }
    };

});
