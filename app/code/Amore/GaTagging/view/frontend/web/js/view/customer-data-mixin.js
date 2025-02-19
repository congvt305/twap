define([
    'jquery',
    'underscore',
    'mage/utils/wrapper'
], function ($, _, wrapper) {
    'use strict';

    return function (stepNavigator) {
        stepNavigator.reload = wrapper.wrapSuper(stepNavigator.reload, function (sectionNames, forceNewSectionTimestamp) {

            return this._super(sectionNames, forceNewSectionTimestamp).always(function (){
                var storage = $.initNamespaceStorage('mage-cache-storage').localStorage, sectionData;
                sectionData = storage.get('customer-ap-data');
                if (_.size(sectionData) > 1) {
                    window.AP_DATA_GCID = sectionData['AP_DATA_GCID'] || undefined;
                    window.AP_DATA_CID = sectionData['AP_DATA_CID'];
                    window.AP_DATA_ISMEMBER = sectionData['AP_DATA_ISMEMBER'];
                    window.AP_DATA_ISLOGIN = sectionData['AP_DATA_ISLOGIN'];
                    window.AP_DATA_LOGINTYPE = sectionData['AP_DATA_LOGINTYPE'] || undefined;
                    window.AP_DATA_CA = sectionData['AP_DATA_CA'];
                    window.AP_DATA_CD = sectionData['AP_DATA_CD'] || undefined;
                    window.AP_DATA_CG = sectionData['AP_DATA_CG'] || undefined;
                    window.AP_DATA_CT = sectionData['AP_DATA_CT'] || undefined;

                    if (_.contains(sectionNames, 'customer-ap-data') && window.AP_DATA_LOGINTYPE) {
                        window.dataLayer.push({
                            'event': 'login_complete',
                            'event_category': 'login',
                            'event_action': 'login - complete',
                            'event_label': window.AP_DATA_LOGINTYPE
                        });
                    }
                }
            });
        });

        return stepNavigator;
    };
});
