<?php
/**
 * 默认数据定义 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

/**
 * 获取默认数据
 * @param string $prefix 表前缀
 * @return array 默认数据
 */
function getDefaultData($prefix = 'forum_') {
    return [
        // 默认设置
        "{$prefix}settings" => [
            [
                'setting_key' => 'site_name',
                'setting_value' => 'PHP轻论坛',
                'setting_type' => 'string',
                'description' => '网站名称'
            ],
            [
                'setting_key' => 'site_description',
                'setting_value' => '一个简单易用的PHP论坛程序',
                'setting_type' => 'string',
                'description' => '网站描述'
            ],
            [
                'setting_key' => 'allow_registration',
                'setting_value' => '1',
                'setting_type' => 'bool',
                'description' => '是否允许用户注册'
            ],
            [
                'setting_key' => 'posts_per_page',
                'setting_value' => '15',
                'setting_type' => 'int',
                'description' => '每页显示的帖子数'
            ],
            [
                'setting_key' => 'topics_per_page',
                'setting_value' => '20',
                'setting_type' => 'int',
                'description' => '每页显示的主题数'
            ],
            [
                'setting_key' => 'forum_version',
                'setting_value' => '3.0.0',
                'setting_type' => 'string',
                'description' => '论坛版本'
            ],
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
        
        // 默认分类
        "{$prefix}categories" => [
            [
                'title' => '公告板',
                'description' => '官方公告和重要信息',
                'slug' => 'announcements',
                'sort_order' => 1
            ],
            [
                'title' => '综合讨论',
                'description' => '各种话题的讨论区',
                'slug' => 'general',
                'sort_order' => 2
            ],
            [
                'title' => '问答专区',
                'description' => '提问和解答的地方',
                'slug' => 'questions',
                'sort_order' => 3
            ]
        ],

        // 默认友链
        "{$prefix}links" => [
            [
                'name' => 'iKun外链',
                'url' => 'https://www.ikunwl.com',
                'description' => 'iKun外链(www.ikunwl.com)是一款优秀的中文分类目录联盟免费收录导航网。',
                'sort_order' => 100
            ]
        ]
    ];
}
?>

