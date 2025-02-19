/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer store credit(balance) application
 */
define([
    'ko',
    'jquery',
    'Magento_Checkout/js/model/error-processor',
    'Amore_StaffReferral/js/model/referral-messages',
    'mage/storage',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/recollect-shipping-rates'
], function (ko, $, errorProcessor, messageContainer, storage, $t, getPaymentInformationAction,
    totals, fullScreenLoader, recollectShippingRates
) {
    'use strict';

    var dataModifiers = [],
        successCallbacks = [],
        failCallbacks = [],
        action;

    /**
     * Apply provided coupon.
     *
     * @param {Object} referralInformation
     * @returns {Deferred}
     */
    action = function (referralInformation) {
        var url = referralInformation.getUrl(),
            data = referralInformation.getData(),
            headers = {};

        //Allowing to modify coupon-apply request
        dataModifiers.forEach(function (modifier) {
            modifier(headers, data);
        });
        fullScreenLoader.startLoader();
        return storage.put(
            url,
            JSON.stringify(data),
            false,
            null,
            headers
        ).done(function (response) {
            var deferred;

            if (response) {
                deferred = $.Deferred();

                totals.isLoading(true);
                recollectShippingRates();
                getPaymentInformationAction(deferred);
                messageContainer.addSuccessMessage({
                    'message': response.message
                });
                $.when(deferred).done(function () {
                    fullScreenLoader.stopLoader();
                    totals.isLoading(false);
                    referralInformation.onApplied();
                });
                //Allowing to tap into apply-coupon process.
                successCallbacks.forEach(function (callback) {
                    callback(response);
                });
            }
        }).fail(function (response) {
            fullScreenLoader.stopLoader();
            totals.isLoading(false);
            errorProcessor.process(response, messageContainer);
            failCallbacks.forEach(function (callback) {
                callback(response);
            });
        });
    };

    /**
     * Modifying data to be sent.
     *
     * @param {Function} modifier
     */
    action.registerDataModifier = function (modifier) {
        dataModifiers.push(modifier);
    };

    /**
     * When successfully added a coupon.
     *
     * @param {Function} callback
     */
    action.registerSuccessCallback = function (callback) {
        successCallbacks.push(callback);
    };

    /**
     * When failed to add a coupon.
     *
     * @param {Function} callback
     */
    action.registerFailCallback = function (callback) {
        failCallbacks.push(callback);
    };

    return action;
});
