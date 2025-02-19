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
                const errorMessage = {
                    please_enter_in_numbers: $t('Please enter in numbers.'),
                    this_is_a_required_field: $t('This is a required field.')
                };
                $('.line-pay-triplicate-title-error-msg').hide();
                $('.line-pay-tax-id-number-error-msg').hide();
                if (choosenEInvoice == 'cellphone-barcode-invoice') {
                    if ($("input:text[id=line_pay_ecpay_cellphone_barcode]").val() != '') {
                        var barCode = $("input:text[id=line_pay_ecpay_cellphone_barcode]").val();
                        barCode = barCode.replace(/[\s]/g, "+");
                        $("input:text[id=line_pay_ecpay_cellphone_barcode]").val(barCode);
                    }
                }
                if (choosenEInvoice == 'triplicate-invoice')
                {
                    let requiredFieldEntered = true;
                    let linePayTriplicateTitle = $("input:text[id=line_pay_triplicate_title]").val();
                    if (linePayTriplicateTitle == '') {
                        $('.line-pay-triplicate-title-error-msg').show();
                        requiredFieldEntered = false;
                    }
                    let linePayTaxIdNumber = $("input:text[id=line_pay_tax_id_number]").val();
                    if (linePayTaxIdNumber == '') {
                        $('.line-pay-tax-id-number-error-msg').text(errorMessage.this_is_a_required_field).show();
                        requiredFieldEntered = false;
                    } else {
                        let isNum = linePayTaxIdNumber.match(/^[0-9]+$/) != null;
                        if (!isNum) {
                            $('.line-pay-tax-id-number-error-msg').text(errorMessage.please_enter_in_numbers).show();
                            requiredFieldEntered = false;
                        }
                    }
                    if (requiredFieldEntered == false) {
                        return requiredFieldEntered;
                    }
                }

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
