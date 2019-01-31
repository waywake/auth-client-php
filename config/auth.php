<?php

return [

    'code' => [
        'unauthorized' => 400401,
    ],

    /**
     * 支持的应用配置
     */
    'apps' => [
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
    ],
];