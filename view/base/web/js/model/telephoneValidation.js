define([], function () {
    'use strict';

    return function (telephoneNumber, countryCodeClass) {
        let countryCode;

        if (countryCodeClass === undefined || countryCodeClass.indexOf(' ') === -1) {
            throw new Error("Cannot find country code");
        }

        countryCodeClass = countryCodeClass.split(' ')[1];
        countryCode = countryCodeClass.split('__')[1];
        return window.intlTelInputUtils.isValidNumber(telephoneNumber, countryCode);
    };
});
