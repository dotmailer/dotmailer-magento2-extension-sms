define([
    'Magento_Ui/js/form/element/boolean',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function (Boolean, quote, customer) {
    'use strict';

    return Boolean.extend({

        custom_config: {
            isoCodes: [],
            intlTelInputConfig: []
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.custom_config = this.get('customConfig');
            this.listenForCountryChange();
            return this;
        },

        /**
         * Listen for country change events on telephone input
         */
        listenForCountryChange: function () {
            const self = this;

            // Subscribe to address changes for logged-in customers
            quote.shippingAddress.subscribe(function (newAddress) {
                if (!newAddress || !newAddress.telephone) {
                    self.visible(false);
                    self.disabled(true);
                    return;
                }

                const hasSavedAddress = customer.getShippingAddressList().length > 0;
                // Attach event listener for customer without saved addresses
                if (!self.guestAddressHandler && customer.isLoggedIn() && !hasSavedAddress) {
                    self.guestAddressHandler = function(event) {
                        const eventData = event.detail;
                        if (eventData && eventData.iso2) {
                            self.updateVisibility(eventData.iso2.toLowerCase());
                        }
                    };

                    document.addEventListener('addressPhoneCountryChange', self.guestAddressHandler);
                }else if(customer.isLoggedIn() && hasSavedAddress){
                    self.getCountryFromPhone(newAddress.telephone).then(countryIso => {
                        self.updateVisibility(countryIso);
                    });
                }
            });

            const initialAddress = quote.shippingAddress();
            if (initialAddress && initialAddress.telephone) {
                self.getCountryFromPhone(initialAddress.telephone).then(countryIso => {
                    self.updateVisibility(countryIso);
                });
            }

            const hasSavedAddress = customer.getShippingAddressList().length > 0;
            if (!customer.isLoggedIn() || (customer.isLoggedIn() && !self.guestAddressHandler && !hasSavedAddress)){
                self.guestAddressHandler = function(event) {
                    const eventData = event.detail;
                    if (eventData && eventData.iso2) {
                        self.updateVisibility(eventData.iso2.toLowerCase());
                    }
                };

                document.addEventListener('addressPhoneCountryChange', self.guestAddressHandler);
            }
        },

        /**
         * Update checkbox visibility based on country ISO
         * @param {string} countryIso
         */
        updateVisibility: function(countryIso) {
            if (this.custom_config.isoCodes.includes(countryIso)) {
                this.visible(true);
                this.disabled(false);
            } else {
                this.visible(false);
                this.disabled(true);
            }

            jQuery.async(`input[name="${this.inputName}"]`, element => element.checked = false);
        },

        /**
         * Cleanup on component destroy
         */
        destroy: function() {
            if (this.guestAddressHandler) {
                document.removeEventListener('addressPhoneCountryChange', this.guestAddressHandler);
            }
            this._super();
        },

        /**
         * Extract country ISO from phone number
         * @param {string} telephone
         * @returns {string}
         */
        getCountryFromPhone: function (telephone) {
            if (!telephone || !window.intlTelInput) {
                return Promise.resolve('');
            }

            const tempInput = document.createElement('input');
            tempInput.type = 'tel';
            tempInput.style.display = 'none';
            document.body.appendChild(tempInput);

            const iti = window.intlTelInput(tempInput, JSON.parse(this.custom_config.intlTelInputConfig) || {});
            iti.setNumber(telephone);

            return iti.promise.then(() => {
                const isValid = iti.isValidNumber();
                const countryData = iti.getSelectedCountryData();
                const iso2 = (isValid && countryData.iso2) ? countryData.iso2.toLowerCase() : '';

                iti.destroy();
                document.body.removeChild(tempInput);

                return iso2;
            });
        }
    });
});
