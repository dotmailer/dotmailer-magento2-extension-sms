define([
    'Magento_Ui/js/form/element/boolean',
    'Magento_Checkout/js/model/quote'
], function (Boolean, quote) {
    'use strict';

    return Boolean.extend({

        custom_config: {
            isEnabled: false,
            isoCodes: []
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

            jQuery.async(`input[name="${self.inputName}"]`, element => {
                quote.shippingAddress.subscribe( (newAddress) => element.checked = false, this);
            });

            document.addEventListener('addressPhoneCountryChange', (event) =>  {
                jQuery.async(`input[name="${self.inputName}"]`, element => element.checked = false );
                const eventData = event.detail;
                const isEnabled = self.custom_config.isEnabled;
                if (self.custom_config.isoCodes.includes(eventData.iso2)) {
                    self.visible( (isEnabled) ? true : false );
                    self.disabled(false);
                } else {
                    self.visible(false);
                    self.disabled(true);
                }
            });
        }
    });
});
