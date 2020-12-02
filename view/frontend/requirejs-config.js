var config = {
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
                'Dotdigitalgroup_Sms/js/telephoneValidatorAddress': true
            }
        }
    }
};
