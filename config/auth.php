<?php

return [

    'code' => [
        'unauthorized' => 400401,
    ],

    /**
     * 支持的应用配置
     */
    'apps' => [
        'op' => [
            'id' => '100006',
            'secret' => env('AUTH_OP_SECRET','123456'),
        ],
        'erp' => [
            'id' => '100009',
            'secret' => env('AUTH_ERP_SECRET','123456'),
        ],
        'crm' => [
            'id' => '100010',
            'secret' => env('AUTH_CRM_SECRET','123456'),
        ],
        'ds' => [
            'id' => '100011',
            'secret' => env('AUTH_DS_SECRET','123456'),
        ],
        'payment' => [
            'id' => '100007',
            'secret' => env('AUTH_PAYMENT_SECRET','123456'),
        ],
        'xiaoke' => [
            'id' => '100005',
            'secret' => env('AUTH_XIAOKE_SECRET','123456'),
        ],
    ],
];