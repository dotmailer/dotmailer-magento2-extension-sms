define([
    'Magento_Ui/js/lib/view/utils/async',
    'uiComponent',
    'Dotdigitalgroup_Sms/js/intlTelInput'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            selectors: [
                'input[name="telephone"]'
            ]
        },

        events: {
            country_change_event: (element) => {
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

        attachIntlTelInput: function(node) {
            const element = $(node)[0];
            const iti = window.intlTelInput(element, this._configData);

            const isResubmissionInput = $(element).closest('#telephone-resubmission').length > 0;
            const isBillingAddress = $(element).closest('[data-form="billing-new-address"]').length > 0;
            const isShippingPage = !isResubmissionInput && !isBillingAddress;

            element.itiPromise = iti.promise;

            iti.promise.then(() => {
                const intlTelInput = window.intlTelInputGlobals.getInstance(element);

                element.addEventListener('blur', () => {
                    element.value = intlTelInput.getNumber();
                });

                element.addEventListener('countrychange', (event) => {
                    if(isShippingPage)
                        document.dispatchEvent (this.events.country_change_event (event.target));
                });

                document.addEventListener('numberIsValid', (event) => {
                    intlTelInput.setNumber(event.detail.number);
                    intlTelInput.telInput.blur()
                });

                document.addEventListener('numberIsInvalid', (event) => {
                    intlTelInput.setNumber(event.detail.number);
                    intlTelInput.telInput.blur()
                });

                if(isShippingPage)
                    document.dispatchEvent(this.events.country_change_event(element));
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
