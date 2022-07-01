define([
    'jquery',
    'Magento_Ui/js/form/form',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'mage/translate'
], function ($, Component, quote, url, $t) {
    'use strict';
    return Component.extend({
        /**
         * Form submit handler
         */
        onSubmit: function () {
            this.source.set('params.invalid', false);
            this.source.trigger('telephoneResubmission.data.validate');
            // trigger form validation
            if (!this.source.get('params.invalid')) {
                const formData = this.source.get('telephoneResubmission'),
                    shippingAddress = quote.shippingAddress(),
                    phoneUpdateButton = $('#ddg-phone-update');

                $('body').trigger('processStart');
                phoneUpdateButton.prop('disabled', true);
                $.post(url.build('sms_connector/customeraddress/updatetelephonenumber'), {
                    addressId: shippingAddress.customerAddressId,
                    phoneNumber: formData.telephone
                }).done(function () {
                    window.location.reload();
                    phoneUpdateButton.prop('disabled', false);
                }).fail(function () {
                    $('body').trigger('processStop');
                    phoneUpdateButton.prop('disabled', false);
                    $('<div class="field-error">' +
                        '<p>' + $t('Something went wrong, please try again.') + '</p>' +
                    '</div>').insertAfter(phoneUpdateButton);
                });
            }
        }
    });
});
