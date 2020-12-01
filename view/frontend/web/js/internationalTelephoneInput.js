define([
    'jquery',
    'intlTelInput'
], function ($) {
    return function (config, node) {
        // initialise plugin
        window.intlTelInput($(node)[0], config);
    };
});
