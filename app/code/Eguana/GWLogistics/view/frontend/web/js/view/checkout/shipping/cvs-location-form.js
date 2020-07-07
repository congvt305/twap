define([
    'ko',
    'jquery',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Checkout/js/model/quote',
    'Eguana_GWLogistics/js/action/get-selected-cvs-location',
    'Eguana_GWLogistics/js/model/cvs-location',
    'Magento_Customer/js/customer-data',
    './test',
    'mage/url',
], function (ko, $, Component, quote, getSelectedCvsLocationAction, cvsLocation,  customerData, test, urlBuilder) {
    'use strict';
    return Component.extend({
        defaults: {
            visible: true,
            disabled: true,
            isMapVisible : true,
            displayArea: 'after-shipping-method-item',
            template: 'Eguana_GWLogistics/checkout/shipping/cvs-location-form',
            cvsMapFormTemplate: 'Eguana_GWLogistics/cvs-map-form',
            MerchantTradeNo: null,
            ServerReplyURL: urlBuilder.build('eguana_gwlogistics/SelectedCvsNotify'),
            LogisticsType: 'CVS',
            LogisticsSubType: 'UNIMART',
            IsCollection: 'N',
            device: null, //0: PC (default) 1: Mobile,
            tracks: {
                visible: true,
                isMapVisible: true,
            }
        },
        windowActivateCount: 0,

        initialize: function () {
            this._super();
            cvsLocation.clear();
            this.windowActivateCount = 0;
            return this;
        },

        initObservable: function () {
            this._super();
            quote.shippingMethod.subscribe(function (data) {
                this.visible = (data.method_code + '_' + data.carrier_code === 'CVS_gwlogistics') ? true : false ;
            }, this)
            $(document).on('visibilitychange', $.proxy(this.onWindowActivated, this));
            return this;
        },

        onWindowActivated: function () {
            this.windowActivateCount++;
            if (this.windowActivateCount % 2 === 0) {
                this.getSelectedCvsLocation();
            }

        },

        getSelectedCvsLocation: function () {
            getSelectedCvsLocationAction.bind(this);
            cvsLocation.selectCvsLocation();
        },

        getCvsLocation: function () {
            return cvsLocation.getCvsLocation();
        },

        openCvsMap: function () { //todo open window and submit
            // console.log('openCvsMap');
            var mapWin = window.open('', 'cvsMapFormGw');
            // $.proxy($('#cvs-map-load-form').submit(), this);
        },

        getMerchantTradeNo: function () {
            return Date.now().toString();
        },

        setCvsMapFormData: function () { //todo create form dynamically
            // console.log('cvs initialized.');
            // this.device(this.isMobile());
            // this.ServerReplyURL('http://192.168.0.1/ReceiverServerReply');
        },

        isMobile: function () {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },

        getExtraData: function () {
            return quote.getQuoteId();
        },

    });
});
