/**
 * This module defines a custom UI component for Magento that integrates the
 * international telephone input library.
 *
 * @module Dotdigitalgroup_Sms/js/intlTelInput
 */
define([
    'Magento_Ui/js/lib/view/utils/async',
    'uiComponent',
    'Dotdigitalgroup_Sms/js/intlTelInput'
], function ($, Component) {
    'use strict';

    /**
     * The custom UI component.
     *
     * @class
     * @extends uiComponent
     */
    return Component.extend({
        /**
         * Default configuration for the component.
         *
         * @property {Array} selectors - The CSS selectors for the telephone input fields.
         */
        defaults: {
            selectors: [
                'input[name="telephone"]'
            ]
        },

        /**
         * Attaches the international telephone input library to a DOM node.
         *
         * @param {HTMLElement} node - The DOM node to attach the library to.
         */
        attachIntlTelInput: function(node) {
            let telephoneInput = $(node)[0],
                iti = window.intlTelInput(telephoneInput, this._configData);

            // Update the telephone input field with the formatted number when it loses focus.
            telephoneInput.addEventListener('blur', function() {
                telephoneInput.value = iti.getNumber();
                telephoneInput.dispatchEvent(new Event('change'));
            });

            // Update the telephone input field with the formatted number when the 'numberIsInvalid' event is dispatched.
            document.addEventListener('numberIsInvalid', function(event) {
                telephoneInput.value = event.detail.number;
                telephoneInput.value = iti.getNumber();
                telephoneInput.dispatchEvent(new Event('change'));
            });
        },

        /**
         * Initializes the component.
         *
         * @param {Object} configData - The configuration data for the international telephone input library.
         */
        initialize: function (configData) {
            this._super();
            this._configData = configData;
            // Attach the international telephone input library to each telephone input field.
            this.selectors.forEach((selector) =>  {
                $.async(selector, (node) => {
                    this.attachIntlTelInput(node);
                });
            });

            return this;
        }
    });
});
