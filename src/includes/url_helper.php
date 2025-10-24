<?php
/**
 * URL助手函数库 - 用于生成和解析伪静态URL
 * 
 * 此文件包含所有与URL生成相关的函数，用于支持轻论坛的伪静态功能
 * 所有页面链接应统一通过此文件中的函数生成，确保全站URL风格一致
 */

/**
 * 获取网站根URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = $path === '/' ? '' : $path;
    
    return $protocol . $host . $path;
}

/**
 * 获取首页URL
 */
function getHomeUrl() {
    return getBaseUrl() . '/';
}

/**
 * 获取首页分页URL
 */
function getHomePageUrl($page = 1) {
    if ($page <= 1) {
        return getHomeUrl();
    }
    return getBaseUrl() . '/page-' . $page . '.html';
}

/**
 * 获取主题页URL
 */
function getTopicUrl($id, $page = null, $title = '') {
    if ($page && $page > 1) {
        return getBaseUrl() . '/topic-' . $id . '-page-' . $page . '.html';
    }
    return getBaseUrl() . '/topic-' . $id . '.html';
}

/**
 * 获取编辑主题URL
 */
function getEditTopicUrl($id) {
    return getBaseUrl() . '/edit-topic-' . $id . '.html';
}

/**
 * 获取分类页URL
 */
function getCategoryUrl($id, $page = null, $title = '') {
    if ($page && $page > 1) {
        return getBaseUrl() . '/category-' . $id . '-page-' . $page . '.html';
    }
    return getBaseUrl() . '/category-' . $id . '.html';
}

/**
 * 获取分类列表页URL
 */
function getCategoriesUrl() {
    return getBaseUrl() . '/categories.html';
}

/**
 * 获取用户资料页URL
 */
function getUserProfileUrl($id, $username = '') {
    return getBaseUrl() . '/user-' . $id . '.html';
}

/**
 * 获取搜索页URL
 */
function getSearchUrl($query = null, $page = null) {
    $url = getBaseUrl() . '/search.html';
    
    if ($query) {
        $url .= '?q=' . urlencode($query);
        if ($page && $page > 1) {
            $url .= '&page=' . $page;
        }
        return $url;
    }
    
    if ($page && $page > 1) {
        return getBaseUrl() . '/search-page-' . $page . '.html';
    }
    
    return $url;
}

/**
 * 获取登录页URL
 */
function getLoginUrl($redirect = null) {
    $url = getBaseUrl() . '/login.html';
    if ($redirect) {
        $url .= '?redirect=' . urlencode($redirect);
    }
    return $url;
}

/**
 * 获取注册页URL
 */
function getRegisterUrl() {
    return getBaseUrl() . '/register.html';
}

/**
 * 获取忘记密码页URL
 */
function getForgotPasswordUrl() {
    return getBaseUrl() . '/forgot-password.html';
}

/**
 * 获取重置密码页URL
 */
function getResetPasswordUrl($token) {
    return getBaseUrl() . '/reset-password/' . $token . '.html';
}

/**
 * 获取新主题页URL
 */
function getNewTopicUrl($category_id = null) {
    if ($category_id) {
        return getBaseUrl() . '/new-topic-in-' . $category_id . '.html';
    }
    return getBaseUrl() . '/new-topic.html';
}

/**
 * 获取退出登录URL
 */
function getLogoutUrl() {
    return getBaseUrl() . '/logout.html';
}

/**
 * 获取后台首页URL
 */
function getAdminUrl() {
    return getBaseUrl() . '/admin/';
}

/**
 * 生成分页URL模式
 * 
 * @param string $page_name 页面名称
 * @param array $params 参数数组
 * @return string 分页URL模式
 */
