<?php
/**
 * 新增smtp功能
 */

function getDefaultData($prefix = 'forum_') {
    return [
        // 默认设置
        "{$prefix}settings" => [
            // SMTP配置项
            [
                'setting_key' => 'smtp_host',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => 'SMTP服务器地址'
            ],
            [
                'setting_key' => 'smtp_port',
                'setting_value' => '587',
                'setting_type' => 'int',
                'description' => 'SMTP服务器端口'
            ],
            [
                'setting_key' => 'smtp_secure',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => 'SMTP加密方式 (tls/ssl)'
            ],
            [
                'setting_key' => 'smtp_username',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => 'SMTP邮箱账号'
            ],
            [
                'setting_key' => 'smtp_password',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => 'SMTP邮箱密码'
            ],
            [
                'setting_key' => 'smtp_from',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => '发件人邮箱地址'
            ],
            [
                'setting_key' => 'smtp_from_name',
                'setting_value' => '论坛管理员',
                'setting_type' => 'string',
                'description' => '发件人显示名称'
            ],
            [
                'setting_key' => 'password_reset_expires',
                'setting_value' => '60',
                'setting_type' => 'int',
                'description' => '密码重置链接有效期（分钟）'
            ],
            [
                'setting_key' => 'account_activation_expires',
                'setting_value' => '24',
                'setting_type' => 'int',
                'description' => '账户激活链接有效期（小时）'
            ]
        ],
    ];
}
?>

