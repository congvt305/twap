<?php
/**
 * @author Eguana Team
 * @copyright Copyright (c) 2019 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: david
 * Date: 2020/06/25
 * Time: 10:25 AM
 */

namespace Ecpay\Ecpaypayment\Helper\Library;

class ECPayInvoiceVoidValidator
{
    // 所需參數
    public $parameters = array(
        'TimeStamp'		=>'',
        'MerchantID'		=>'',
        'CheckMacValue'		=>'',
        'InvoiceNumber'		=>'',
        'Reason' 		=>''
    );

    // 需要做urlencode的參數
    public $urlencode_field = array(
        'Reason' 		=>''
    );

    // 不需要送壓碼的欄位
    public $none_verification = array(
        'Reason' 		=>'',
        'CheckMacValue'		=>''
    );

    /**
     * 1寫入參數
     */
    function insert_string($arParameters = array())
    {
        foreach ($this->parameters as $key => $value)
        {
            if(isset($arParameters[$key])) {
                $this->parameters[$key] = $arParameters[$key];
            }
        }

        return $this->parameters ;
    }

    /**
     * 2-2 驗證參數格式
     */
    function check_extend_string($arParameters = array())
    {

        $arErrors = array();

        // 42.發票號碼 InvoiceNumber
        // *必填項目
        if(strlen($arParameters['InvoiceNumber']) == 0) {
            array_push($arErrors, "42:InvoiceNumber is required.");
        }
        // *預設長度固定10碼
        if(strlen($arParameters['InvoiceNumber']) != 10) {
            array_push($arErrors, '42:InvoiceNumber length as 10.');
        }

        // 43.作廢原因 Reason
        // *必填欄位
        if(strlen($arParameters['Reason']) == 0) {
            array_push($arErrors, "43:Reason is required.");
        }
        // *字數限制在20(含)個字以內
        if( mb_strlen($arParameters['Reason'], 'UTF-8') > 20) {
            //array_push($arErrors, "43:Reason max length as 20.");
        }

        if(sizeof($arErrors)>0) {
            throw new \Exception(join('<br>', $arErrors));
        }

        return $arParameters ;
    }

    /**
     * 4欄位例外處理方式(送壓碼前)
     */
    function check_exception($arParameters = array())
    {
        return $arParameters ;
    }
}
