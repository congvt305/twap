define(
    [
        'jquery',
        'Magento_Ui/js/modal/modal',
        'domReady!'
    ], function($, modal) {
    'use strict';

    var modaloption = {
        type: 'popup',
        modalClass: 'modal-popup',
        responsive: true,
        innerScroll: true,
        clickableOverlay: true
    };

    return function(config, element) {
        $( document ).ready(function() {
            $("."+config.attributeCode+'_checkbox').change(function() {
                if(this.checked) {
                    $("#"+config.attributeCode).val(1);
                } else {
                    $("#"+config.attributeCode).val(0);
                }

                if(config.attributeCode+'_checkbox' == 'dm_subscription_status_checkbox')
                {
                    $('.dm-address').toggle();
                }
            });

            $("#"+config.attributeCode+'-read-policy').on('click', function (e) {
                e.preventDefault();
                var privacyPolicyPopupSelector = '.'+config.attributeCode+'-policy-popup';
                var privacyPolicyLabelSelector = '.'+config.attributeCode+'-label';
                var chceckBoxSelector = "."+config.attributeCode+'_checkbox';

                modaloption['title'] = $(privacyPolicyLabelSelector).text();

                modaloption['buttons'] = [
                    {
                        text: $.mage.__('Agree'),
                        class: 'agree button action primary',
                        click: function () {
                            this.closeModal();
                            $(chceckBoxSelector).prop("checked", true);
                            if(chceckBoxSelector == '.dm_subscription_status_checkbox')
                            {
                                $('.dm-address').show();
                            }
                        }
                    },
                    {
                        text: $.mage.__('Disagree'),
                        class: 'disagree button action secondary',
                        click: function () {
                            this.closeModal();
                            $(chceckBoxSelector).prop("checked", false);
                            if(chceckBoxSelector == '.dm_subscription_status_checkbox')
                            {
                                $('.dm-address').hide();
                            }
                            return false;
                        }
                    }
                ]
                $(privacyPolicyPopupSelector).modal(modaloption).modal('openModal');
            });
        });

    };

});