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
                const ddgContainerSelector = $('#telephone-resubmission');
                const shippingAddress = quote.shippingAddress();
                const hasSavedAddress = customer.getShippingAddressList().length > 0;
                if(!hasSavedAddress){
                    return false;
                }
                messageList.clear();
                ddgContainerSelector.hide();

                const ValidatePhoneNumber = element.itiPromise.then(() => {
                    const intlInput = window.intlTelInputGlobals.getInstance(element);
                    intlInput.setNumber(shippingAddress.telephone);

                    return intlInput.isValidNumber()
                        ? Promise.resolve(intlInput)
                        : Promise.reject(intlInput);
                });

                ValidatePhoneNumber
                    .then((intlInput) => {
                        document.dispatchEvent(new CustomEvent('numberIsValid', {
                            'detail': {'number': shippingAddress.telephone}
                        }));
                    })
                    .catch((intlInput) => {
                        ddgContainerSelector.show();
                        document.dispatchEvent(new CustomEvent('numberIsInvalid', {
                            'detail': {'number': shippingAddress.telephone}
                        }));
                        if (!messageList.hasMessages()) {
                            messageList.addErrorMessage({
                                message: $t('Enter a valid phone number to receive SMS order notifications.')
                            });
                        }
                    });
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
