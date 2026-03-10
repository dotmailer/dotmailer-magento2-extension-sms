/* global define, document, window, jQuery */
define([
    'Magento_Ui/js/form/element/boolean',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function (Boolean, quote, customer) {

    return Boolean.extend({

        customConfig: {
            isoCodes: [],
            intlTelInputConfig: []
        },

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();
            this.customConfig = this.get('customConfig');
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

                // Attach event listener for customer without saved addresses
                if (!self.guestAddressHandler && customer.isLoggedIn() && !customer.getShippingAddressList().length) {
                    self.guestAddressHandler = function (event) {
                        const eventData = event.detail;

                        if (eventData && eventData.iso2) {
                            self.updateVisibility(eventData.iso2.toLowerCase());
                        }
                    };

                    document.addEventListener('addressPhoneCountryChange', self.guestAddressHandler);
                } else if (customer.isLoggedIn() && customer.getShippingAddressList().length > 0) {
                    self.getCountryFromPhone(newAddress.telephone).then(function (countryIso) {
                        self.updateVisibility(countryIso);
                    });
                }
            });

            if (quote.shippingAddress() && quote.shippingAddress().telephone) {
                self.getCountryFromPhone(quote.shippingAddress().telephone).then(function (countryIso) {
                    self.updateVisibility(countryIso);
                });
            }

            if (!customer.isLoggedIn() || customer.isLoggedIn() &&
                !self.guestAddressHandler && !customer.getShippingAddressList().length) {
                self.guestAddressHandler = function (event) {
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
        updateVisibility: function (countryIso) {
            if (this.customConfig.isoCodes.includes(countryIso)) {
                this.visible(true);
                this.disabled(false);
            } else {
                this.visible(false);
                this.disabled(true);
            }

            jQuery.async('input[name="' + this.inputName + '"]', function (element) {
                element.checked = false;
            });
        },

        /**
         * Cleanup on component destroy
         */
        destroy: function () {
            if (this.guestAddressHandler) {
                document.removeEventListener('addressPhoneCountryChange', this.guestAddressHandler);
            }
            this._super();
        },

        /**
         * Extract country ISO from phone number
         * @param {string} telephone
         * @returns {Promise}
         */
        getCountryFromPhone: function (telephone) {
            if (!telephone || !window.intlTelInput) {
                return Promise.resolve('');
            }

            const tempInput = document.createElement('input'),
                iti = (
                    tempInput.type = 'tel',
                        tempInput.style.display = 'none',
                        document.body.appendChild(tempInput),
                        window.intlTelInput(tempInput, JSON.parse(this.customConfig.intlTelInputConfig) || {})
                );

            iti.setNumber(telephone);

            return iti.promise.then(function () {
                const isValid = iti.isValidNumberPrecise(),
                    countryData = iti.getSelectedCountryData(),
                    iso2 = isValid && countryData.iso2 ? countryData.iso2.toLowerCase() : '';

                iti.destroy();
                document.body.removeChild(tempInput);

                return iso2;
            });
        }
    });
});
