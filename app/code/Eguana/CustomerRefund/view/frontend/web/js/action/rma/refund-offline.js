define([
    'jquery',
    'mage/url',
    'mage/storage',
    'mage/translate'
], function ($,url,storage, $t) {
    'use strict';

    return function (bankInfoData) {
        var refundUrl, serviceUrl, payload;
        refundUrl = 'rest/V1/eguana_customerrefund/mine/rma/bankinfo/save';
        payload = {'bankInfoData': bankInfoData};

        serviceUrl = url.build(refundUrl);

        $('body').trigger('processStart');

        return storage.post(
            serviceUrl,
            JSON.stringify(payload)
        ).done(
            function (response) {
                return true;
            } //if errors, reload
        ).fail(
            function () {
                $('body').trigger('processStop');
            }
        );
    };
});
