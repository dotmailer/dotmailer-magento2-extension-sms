define([
    'jquery',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Checkout/js/model/quote',
    'intlTelInput'
], function ($, Component, ko, quote, intlTelInput) {
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

            const parent_component = ko.contextFor(element).$parent,
                parent_telephone_input = parent_component?.getChild('telephone'),
                consent_telephone_input = UiClass
                    .regions['consent-checkout-form-fields']()[0]
                    .getChild('dd_sms_consent_telephone'),
                consent_checkbox = UiClass
                    .regions['consent-checkout-form-fields-checkbox']()[0]
                    .getChild('dd_sms_consent_checkbox');

            this.isChecked(consent_checkbox.value());
            this.consentPhoneInput(consent_telephone_input);

            document.addEventListener('numberIsValid', (validationEvent) => {
                if (!consent_telephone_input.value())
                {
                    this.isValid(true);
                    this.updateTelephoneField(consent_telephone_input,validationEvent.detail.value);
                }
            });

            consent_checkbox.value.subscribe((value) => this.isChecked(value));

            if(parent_telephone_input) {
                parent_telephone_input.value.subscribe((value) => {
                    const newValueValid = window.intlTelInputUtils.isValidNumber(value);
                    if (!consent_telephone_input.value() && newValueValid)
                    {
                        this.isValid(newValueValid);
                        this.updateTelephoneField(consent_telephone_input,value);
                        return true;
                    }
                });
            }

            quote.shippingAddress.subscribe((address) => {
                const newValueValid = window.intlTelInputUtils.isValidNumber(address.telephone, address.countryId),
                 oldValueValid = window.intlTelInputUtils.isValidNumber(consent_telephone_input.value());

                this.isValid(newValueValid);
                if (
                    newValueValid
                    && !this.isChecked()
                ) {
                    this.updateTelephoneField(consent_telephone_input,address.telephone);
                    return true;
                }

                if (
                    newValueValid
                    && this.isChecked()
                    && !consent_telephone_input.value()
                ) {
                    this.updateTelephoneField(consent_telephone_input,address.telephone);
                    return true;
                }

                if (
                    oldValueValid
                    && !newValueValid
                ) {
                    this.updateTelephoneField(consent_telephone_input,consent_telephone_input.value());
                    return true;
                }

                if (
                    !newValueValid
                ) {
                    this.updateTelephoneField(consent_telephone_input,consent_telephone_input.value());
                    return true;
                }

            });

            this.isChecked.subscribe((checked) => {
                if (checked && !consent_telephone_input.value()) {
                    this.updateTelephoneField(
                        consent_telephone_input,
                        parent_telephone_input?.value() ?? quote.shippingAddress().telephone
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
                const element = $('#' + UiClass.uid)
                if (element[0] && value) {
                    UiClass.value(value);
                    Object
                        .entries(window.intlTelInputGlobals.instances)
                        .find(([key, value]) => value.telInput.id === element[0].id)
                        ?.pop()
                        ?.setNumber(`${value}`);
                }

                if (triggerDOM && element[0]) {
                    ko.utils.triggerEvent(
                        element[0],
                        'change'
                    );
                }
            },1);
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
