/* global CustomEvent, define, document, window */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Ui/js/model/messageList',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
], function ($, wrapper, messageList, $t, quote, customer) {


    /**
     * Runs on address selection for logged in users and checks if phone number is valid
     *
     * @param {Function} selectShippingAddress
     * @returns {Function}
     */
    return  function (selectShippingAddress) {
        return wrapper.wrap(selectShippingAddress, function (originalSelectShippingAddress, config, element) {
            originalSelectShippingAddress(config, element);

            const validateQuotePhone = (phoneElement) => {
                if (!customer.getShippingAddressList().length) {
                    return false;
                }

                const ddgContainerSelector = $('#telephone-resubmission'),
                    shippingAddress = quote.shippingAddress(),
                    ValidatePhoneNumber = phoneElement.itiPromise.then(() => {
                        const intlInput = window.intlTelInput.getInstance(phoneElement),
                        isInternationalFormat = (
                            intlInput.setNumber(shippingAddress.telephone),
                                shippingAddress.telephone.startsWith('+')
                        ),
                        isValid = isInternationalFormat ? intlInput.isValidNumberPrecise() : null;

                        if (!isInternationalFormat) {
                            return Promise.reject(intlInput);
                        }

                        return isValid
                            ? Promise.resolve(intlInput)
                            : Promise.reject(intlInput);
                    });

                messageList.clear();
                ddgContainerSelector.hide();

                ValidatePhoneNumber
                    .then(() => {
                        const intlInput = window.intlTelInput.getInstance(phoneElement);

                        document.dispatchEvent(new CustomEvent('numberIsValid', {
                            'detail': {'number': intlInput.getNumber()}
                        }));
                    })
                    .catch(() => {
                        const intlInput = window.intlTelInput.getInstance(phoneElement);

                        ddgContainerSelector.show();
                        document.dispatchEvent(new CustomEvent('numberIsInvalid', {
                            'detail': {'number': intlInput.getNumber()}
                        }));
                        if (!messageList.hasMessages()) {
                            messageList.addErrorMessage({
                                message: $t('Enter a valid phone number to receive SMS order notifications.')
                            });
                        }
                    });
            };


            (async () => {
                if (!customer.isLoggedIn()) { return; }
                $.async('#telephone-resubmission input[name="telephone"]', (el)=> {
                    validateQuotePhone(el);
                });
            })();
        });
    };
});
