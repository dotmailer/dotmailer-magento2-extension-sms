define([], function () {
    'use strict';

    return function (telephoneNumber, countryCodeClass) {
        let countryCode;

        if (countryCodeClass === undefined || countryCodeClass.indexOf(' ') === -1) {
            return false;
        }

        countryCodeClass = countryCodeClass.split(' ')[1];
        countryCode = countryCodeClass.split('__')[1];
        return window.intlTelInputUtils.isValidNumber(telephoneNumber, countryCode);
    };
});
