define(['jquery', 'domReady!'], function($){
    'use strict';

    return function(config, element) {
        $( document ).ready(function() {
            $("."+config.attributeCode+'_checkbox').change(function() {
                if(this.checked) {
                    $("#"+config.attributeCode).val(1);
                } else {
                    $("#"+config.attributeCode).val(0);
                }
            });
        });

    };

});