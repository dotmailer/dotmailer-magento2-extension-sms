/* global define, window */
define([
    'jquery',
    'intlTelInput'
], function ($) {

    return function (config, node) {
        var telephoneInput = $(node)[0],
            iti = window.intlTelInput($(node)[0], config);

        telephoneInput.addEventListener('blur', function () {
            telephoneInput.value = iti.getNumber();
        });
    };
});