function getPaginationUrlPattern($page_name, $params = []) {
    if ($page_name === 'index.php') {
        return getBaseUrl() . '/page-%d.html';
    } else if ($page_name === 'topic.php' && isset($params['id'])) {
        return getBaseUrl() . '/topic-' . $params['id'] . '-page-%d.html';
    } else if ($page_name === 'category.php' && isset($params['id'])) {
        return getBaseUrl() . '/category-' . $params['id'] . '-page-%d.html';
    } else if ($page_name === 'search.php') {
        if (isset($params['q'])) {
            return getBaseUrl() . '/search.html?q=' . urlencode($params['q']) . '&page=%d';
        }
        return getBaseUrl() . '/search-page-%d.html';
    }
    
    // 默认情况，添加page参数
    $url = getBaseUrl() . '/' . $page_name . '?page=%d';
    
    // 添加其他参数
    foreach ($params as $key => $value) {
        if ($key !== 'page') {
            $url .= '&' . $key . '=' . urlencode($value);
        }
    }
    
    return $url;
}

/**
 * 解析当前URL，获取伪静态参数
 * 
 * @return array 解析后的参数数组
 */
function parseRewriteUrl() {
    $request_uri = $_SERVER['REQUEST_URI'];
    $params = [];
    
    // 解析首页分页
    if (preg_match('/^\/page-([0-9]+)\.html/', $request_uri, $matches)) {
        $_GET['page'] = $matches[1];
        return ['page' => 'index.php'];
    }
    
    // 解析主题页
    if (preg_match('/^\/topic-([0-9]+)(-page-([0-9]+))?\.html/', $request_uri, $matches)) {
        $_GET['id'] = $matches[1];
        if (isset($matches[3])) {
            $_GET['page'] = $matches[3];
        }
        return ['page' => 'topic.php'];
    }
    
    // 解析编辑主题页
    if (preg_match('/^\/edit-topic-([0-9]+)\.html/', $request_uri, $matches)) {
        $_GET['id'] = $matches[1];
        return ['page' => 'edit_topic.php'];
    }
    
    // 解析分类页
    if (preg_match('/^\/category-([0-9]+)(-page-([0-9]+))?\.html/', $request_uri, $matches)) {
        $_GET['id'] = $matches[1];
        if (isset($matches[3])) {
            $_GET['page'] = $matches[3];
        }
        return ['page' => 'category.php'];
    }
    
    // 解析分类列表页
    if (preg_match('/^\/categories\.html/', $request_uri)) {
        return ['page' => 'categories.php'];
    }
    
    // 解析用户资料页
    if (preg_match('/^\/user-([0-9]+)\.html/', $request_uri, $matches)) {
        $_GET['id'] = $matches[1];
        return ['page' => 'profile.php'];
    }
    
    // 解析搜索页
    if (preg_match('/^\/search(-page-([0-9]+))?\.html/', $request_uri, $matches)) {
        if (isset($matches[2])) {
            $_GET['page'] = $matches[2];
        }
        return ['page' => 'search.php'];
    }
    
    // 解析登录页
    if (preg_match('/^\/login\.html/', $request_uri)) {
        return ['page' => 'login.php'];
    }
    
    // 解析注册页
    if (preg_match('/^\/register\.html/', $request_uri)) {
        return ['page' => 'register.php'];
    }
    
    // 解析忘记密码页
    if (preg_match('/^\/forgot-password\.html/', $request_uri)) {
        return ['page' => 'forgot_password.php'];
    }
    
    // 解析重置密码页
    if (preg_match('/^\/reset-password\/([a-zA-Z0-9]+)\.html/', $request_uri, $matches)) {
        $_GET['token'] = $matches[1];
        return ['page' => 'reset_password.php'];
    }
    
    // 解析新主题页
    if (preg_match('/^\/new-topic(-in-([0-9]+))?\.html/', $request_uri, $matches)) {
        if (isset($matches[2])) {
            $_GET['category_id'] = $matches[2];
        }
        return ['page' => 'new_topic.php'];
    }
    
    // 解析退出登录页
    if (preg_match('/^\/logout\.html/', $request_uri)) {
        return ['page' => 'logout.php'];
    }
    
    // 默认返回首页
    return ['page' => 'index.php'];
}
?>
