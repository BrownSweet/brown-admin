<?php

return [
    // 默认磁盘
    'default' => env('filesystem.driver', 'local'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/storage',
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage',
            // 可见性
            'visibility' => 'public',
        ],
        'ai'=>[
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/storage/ai',
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage/ai',
            // 可见性
            'visibility' => 'public',
        ],
        'png'=>[
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/storage/png',
            // 磁盘路径对应的外部URL路径
            'url'        => '/storage/png',
            // 可见性
            'visibility' => 'public',
        ]
        // 更多的磁盘配置信息
    ],
];
