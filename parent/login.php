<?php
/**
 * 家长端登录页面
 * 积分管理系统 - 家长登录
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 如果已登录，直接跳转
if (isLoggedIn() && $_SESSION['user_role'] === 'parent') {
    redirect('dashboard.php');
}

$login_error = '';

// 处理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (loginUser($username, $password)) {
        if ($_SESSION['user_role'] === 'parent') {
            redirect('dashboard.php');
        } else {
            $login_error = "此账号不是家长账号";
            logoutUser();
        }
    } else {
        $login_error = "用户名或密码错误";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家长登录 - 家庭积分管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Microsoft YaHei', sans-serif;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .admin-features {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <h2><i class="bi bi-shield-lock"></i> 家长登录</h2>
                            <p class="mb-0">管理家庭积分系统</p>
                        </div>
                        
                        <div class="login-body">
                            <?php if ($login_error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo $login_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="login">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="bi bi-person"></i> 管理员用户名
                                    </label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="请输入管理员用户名" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="bi bi-lock"></i> 密码
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="请输入密码" required>
                                </div>
                                
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="bi bi-box-arrow-in-right"></i> 登录管理后台
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center">
                                <a href="../index.php" class="back-link">
                                    <i class="bi bi-arrow-left"></i> 返回孩子端
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 管理功能说明 -->
                    <div class="admin-features">
                        <h5 class="text-center mb-3">
                            <i class="bi bi-gear"></i> 管理功能
                        </h5>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div>
                                <strong>审核申请</strong>
                                <br>
                                <small class="text-muted">审核孩子的积分申请</small>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <div>
                                <strong>管理规则</strong>
                                <br>
                                <small class="text-muted">设置积分任务规则</small>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-shop"></i>
                            </div>
                            <div>
                                <strong>商城管理</strong>
                                <br>
                                <small class="text-muted">管理积分商城商品</small>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="bi bi-graph-up"></i>
                            </div>
                            <div>
                                <strong>数据统计</strong>
                                <br>
                                <small class="text-muted">查看积分统计数据</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('请填写完整的登录信息');
                return false;
            }
        });
        
        // 输入框焦点效果
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>