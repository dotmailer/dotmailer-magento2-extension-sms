define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function ($, wrapper, messageList, $t, quote, customer) {
    'use strict';

    /**
     * Runs on address selection for logged in users and checks if phone number is valid
     *
     * @param {Function} selectShippingAddress
     * @returns {Function}
     */
    return function (selectShippingAddress) {
        return wrapper.wrap(selectShippingAddress, function (originalSelectShippingAddress, config, element) {
            originalSelectShippingAddress(config, element);
            const validateQuotePhone = () => {
                let shippingAddress = quote.shippingAddress(),
                    regex = /^\+(?:[0-9] ?){6,14}[0-9]$/,
                    ddgContainerSelector = $('#telephone-resubmission'),
                    phoneNumber = shippingAddress.telephone,
                    isLoggedIn = customer.isLoggedIn(),
                    hasStoredAddresses = $('.shipping-address-items').length,
                    isValid = regex.test(phoneNumber);

                ddgContainerSelector.hide();

                if (!phoneNumber) {
                    return;
                }

                if (!isValid && ddgContainerSelector.length && isLoggedIn && hasStoredAddresses) {
                    ddgContainerSelector.show();
                    let event = new CustomEvent('numberIsInvalid', {'detail': {'number': phoneNumber}});

                    document.dispatchEvent(event);

                    if (!messageList.hasMessages()) {
                        messageList.addErrorMessage({
                            message: $t('Enter a valid phone number to receive SMS order notifications.')
                        });
                    }
                } else {
                    //This is required for UI issue in magento2 v2.4.4
                    //https://github.com/magento/magento2/issues/35651
                    messageList.clear();
                    let event = new CustomEvent('numberIsValid', {'detail': {'number': phoneNumber}});

                    document.dispatchEvent(event);
                }
            };

            (async () => {
                while (!$('#telephone-resubmission').length) { await new Promise(resolve => setTimeout(resolve, 1)); }
                validateQuotePhone();
            })();
        });
    };
});
