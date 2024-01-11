/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'uiRegistry'
], function ($, quote, registry) {
    'use strict';

    return function (shippingAddress) {
        if (shippingAddress.telephone) {
            let telComponent = registry.get("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.telephone");
            telComponent.value(shippingAddress.telephone);
            registry.set("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.telephone", telComponent);
            $("#shipping-new-address-form input[name='telephone']").val(shippingAddress.telephone);
        }
        if (shippingAddress.firstname) {
            let firstnameComponent = registry.get("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname");
            firstnameComponent.value(shippingAddress.firstname);
            registry.set("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname", firstnameComponent);
            $("#shipping-new-address-form input[name='firstname']").val(shippingAddress.firstname);
        }
        if (shippingAddress.lastname) {
            let lastnameComponent = registry.get("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.lastname");
            lastnameComponent.value(shippingAddress.lastname);
            registry.set("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.firstname", lastnameComponent);
            $("#shipping-new-address-form input[name='lastname']").val(shippingAddress.lastname);
        }
        quote.shippingAddress(shippingAddress);
    };
});
