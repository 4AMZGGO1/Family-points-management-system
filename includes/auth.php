<?php
/**
 * 登录验证系统
 * 积分管理系统 - 用户认证
 */

require_once 'config.php';
require_once 'functions.php';

/**
 * 用户登录
 */
function loginUser($username, $password) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT id, username, password, role, points FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (verifyPassword($password, $user['password'])) {
            // 登录成功，设置会话
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_points'] = $user['points'];
            
            return true;
        }
    }
    
    return false;
}

/**
 * 用户登出
 */
function logoutUser() {
    session_destroy();
    session_start();
}

/**
 * 检查用户是否已登录
 */
function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'parent') {
            redirect('../parent/login.php');
        } else {
            redirect('../index.php');
        }
    }
}

/**
 * 检查用户角色权限
 */
function requireRole($requiredRole) {
    requireLogin();
    
    if (!checkUserRole($requiredRole)) {
        if ($requiredRole === 'parent') {
            redirect('../parent/login.php');
        } else {
            redirect('../index.php');
        }
    }
}

/**
 * 处理登录表单提交
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        if (loginUser($username, $password)) {
            if ($_SESSION['user_role'] === 'parent') {
                redirect('../parent/dashboard.php');
            } else {
                redirect('../child/home.php');
            }
        } else {
            $login_error = "用户名或密码错误";
        }
    }
    
    if ($_POST['action'] === 'logout') {
        logoutUser();
        redirect('../index.php');
    }
}
?>