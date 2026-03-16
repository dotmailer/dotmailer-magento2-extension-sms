var config = {
    map: {
        '*': {
            smsCounter: 'Dotdigitalgroup_Sms/js/counter/smsCounter'
        },
        'Dotdigitalgroup_Sms': {
            ddTelephoneValidation: 'Dotdigitalgroup_Sms/js/model/telephoneValidation'
        }
    },

    paths: {
        'intlTelInput': 'Dotdigitalgroup_Sms/js/intlTelInput',
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
            'Magento_Ui/js/lib/validation/validator': {
                'Dotdigitalgroup_Sms/js/telephoneValidatorUi': true
            }
        }
    }
};
