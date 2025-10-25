<?php
/**
 * 孩子端历史记录页面
 * 积分管理系统 - 积分历史记录
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
$user_info = getUserInfo($user_id);

// 获取积分变动记录
$transactions = getUserPointTransactions($user_id, 100);

// 获取申请记录
$submissions = getUserSubmissions($user_id);

// 获取购买记录
$purchases = getUserPurchases($user_id);

// 统计信息
$total_earned = 0;
$total_spent = 0;
foreach ($transactions as $transaction) {
    if (in_array($transaction['type'], ['earn', 'manual_add'])) {
        $total_earned += $transaction['amount'];
    } else {
        $total_spent += $transaction['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史记录 - 家庭积分管理系统</title>
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
            margin-bottom: 2rem;
        }
        .stat-item {
            padding: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
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
        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .transaction-icon.earn {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        .transaction-icon.spend {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .tab-content {
            border-radius: 0 0 15px 15px;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
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
                        <a class="nav-link" href="apply.php">
                            <i class="bi bi-plus-circle"></i> 申请积分
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">
                            <i class="bi bi-shop"></i> 积分商城
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="history.php">
                            <i class="bi bi-clock-history"></i> 历史记录
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
            <!-- 统计信息 -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $user_info['points']; ?></div>
                        <div>当前积分</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $total_earned; ?></div>
                        <div>累计获得</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card">
                        <div class="stat-number"><?php echo $total_spent; ?></div>
                        <div>累计消费</div>
                    </div>
                </div>
            </div>

            <!-- 记录分类 -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="historyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="transactions-tab" data-bs-toggle="tab" 
                                    data-bs-target="#transactions" type="button" role="tab">
                                <i class="bi bi-graph-up"></i> 积分变动
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="submissions-tab" data-bs-toggle="tab" 
                                    data-bs-target="#submissions" type="button" role="tab">
                                <i class="bi bi-file-earmark-check"></i> 申请记录
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" 
                                    data-bs-target="#purchases" type="button" role="tab">
                                <i class="bi bi-cart-check"></i> 购买记录
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="historyTabsContent">
                    <!-- 积分变动记录 -->
                    <div class="tab-pane fade show active" id="transactions" role="tabpanel">
                        <div class="card-body p-0">
                            <?php if (empty($transactions)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-graph-down"></i>
                                    <h5>暂无积分变动记录</h5>
                                    <p>完成第一个任务后，这里会显示你的积分变动历史</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex align-items-center">
                                            <div class="transaction-icon <?php echo in_array($transaction['type'], ['earn', 'manual_add']) ? 'earn' : 'spend'; ?> me-3">
                                                <?php if (in_array($transaction['type'], ['earn', 'manual_add'])): ?>
                                                    <i class="bi bi-plus-circle"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-dash-circle"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
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

                    <!-- 申请记录 -->
                    <div class="tab-pane fade" id="submissions" role="tabpanel">
                        <div class="card-body p-0">
                            <?php if (empty($submissions)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-file-earmark-x"></i>
                                    <h5>暂无申请记录</h5>
                                    <p>提交第一个积分申请后，这里会显示你的申请历史</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($submissions as $submission): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex align-items-center">
                                            <div class="transaction-icon earn me-3">
                                                <i class="bi bi-file-earmark-check"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($submission['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($submission['category']); ?> · 
                                                    +<?php echo $submission['score']; ?>积分 · 
                                                    <?php echo formatTimeAgo($submission['created_at']); ?>
                                                </small>
                                                <?php if ($submission['description']): ?>
                                                    <div class="mt-1">
                                                        <small class="text-muted"><?php echo htmlspecialchars($submission['description']); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($submission['parent_remark']): ?>
                                                    <div class="mt-1">
                                                        <small class="text-info">
                                                            <i class="bi bi-chat-quote"></i> 
                                                            家长备注: <?php echo htmlspecialchars($submission['parent_remark']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <span class="status-badge bg-<?php echo getSubmissionStatusClass($submission['status']); ?>">
                                                    <?php echo getSubmissionStatusName($submission['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 购买记录 -->
                    <div class="tab-pane fade" id="purchases" role="tabpanel">
                        <div class="card-body p-0">
                            <?php if (empty($purchases)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-cart-x"></i>
                                    <h5>暂无购买记录</h5>
                                    <p>在积分商城购买第一个商品后，这里会显示你的购买历史</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <div class="transaction-item">
                                        <div class="d-flex align-items-center">
                                            <div class="transaction-icon spend me-3">
                                                <i class="bi bi-cart-check"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($purchase['name']); ?></h6>
                                                <small class="text-muted">
                                                    数量: <?php echo $purchase['quantity']; ?> · 
                                                    <?php echo formatTimeAgo($purchase['created_at']); ?>
                                                </small>
                                                <?php if ($purchase['description']): ?>
                                                    <div class="mt-1">
                                                        <small class="text-muted"><?php echo htmlspecialchars($purchase['description']); ?></small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-danger fw-bold">-<?php echo $purchase['total_cost']; ?></span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.transaction-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>