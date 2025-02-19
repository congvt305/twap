/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_OfflinePayments/payment/checkmo'
        },

        //customize here
        /**
         * get Code
         *
         * @returns {string}
         */
        getCode: function() {
            return 'checkmo';
        },


        /**
         * Get payment method data
         */
        getData: function () {
            return {
                'method': this.getCode(),
                'po_number': null,
                'additional_data': null
            };
        },
        //end of customize


        /**
         * Returns send check to info.
         *
         * @return {*}
         */
        getMailingAddress: function () {
            return window.checkoutConfig.payment.checkmo.mailingAddress;
        },

        /**
         * Returns payable to info.
         *
         * @return {*}
         */
        getPayableTo: function () {
            return window.checkoutConfig.payment.checkmo.payableTo;
        }
    });
});
