define([
    'Magento_Ui/js/form/element/boolean'
], function (Boolean) {
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
            document.addEventListener('addressPhoneCountryChange', (event) =>  {
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
