define(['underscore'], function (_) {
    'use strict';

    return {
        /**
         * Format address to use in store pickup
         *
         * @param {Object} address
         * @return {*}
         */
        formatAddressToPickupAddress: function (address) {
            var sourceCode = _.findWhere(address.customAttributes, {
                'attribute_code': 'sourceCode'
            });

            if (sourceCode && address.getType() !== 'cvs-pickup-address') {
                address = _.extend({}, address, {
                    saveInAddressBook: 0,

                    /**
                     * Is address can be used for billing
                     *
                     * @return {Boolean}
                     */
                    canUseForBilling: function () {
                        return false;
                    },

                    /**
                     * Returns address type
                     *
                     * @return {String}
                     */
                    getType: function () {
                        return 'cvs-pickup-address';
                    },

                    /**
                     * Returns address key
                     *
                     * @return {*}
                     */
                    getKey: function () {
                        return this.getType() + sourceCode.value;
                    }
                });
            }

            return address;
        }
    };
});
