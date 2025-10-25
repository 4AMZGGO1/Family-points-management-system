<?php
/**
 * 孩子端申请积分页面
 * 积分管理系统 - 提交积分申请
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// 设置默认孩子用户ID（无需登录）
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'child1';
    $_SESSION['user_role'] = 'child';
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    $task_id = (int)$_POST['task_id'];
    $description = sanitizeInput($_POST['description']);
    $proof_image = null;
    
    // 处理图片上传
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $proof_image = handleFileUpload($_FILES['proof_image']);
        if (!$proof_image) {
            $error_message = "图片上传失败，请检查文件格式和大小。支持JPG、PNG、GIF格式，文件大小不超过5MB。";
        }
    } elseif (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // 处理上传错误
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => '文件上传被扩展程序阻止'
        ];
        $error_message = "图片上传失败：" . ($upload_errors[$_FILES['proof_image']['error']] ?? '未知错误');
    }
    
    if (!$error_message) {
        if (submitPointsApplication($user_id, $task_id, $description, $proof_image)) {
            $success_message = "申请提交成功！请等待家长审核。";
        } else {
            $error_message = "申请提交失败，请重试";
        }
    }
}

// 获取任务数据
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
if ($category) {
    $tasks = getTasksByCategory($category);
} else {
    $tasks = getAllTasks();
}

$categories = getTaskCategories();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申请积分 - 家庭积分管理系统</title>
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
            cursor: pointer;
        }
        .task-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .task-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .task-score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        .category-filter {
            margin-bottom: 2rem;
        }
        .category-btn {
            margin: 0.25rem;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .category-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
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
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="home.php">
                <i class="bi bi-star-fill text-primary"></i> 积分系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-house"></i> 首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="apply.php">
                            <i class="bi bi-plus-circle"></i> 申请积分
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">
                            <i class="bi bi-shop"></i> 积分商城
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="history.php">
                            <i class="bi bi-clock-history"></i> 历史记录
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="navbar-text">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-plus-circle"></i> 申请积分
                            </h4>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($success_message): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error_message): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- 分类筛选 -->
                            <div class="category-filter">
                                <h5>选择任务分类：</h5>
                                <div class="d-flex flex-wrap">
                                    <a href="apply.php" class="btn btn-outline-primary category-btn <?php echo !$category ? 'active' : ''; ?>">
                                        全部
                                    </a>
                                    <?php foreach ($categories as $cat): ?>
                                        <a href="apply.php?category=<?php echo urlencode($cat); ?>" 
                                           class="btn btn-outline-primary category-btn <?php echo $category === $cat ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="submit_application">
                                
                                <!-- 选择任务 -->
                                <div class="mb-4">
                                    <h5>选择要完成的任务：</h5>
                                    <div class="row">
                                        <?php foreach ($tasks as $task): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="task-card" onclick="selectTask(<?php echo $task['id']; ?>)">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-2"><?php echo htmlspecialchars($task['title']); ?></h6>
                                                            <p class="text-muted mb-2 small">
                                                                <?php echo htmlspecialchars($task['description']); ?>
                                                            </p>
                                                            <small class="text-muted">
                                                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($task['category']); ?>
                                                            </small>
                                                        </div>
                                                        <div class="task-score">
                                                            +<?php echo $task['score']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <input type="hidden" name="task_id" id="selected_task_id" required>
                                    <div class="invalid-feedback" id="task_error">
                                        请选择一个任务
                                    </div>
                                </div>
                                
                                <!-- 任务描述 -->
                                <div class="mb-4">
                                    <label for="description" class="form-label">
                                        <i class="bi bi-chat-text"></i> 任务完成情况描述
                                    </label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" placeholder="请详细描述你是如何完成这个任务的..." required></textarea>
                                </div>
                                
                                <!-- 上传证明图片 -->
                                <div class="mb-4">
                                    <label for="proof_image" class="form-label">
                                        <i class="bi bi-image"></i> 上传证明图片（可选）
                                    </label>
                                    <input type="file" class="form-control" id="proof_image" name="proof_image" 
                                           accept="image/*">
                                    <div class="form-text">
                                        支持 JPG、PNG、GIF 格式，文件大小不超过 5MB
                                    </div>
                                </div>
                                
                                <!-- 提交按钮 -->
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-submit">
                                        <i class="bi bi-send"></i> 提交申请
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedTaskId = null;
        
        function selectTask(taskId) {
            // 移除之前的选择
            document.querySelectorAll('.task-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // 添加当前选择
            event.currentTarget.classList.add('selected');
            selectedTaskId = taskId;
            document.getElementById('selected_task_id').value = taskId;
            
            // 清除错误提示
            document.getElementById('task_error').style.display = 'none';
        }
        
        // 表单验证
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!selectedTaskId) {
                e.preventDefault();
                document.getElementById('task_error').style.display = 'block';
                return false;
            }
        });
        
        // 图片预览
        document.getElementById('proof_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // 可以在这里添加图片预览功能
                    console.log('图片已选择:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
        
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