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

        events: {

            country_change_event: (element) => {
                /** @type Dotdigitalgroup_Sms/js/intlTelInput*/
                const intlTelInput = window.intlTelInputGlobals.getInstance(element);
                return new CustomEvent('addressPhoneCountryChange', {
                    detail: {
                        inputId: element.id,
                        iso2: intlTelInput.getSelectedCountryData().iso2,
                        countryData: intlTelInput.getSelectedCountryData()
                    }
                })
            }
        },

        /**
         * Attaches the international telephone input library to a DOM node.
         *
         * @param {HTMLElement} node - The DOM node to attach the library to.
         */
        attachIntlTelInput: function(node) {
            const element = $(node)[0];

            window.intlTelInput(element, this._configData);
            const intlTelInput = window.intlTelInputGlobals.getInstance(element);

            element.addEventListener('blur', () => {
                element.value = intlTelInput.getNumber();
            });

            element.addEventListener('countrychange', (event) => {
                document.dispatchEvent(this.events.country_change_event(event.target));
            });

            document.addEventListener('numberIsValid', (event) => {
                intlTelInput.setNumber(event.detail.number) ;
                intlTelInput.telInput.blur()
            });

            document.addEventListener('numberIsInvalid', (event) => {
                intlTelInput.setNumber(event.detail.number);
                intlTelInput.telInput.blur()
            });

            window.addEventListener('hashchange', () => {
                if($(element).is(":visible")){
                    document.dispatchEvent(this.events.country_change_event(element));
                }
            });

            document.dispatchEvent(this.events.country_change_event(element));
        },


        /**
         * Initializes the component.
         *
         * @param {Object} configData - The configuration data for the international telephone input library.
         */
        initialize: function (configData) {
            this._super();
            this._configData = configData;
            this.selectors.forEach((selector) =>  {
                $.async(selector, (node) => this.attachIntlTelInput(node));
            });
            return this;
        }
    });
});
