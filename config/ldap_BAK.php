<?php
return [
    'connections' => [
        'default' => [
            'hosts' => ['sede.gualadispensing.italia.com'],
            'username' => 'GUALADIS\B4WEB',
            'password' => 'Guala@1905',
            'base_dn' => 'dc=sede,dc=gualadispensing,dc=italia,dc=com',
            'port' => 389,
            'use_ssl' => false,
            'use_tls' => false,
        ],
    ],
];


//paolo.miraglia@gualadispensing.com
// 
// php artisan config:cache
//php artisan cache:clear
