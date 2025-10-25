<?php
/**
 * 家长端积分调整页面
 * 积分管理系统 - 手动调整孩子积分
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态和权限
requireRole('parent');

$success_message = '';
$error_message = '';

// 处理积分调整
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adjust_points') {
    $user_id = (int)$_POST['user_id'];
    $amount = (int)$_POST['amount'];
    $type = $_POST['type'];
    $description = sanitizeInput($_POST['description']);
    
    if ($amount > 0 && !empty($description)) {
        if (adjustUserPoints($user_id, $amount, $type, $description)) {
            $success_message = "积分调整成功！";
        } else {
            $error_message = "积分调整失败，请重试";
        }
    } else {
        $error_message = "请填写完整的调整信息";
    }
}

// 获取孩子用户信息
$db = getDBConnection();
$result = $db->query("SELECT * FROM users WHERE role = 'child'");
$child = $result->fetch_assoc();

// 获取最近的积分变动记录
$recent_transactions = getUserPointTransactions($child['id'], 10);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>调整积分 - 家庭积分管理系统</title>
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
        .points-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .points-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
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
        .btn-subtract {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-subtract:hover {
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
        .transaction-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        .transaction-item:hover {
            background: #f8f9fa;
        }
        .transaction-item:last-child {
            border-bottom: none;
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
                        <a class="nav-link" href="rules.php">
                            <i class="bi bi-list-check"></i> 管理规则
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop_admin.php">
                            <i class="bi bi-shop"></i> 商城管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="adjust_points.php">
                            <i class="bi bi-plus-minus"></i> 调整积分
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
                        <i class="bi bi-plus-minus"></i> 调整积分
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

            <div class="row">
                <!-- 当前积分状态 -->
                <div class="col-md-4 mb-4">
                    <div class="points-card">
                        <h4><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($child['username']); ?></h4>
                        <div class="points-number"><?php echo $child['points']; ?></div>
                        <div>当前积分</div>
                    </div>
                </div>

                <!-- 积分调整表单 -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-plus-minus"></i> 手动调整积分
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="adjust_points">
                                <input type="hidden" name="user_id" value="<?php echo $child['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">调整数量</label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               min="1" max="10000" placeholder="请输入积分数量" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="type" class="form-label">调整类型</label>
                                        <select class="form-select" id="type" name="type" required>
                                            <option value="">选择类型</option>
                                            <option value="manual_add">加分</option>
                                            <option value="manual_subtract">扣分</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">调整原因</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3" placeholder="请输入调整原因..." required></textarea>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="adjust_type" value="add" 
                                            class="btn btn-success btn-add me-md-2">
                                        <i class="bi bi-plus-circle"></i> 加分
                                    </button>
                                    <button type="submit" name="adjust_type" value="subtract" 
                                            class="btn btn-danger btn-subtract">
                                        <i class="bi bi-dash-circle"></i> 扣分
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近积分变动记录 -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> 最近积分变动记录
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_transactions)): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-graph-down fs-1"></i>
                                    <h5>暂无积分变动记录</h5>
                                    <p>还没有积分变动记录</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($transaction['description']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo getPointTypeName($transaction['type']); ?> · 
                                                    <?php echo formatTimeAgo($transaction['created_at']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <?php if (in_array($transaction['type'], ['earn', 'manual_add'])): ?>
                                                    <span class="text-success fw-bold">+<?php echo $transaction['amount']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger fw-bold">-<?php echo $transaction['amount']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
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
        
        // 表单提交处理
        document.querySelectorAll('button[name="adjust_type"]').forEach(button => {
            button.addEventListener('click', function(e) {
                const amount = document.getElementById('amount').value;
                const type = document.getElementById('type').value;
                const description = document.getElementById('description').value;
                const adjustType = this.value;
                
                if (!amount || !description) {
                    e.preventDefault();
                    alert('请填写完整的调整信息');
                    return false;
                }
                
                // 设置调整类型
                document.getElementById('type').value = adjustType === 'add' ? 'manual_add' : 'manual_subtract';
                
                // 确认操作
                const action = adjustType === 'add' ? '加分' : '扣分';
                if (!confirm(`确定要${action} ${amount} 积分吗？`)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
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