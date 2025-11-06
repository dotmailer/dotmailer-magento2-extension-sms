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
    return  function (selectShippingAddress) {
        return wrapper.wrap(selectShippingAddress, function (originalSelectShippingAddress, config, element) {
            originalSelectShippingAddress(config, element);

            const validateQuotePhone = (element) => {
                const ddgContainerSelector= $('#telephone-resubmission')
                const shippingAddress = quote.shippingAddress()
                const ValidatePhoneNumber = new Promise((resolve,reject) => {
                    return setTimeout(() => {
                        let intlInput = window.intlTelInputGlobals.getInstance(element)
                        intlInput.setNumber(shippingAddress.telephone);
                        return (intlInput.isValidNumber()) ? resolve(intlInput) : reject(intlInput);
                    }, 500); //wait for intlTelInput to initialize
                });

                //This is required for UI issue in magento2 v2.4.4
                //https://github.com/magento/magento2/issues/35651
                messageList.clear();
                ddgContainerSelector.hide();

                ValidatePhoneNumber
                    .then((intlInput) => document.dispatchEvent(new CustomEvent('numberIsValid', {'detail': {'number': shippingAddress.telephone}})))
                    .catch((intlInput) => {
                        ddgContainerSelector.show();
                        document.dispatchEvent(new CustomEvent('numberIsInvalid', {'detail': {'number': shippingAddress.telephone}}));
                        if (!messageList.hasMessages()) {
                            messageList.addErrorMessage({
                                message: $t('Enter a valid phone number to receive SMS order notifications.')
                            });
                        }
                    })
            }

            (async () => {
                if(!customer.isLoggedIn()) return;
                $.async('#telephone-resubmission input[name="telephone"]' , (element)=> {
                    validateQuotePhone(element);
                })
            })();
        });
    };
});
