define([], function () {
    'use strict';

    var mixin = {

        validate: function () {
            if (this.elementTmpl === 'Dotdigitalgroup_Sms/form/element/telephone') {
                this.validationParams = {
                    uid: this.uid
                };
            }

            return this._super();
        }
    };

    return function (target) {
        return target.extend(mixin);
    };
});
