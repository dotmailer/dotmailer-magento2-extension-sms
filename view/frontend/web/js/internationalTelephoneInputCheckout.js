/* global define, window, document, CustomEvent */
define([
    'Magento_Ui/js/lib/view/utils/async',
    'uiComponent',
    'ko',
    'Dotdigitalgroup_Sms/js/intlTelInput'
], function ($, Component, ko) {

    return Component.extend({
        defaults: {
            selectors: [
                'input[name="telephone"]'
            ]
        },

        events: {
            countryChangeEvent: (element) => {
                const intlTelInput = window.intlTelInput.getInstance(element);

                return new CustomEvent('addressPhoneCountryChange', {
                    detail: {
                        inputId: element.id,
                        iso2: intlTelInput.getSelectedCountryData().iso2,
                        countryData: intlTelInput.getSelectedCountryData()
                    }
                });
            }
        },

        attachIntlTelInput: function (node) {
            const element = $(node)[0],
                intlTelInput = window.intlTelInput(element, this._configData),

                isResubmissionInput = $(element).closest('#telephone-resubmission').length > 0,
                isBillingAddress = $(element).closest('[data-form="billing-new-address"]').length > 0,
                isShippingPage = !isResubmissionInput && !isBillingAddress;

            element.itiPromise = intlTelInput.promise;

            intlTelInput.promise.then(() => {
                const intlTelInputInstance = window.intlTelInput.getInstance(element);

                element.addEventListener('blur', () => {
                    element.value = intlTelInputInstance.getNumber();
                });

                element.addEventListener('countrychange', (event) => {
                    if (isShippingPage) { document.dispatchEvent(this.events.countryChangeEvent(event.target)); }
                });

                document.addEventListener('numberIsValid', (event) => {
                    intlTelInputInstance.setNumber(event.detail.number);
                    intlTelInputInstance.ui.telInput.blur();
                    ko.utils.triggerEvent(intlTelInputInstance.ui.telInput, 'change');
                });

                document.addEventListener('numberIsInvalid', (event) => {
                    intlTelInputInstance.setNumber(event.detail.number);
                    intlTelInputInstance.ui.telInput.blur();
                    ko.utils.triggerEvent(intlTelInputInstance.ui.telInput, 'change');
                });

                if (isShippingPage) { document.dispatchEvent(this.events.countryChangeEvent(element)); }
            });
        },

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
