define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'ablr',
                component: 'Ablr_Payment/js/view/payment/method-renderer/ablr-method'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
