define([
    'jquery',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Checkout/js/model/quote'
], function ($, Component, ko, quote) {
    'use strict';
    return Component.extend({

        isChecked: ko.observable(null, {deferred: true}),
        isValid: ko.observable(null),
        consentPhoneInput: ko.observable(''),

        initialize: function () {
            this._super();
            return this;
        },

        /**
         * When ready add event listener to checkbox
         *
         * @param element
         * @param UiClass
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
         * Update telephone field value on next tick
         *
         * @param UiClass
         * @param value
         * @param triggerDOM
         */
        updateTelephoneField: function (UiClass, value, triggerDOM = true) {
            setTimeout(() => {
                const element = $('#' + UiClass.uid);

                if (element[0] && value && typeof window.intlTelInputGlobals !== 'undefined') {
                    UiClass.value(value);
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
         * Returns bool value for content block state (expanded or not)
         *
         * @returns {*|Boolean}
         */
        isConsentBlockExpanded: function () {
            return this.isChecked;
        }

    });
});
