<?php
/**
 * 家长端规则管理页面
 * 积分管理系统 - 管理积分规则
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态和权限
requireRole('parent');

$success_message = '';
$error_message = '';

// 处理添加任务
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    $category = sanitizeInput($_POST['category']);
    $title = sanitizeInput($_POST['title']);
    $score = (int)$_POST['score'];
    $description = sanitizeInput($_POST['description']);
    
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO tasks (category, title, score, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $category, $title, $score, $description);
    
    if ($stmt->execute()) {
        $success_message = "任务规则添加成功！";
    } else {
        $error_message = "添加失败，请重试";
    }
}

// 处理删除任务
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    $task_id = (int)$_POST['task_id'];
    
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE tasks SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    
    if ($stmt->execute()) {
        $success_message = "任务规则已删除！";
    } else {
        $error_message = "删除失败，请重试";
    }
}

// 获取所有任务
$tasks = getAllTasks(false);
$categories = getTaskCategories();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理规则 - 家庭积分管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Microsoft YaHei', sans-serif;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .main-container {
            padding: 2rem 0;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .task-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .task-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .task-card.inactive {
            opacity: 0.6;
            background: #f8f9fa;
        }
        .task-score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .btn-add {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            border: none;
            border-radius: 10px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-shield-lock text-primary"></i> 管理后台
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> 首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="review.php">
                            <i class="bi bi-check-circle"></i> 审核申请
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="rules.php">
                            <i class="bi bi-list-check"></i> 管理规则
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop_admin.php">
                            <i class="bi bi-shop"></i> 商城管理
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="logout()">
                                <i class="bi bi-box-arrow-right"></i> 退出登录
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <!-- 页面标题 -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white text-center mb-0">
                        <i class="bi bi-list-check"></i> 管理积分规则
                    </h2>
                </div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- 添加新规则 -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-plus-circle"></i> 添加新规则
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_task">
                        
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="category" class="form-label">任务分类</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">选择分类</option>
                                    <option value="学习">学习</option>
                                    <option value="家务">家务</option>
                                    <option value="运动">运动</option>
                                    <option value="品德">品德</option>
                                    <option value="其他">其他</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="title" class="form-label">任务标题</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="例如：完成作业" required>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="score" class="form-label">积分奖励</label>
                                <input type="number" class="form-control" id="score" name="score" 
                                       min="1" max="1000" placeholder="10" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="description" class="form-label">任务描述</label>
                                <input type="text" class="form-control" id="description" name="description" 
                                       placeholder="任务详细说明">
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-success btn-add">
                                <i class="bi bi-plus-circle"></i> 添加规则
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 现有规则列表 -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> 现有规则列表
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-list-ul fs-1"></i>
                            <h5>暂无积分规则</h5>
                            <p>请添加第一个积分规则</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($tasks as $task): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="task-card <?php echo !$task['is_active'] ? 'inactive' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                <p class="text-muted mb-2 small">
                                                    <?php echo htmlspecialchars($task['description']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($task['category']); ?> · 
                                                    <i class="bi bi-calendar"></i> <?php echo date('Y-m-d', strtotime($task['created_at'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="task-score mb-2">
                                                    +<?php echo $task['score']; ?>
                                                </div>
                                                <?php if ($task['is_active']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_task">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-delete btn-sm"
                                                                onclick="return confirm('确定要删除这个规则吗？')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">已禁用</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 退出登录表单 -->
    <form id="logoutForm" method="POST" action="../includes/auth.php" style="display: none;">
        <input type="hidden" name="action" value="logout">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logout() {
            if (confirm('确定要退出登录吗？')) {
                document.getElementById('logoutForm').submit();
            }
        }
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.task-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>