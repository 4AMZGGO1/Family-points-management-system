<?php
/**
 * 数据库配置文件
 * 积分管理系统 - 数据库连接配置
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'points_system');
define('DB_PASSWORD', 'points_system');
define('DB_NAME', 'points_system');

// 网站配置
define('SITE_URL', 'http://localhost/php');
define('UPLOAD_PATH', 'assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// 创建数据库连接
function getDBConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $connection = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($connection->connect_error) {
                die("数据库连接失败: " . $connection->connect_error);
            }
            
            // 设置字符集为UTF-8
            $connection->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("数据库连接错误: " . $e->getMessage());
        }
    }
    
    return $connection;
}

// 安全函数：防止SQL注入
function sanitizeInput($input) {
    $connection = getDBConnection();
    return $connection->real_escape_string(trim($input));
}

// 密码加密函数（保留用于兼容性，实际使用明文比较）
function hashPassword($password) {
    return $password; // 直接返回明文
}

// 验证密码函数（改为明文比较）
function verifyPassword($password, $stored_password) {
    return $password === $stored_password; // 明文比较
}

// 生成随机字符串（用于会话等）
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// 检查用户是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 检查用户角色
function checkUserRole($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['user_role'] === $requiredRole;
}

// 重定向函数
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// 显示错误信息
function showError($message) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($message) . "</div>";
}

// 显示成功信息
function showSuccess($message) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// 开始会话
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>