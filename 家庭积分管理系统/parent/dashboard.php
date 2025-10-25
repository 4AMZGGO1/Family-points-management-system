<?php
/**
 * 家长端管理首页
 * 积分管理系统 - 家长管理后台
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态和权限
requireRole('parent');

$user_id = $_SESSION['user_id'];

// 获取系统统计信息
$stats = getSystemStats();

// 获取待审核申请
$pending_submissions = getPendingSubmissions();

// 获取所有孩子用户
$db = getDBConnection();
$result = $db->query("SELECT * FROM users WHERE role = 'child' ORDER BY points DESC");
$children = [];
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

// 获取最近的积分变动记录
$result = $db->query("SELECT pt.*, u.username FROM point_transactions pt 
                     JOIN users u ON pt.user_id = u.id 
                     WHERE u.role = 'child' 
                     ORDER BY pt.created_at DESC 
                     LIMIT 10");
$recent_transactions = [];
while ($row = $result->fetch_assoc()) {
    $recent_transactions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 家庭积分管理系统</title>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .quick-action {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: inherit;
            text-decoration: none;
            border-color: #667eea;
        }
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .child-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .child-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .child-points {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
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
        .badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
        }
        .badge-approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .badge-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
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
                        <a class="nav-link active" href="dashboard.php">
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
            <!-- 欢迎信息 -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white text-center mb-0">
                        <i class="bi bi-speedometer2"></i> 管理后台首页
                    </h2>
                </div>
            </div>

            <!-- 统计信息 -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['pending_submissions']; ?></div>
                        <div>待审核申请</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['today_submissions']; ?></div>
                        <div>今日新增申请</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_points_earned']; ?></div>
                        <div>总发放积分</div>
                    </div>
                </div>
            </div>

            <!-- 快速操作 -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <a href="review.php" class="quick-action d-block">
                        <div class="quick-action-icon text-warning">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h5>审核申请</h5>
                        <p class="text-muted mb-0">审核孩子的积分申请</p>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="rules.php" class="quick-action d-block">
                        <div class="quick-action-icon text-success">
                            <i class="bi bi-list-check"></i>
                        </div>
                        <h5>管理规则</h5>
                        <p class="text-muted mb-0">设置积分任务规则</p>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="shop_admin.php" class="quick-action d-block">
                        <div class="quick-action-icon text-info">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h5>商城管理</h5>
                        <p class="text-muted mb-0">管理积分商城商品</p>
                    </a>
                </div>
                <div class="col-md-3 mb-3">
                    <a href="adjust_points.php" class="quick-action d-block">
                        <div class="quick-action-icon text-primary">
                            <i class="bi bi-plus-minus"></i>
                        </div>
                        <h5>调整积分</h5>
                        <p class="text-muted mb-0">手动调整孩子积分</p>
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- 待审核申请 -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-clock"></i> 待审核申请
                                <?php if ($stats['pending_submissions'] > 0): ?>
                                    <span class="badge bg-light text-dark ms-2"><?php echo $stats['pending_submissions']; ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending_submissions)): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-check-circle fs-1"></i>
                                    <p class="mb-0">暂无待审核申请</p>
                                </div>
                            <?php else: ?>
                                <?php foreach (array_slice($pending_submissions, 0, 5) as $submission): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($submission['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($submission['username']); ?> · 
                                                    <?php echo htmlspecialchars($submission['category']); ?> · 
                                                    +<?php echo $submission['score']; ?>积分
                                                </small>
                                            </div>
                                            <span class="badge badge-pending">
                                                <?php echo getSubmissionStatusName($submission['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($pending_submissions) > 5): ?>
                                    <div class="p-3 text-center">
                                        <a href="review.php" class="btn btn-outline-warning btn-sm">
                                            查看全部 <?php echo count($pending_submissions); ?> 个申请
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 孩子积分排行 -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-trophy"></i> 孩子积分排行
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($children)): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-person-x fs-1"></i>
                                    <p class="mb-0">暂无孩子用户</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($children as $index => $child): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <?php if ($index < 3): ?>
                                                        <i class="bi bi-trophy-fill text-warning fs-4"></i>
                                                    <?php else: ?>
                                                        <span class="fw-bold text-muted"><?php echo $index + 1; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($child['username']); ?></h6>
                                                    <small class="text-muted">
                                                        注册时间: <?php echo date('Y-m-d', strtotime($child['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="child-points">
                                                <?php echo $child['points']; ?> 积分
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近积分变动 -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-graph-up"></i> 最近积分变动
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_transactions)): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-graph-down fs-1"></i>
                                    <p class="mb-0">暂无积分变动记录</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($transaction['description']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($transaction['username']); ?> · 
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
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card, .quick-action');
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