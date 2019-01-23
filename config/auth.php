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
            'alias' => 'erp',
            'secret' => env('AUTH_ERP_SECRET','123456'),
        ],
    ],
];