<?php
/**
 * 孩子端主入口页面
 * 积分管理系统 - 孩子端首页（无需登录）
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// 设置默认孩子用户ID（无需登录）
$db = getDBConnection();
$result = $db->query("SELECT id, username FROM users WHERE role = 'child' LIMIT 1");
if ($child = $result->fetch_assoc()) {
    $_SESSION['user_id'] = $child['id'];
    $_SESSION['username'] = $child['username'];
    $_SESSION['user_role'] = 'child';
} else {
    // 如果没有孩子用户，使用默认值
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = '王文锦';
    $_SESSION['user_role'] = 'child';
}

// 获取用户信息
$user_id = $_SESSION['user_id'];
$user_info = getUserInfo($user_id);
$user_points = $user_info['points'];

// 获取最近的积分变动记录
$recent_transactions = getUserPointTransactions($user_id, 5);

// 获取待审核的申请
$pending_submissions = getUserSubmissions($user_id, 'pending');

// 获取任务分类
$categories = getTaskCategories();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的积分 - 家庭积分管理系统</title>
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
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .points-number {
            font-size: 3rem;
            font-weight: bold;
            margin: 1rem 0;
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
        }
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            color: inherit;
            text-decoration: none;
        }
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
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
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .admin-link {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- 管理员入口 -->
    <div class="admin-link">
        <a href="parent/login.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-shield-lock"></i> 家长入口
        </a>
    </div>

    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-star-fill text-primary"></i> 积分系统
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house"></i> 首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="child/apply.php">
                            <i class="bi bi-plus-circle"></i> 申请积分
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="child/shop.php">
                            <i class="bi bi-shop"></i> 积分商城
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="child/history.php">
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
            <!-- 欢迎信息 -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white text-center mb-0">
                        <i class="bi bi-sun"></i> 欢迎回来，<?php echo htmlspecialchars($_SESSION['username']); ?>！
                    </h2>
                </div>
            </div>

            <!-- 积分卡片 -->
            <div class="row mb-4">
                <div class="col-md-8 mx-auto">
                    <div class="points-card">
                        <h3><i class="bi bi-trophy"></i> 我的积分</h3>
                        <div class="points-number"><?php echo $user_points; ?></div>
                        <p class="mb-0">继续努力，获得更多积分吧！</p>
                    </div>
                </div>
            </div>

            <!-- 快速操作 -->
            <div class="row mb-4 justify-content-center">
                <div class="col-md-3 col-lg-2 mb-3">
                    <a href="child/apply.php" class="quick-action d-block">
                        <div class="quick-action-icon text-success">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <h5>申请积分</h5>
                        <p class="text-muted mb-0">完成任务获得积分</p>
                    </a>
                </div>
                <div class="col-md-3 col-lg-2 mb-3">
                    <a href="child/shop.php" class="quick-action d-block">
                        <div class="quick-action-icon text-warning">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h5>积分商城</h5>
                        <p class="text-muted mb-0">兑换心仪物品</p>
                    </a>
                </div>
                <div class="col-md-3 col-lg-2 mb-3">
                    <a href="child/history.php" class="quick-action d-block">
                        <div class="quick-action-icon text-info">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h5>历史记录</h5>
                        <p class="text-muted mb-0">查看积分变动</p>
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
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending_submissions)): ?>
                                <div class="p-3 text-center text-muted">
                                    <i class="bi bi-check-circle fs-1"></i>
                                    <p class="mb-0">暂无待审核申请</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pending_submissions as $submission): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($submission['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($submission['category']); ?> · 
                                                    +<?php echo $submission['score']; ?>积分
                                                </small>
                                            </div>
                                            <span class="status-badge bg-warning text-dark">
                                                <?php echo getSubmissionStatusName($submission['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 最近积分变动 -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
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

            <!-- 任务分类快速入口 -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-list-check"></i> 任务分类
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($categories as $category): ?>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="child/apply.php?category=<?php echo urlencode($category); ?>" 
                                           class="btn btn-outline-primary w-100">
                                            <i class="bi bi-tag"></i> <?php echo htmlspecialchars($category); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 添加页面加载动画
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