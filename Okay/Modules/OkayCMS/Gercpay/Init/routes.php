<?php

namespace Okay\Modules\OkayCMS\Gercpay;

return [
    'OkayCMS_Gercpay_callback' => [
        'slug' => 'payment/OkayCMS/Gercpay/callback',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\CallbackController',
            'method' => 'payOrder',
        ],
    ],
];
