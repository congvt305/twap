/**
 * @author Eguana Team
 * @copyright Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: umer
 * Date: 9/11/20
 * Time: 6:02 PM
 */
define(
    [
        'jquery',
        'mage/translate',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'mage/url'
    ],
    function ($, $t, messageList, quote, url) {
        'use strict';
        return {
            validate: function () {

                let isValid = true;
                let barCodeValid = true;
                const eInvoiceForm = $('#linepay-custom-checkout-form');
                const choosenEInvoice = eInvoiceForm.find('input:radio[name="linepay_einvoice_type"]:checked').val();
                const barCodeValue = $("input:text[id=line_pay_ecpay_cellphone_barcode]").val();
                if (choosenEInvoice === 'cellphone-barcode-invoice') {
                    $.ajax({
                        dataType: 'json',
                        url: url.build('ecpay_ecpaypayment/payment/paymentcontroller'),
                        data: {barCodeValue: barCodeValue},
                        async: false,
                        cache: false,
                        type: 'post',
                        success: function (result) {
                            if (typeof result.IsExist === 'undefined' || result.IsExist !== 'Y') {
                                isValid = false;
                                barCodeValid = false;
                            }
                        },
                        error: function () {
                            isValid = false;
                            barCodeValid = false;
                        }
                    });
                }
                if (!isValid) {
                    messageList.addErrorMessage({ message: $t('Invalid payment method.') });

                    if (!barCodeValid) {
                        messageList.addErrorMessage({ message: $t('Invalid BarCode.') });
                    }
                    $("html, body").animate({scrollTop: 0}, "slow");
                }
                return isValid;
            }
        }
    }
);