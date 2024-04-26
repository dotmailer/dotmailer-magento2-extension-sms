/**
 * This module defines a Magento UI component for handling the consent form on the checkout page.
 * It uses the Knockout.js library for data binding and event handling.
 *
 * @module consentCheckoutForm
 * @requires jquery
 * @requires Magento_Ui/js/form/form
 * @requires ko
 * @requires Magento_Checkout/js/model/quote
 * @requires Magento_Ui/js/lib/view/utils/async
 */
define([

    'jquery',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/lib/view/utils/async',
], function ($, Component, ko, quote) {
    'use strict';

    /**
     * @class
     * @extends Component
     */
    return Component.extend({

        /**
         * Observable for the checkbox state.
         * @type {ko.observable}
         */
        isChecked: ko.observable(null, {deferred: true}),

        /**
         * Observable for the validity of the telephone number.
         * @type {ko.observable}
         */
        isValid: ko.observable(null),

        /**
         * Observable for the telephone input field.
         * @type {ko.observable}
         */
        consentPhoneInput: ko.observable(''),

        /**
         * Configuration object.
         * @type {Object}
         */
        config: null,

        /**
         * Initializes the component.
         * @param {Object} config - The configuration object.
         * @returns {Component} Returns the instance of the component.
         */
        initialize: function (config) {
            this._super();
            this.config = config;
            return this;
        },

        /**
         * Sets up event listeners when the component is ready.
         * @param {HTMLElement} element - The DOM element associated with the component.
         * @param {Object} UiClass - The UI class associated with the component.
         */
        onReady: function (element, UiClass) {

            const parentComponent = ko.contextFor(element).$parent,
                parentTelephoneInput = parentComponent?.getChild('telephone'),
                consentTelephoneInput = UiClass
                    .regions['consent-checkout-form-fields']()[0]
                    .getChild('dd_sms_consent_telephone'),
                consentCheckbox = UiClass
                    .regions['consent-checkout-form-fields-checkbox']()[0]
                    .getChild('dd_sms_consent_checkbox');

            this.attachIntlTelInput(consentTelephoneInput);
            this.isChecked(consentCheckbox.value());
            this.consentPhoneInput(consentTelephoneInput);

            document.addEventListener('numberIsValid', (validationEvent) => {
                if (!consentTelephoneInput.value()) {
                    this.isValid(true);
                    this.updateTelephoneField(consentTelephoneInput, validationEvent.detail.value);
                }
            });

            consentCheckbox.value.subscribe((value) => this.isChecked(value));

            if (parentTelephoneInput) {
                parentTelephoneInput.value.subscribe((value) => {
                    if (typeof window.intlTelInputUtils === 'undefined') {
                        return true;
                    }
                    const newValueValid = window.intlTelInputUtils.isValidNumber(value);

                    if (!consentTelephoneInput.value() && newValueValid) {
                        this.isValid(newValueValid);
                        this.updateTelephoneField(consentTelephoneInput, value);
                        return true;
                    }
                });
            }

            quote.shippingAddress.subscribe((address) => {

                if (typeof window.intlTelInputUtils === 'undefined') {
                    return true;
                }

                const newValueValid = window.intlTelInputUtils.isValidNumber(address.telephone, address.countryId),
                 oldValueValid = window.intlTelInputUtils.isValidNumber(consentTelephoneInput.value());

                this.isValid(newValueValid);
                if (
                    newValueValid
                    && !this.isChecked()
                ) {
                    this.updateTelephoneField(consentTelephoneInput, address.telephone);
                    return true;
                }

                if (
                    newValueValid
                    && this.isChecked()
                    && !consentTelephoneInput.value()
                ) {
                    this.updateTelephoneField(consentTelephoneInput, address.telephone);
                    return true;
                }

                if (
                    oldValueValid
                    && !newValueValid
                ) {
                    this.updateTelephoneField(consentTelephoneInput, consentTelephoneInput.value());
                    return true;
                }

                if (
                    !newValueValid
                ) {
                    this.updateTelephoneField(consentTelephoneInput, consentTelephoneInput.value());
                    return true;
                }

            });

            this.isChecked.subscribe((checked) => {
                if (checked && !consentTelephoneInput.value()) {
                    this.updateTelephoneField(
                        consentTelephoneInput,
                        parentTelephoneInput?.value() ?? quote.shippingAddress().telephone
                    );
                }
            });

        },

        /**
         * Attaches the international telephone input plugin to the given UI component.
         * @param {Object} uiComponent - The UI component to attach the plugin to.
         */
        attachIntlTelInput: function (uiComponent) {
            $.async(`input[name="${uiComponent.inputName}"]`, (node) => {
                window.intlTelInput(node, this._configData)
            })
        },


        /**
         * Updates the telephone field value on the next tick.
         * @param {Object} UiClass - The UI class associated with the component.
         * @param {string} value - The new value for the telephone field.
         * @param {boolean} [triggerDOM=true] - Whether to trigger a DOM change event.
         */
        updateTelephoneField: function (UiClass, value, triggerDOM = true) {
            setTimeout(() => {
                const element = $('#' + UiClass.uid);

                if (element[0] && value) {
                    UiClass.value(value);

                    if (typeof window.intlTelInputGlobals === 'undefined') { return; }
                    Object
                        .entries(window.intlTelInputGlobals.instances)
                        // eslint-disable-next-line no-unused-vars
                        .find(([key, intlElement]) => intlElement.telInput.id === element[0].id)
                        ?.pop()
                        ?.setNumber(`${value}`);
                }

                if (triggerDOM && element[0]) {
                    ko.utils.triggerEvent(
                        element[0],
                        'change'
                    );
                }
            }, 1);
        },


        /**
         * Returns the state of the consent block (expanded or not).
         * @returns {ko.observable} The observable for the checkbox state.
         */
        isConsentBlockExpanded: function () {
            return this.isChecked;
        }

    });
});
