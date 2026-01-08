var config = {
    map: {
        'Dotdigitalgroup_Sms': {
            ddTelephoneValidation: 'Dotdigitalgroup_Sms/js/model/telephoneValidation',
            ddTelephoneValidationError: 'Dotdigitalgroup_Sms/js/model/telephoneValidationError'
        }
    },

    paths: {
        'intlTelInput': 'Dotdigitalgroup_Sms/js/intlTelInput',
        'intlTelInputUtils': 'Dotdigitalgroup_Sms/js/utils',
        'internationalTelephoneInput': 'Dotdigitalgroup_Sms/js/internationalTelephoneInput'
    },

    shim: {
        'intlTelInput': {
            'deps': ['jquery', 'knockout']
        },
        'internationalTelephoneInput': {
            'deps': ['jquery', 'intlTelInput']
        }
    },

    config: {
        mixins: {
            'mage/validation': {
                'Dotdigitalgroup_Sms/js/telephoneValidatorAccount': true
            },
            'Magento_Ui/js/form/element/abstract': {
                'Dotdigitalgroup_Sms/js/setAdditionalParams': true
            },
            'Magento_Ui/js/lib/validation/validator': {
                'Dotdigitalgroup_Sms/js/telephoneValidatorCheckout': true
            },
            'Magento_Checkout/js/action/select-shipping-address': {
                'Dotdigitalgroup_Sms/js/telephoneValidatorShipping': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Dotdigitalgroup_Sms/js/model/shipping-save-processor/setMarketingConsentMixin': true,
                'Dotdigitalgroup_Sms/js/model/shipping-save-processor/setTransactionalConsentMixin': true
            },
            'Magento_Checkout/js/view/shipping': {
                'Dotdigitalgroup_Sms/js/view/telephoneValidatorConsent': true
            }
        }
    }
};
