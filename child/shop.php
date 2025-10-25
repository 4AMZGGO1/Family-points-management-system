<?php
/**
 * 孩子端积分商城页面
 * 积分管理系统 - 积分商城
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
$user_points = $user_info['points'];

$success_message = '';
$error_message = '';

// 处理购买
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purchase') {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        if (purchaseItem($user_id, $item_id, $quantity)) {
            $success_message = "购买成功！";
            // 刷新用户积分
            $user_info = getUserInfo($user_id);
            $user_points = $user_info['points'];
        } else {
            $error_message = "购买失败，请检查积分是否足够或商品库存";
        }
    } else {
        $error_message = "购买数量必须大于0";
    }
}

// 获取商城物品
$shop_items = getShopItems();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>积分商城 - 家庭积分管理系统</title>
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
            margin: 0.5rem 0;
        }
        .shop-item {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            background: white;
        }
        .shop-item:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .item-price {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .btn-purchase {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-purchase:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 5px;
        }
        .stock-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .out-of-stock {
            opacity: 0.6;
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
                        <a class="nav-link active" href="shop.php">
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
            <!-- 积分余额 -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <div class="points-card">
                        <h4><i class="bi bi-wallet2"></i> 我的积分余额</h4>
                        <div class="points-number"><?php echo $user_points; ?></div>
                        <p class="mb-0">用积分兑换心仪物品吧！</p>
                    </div>
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

            <!-- 商城物品 -->
            <div class="row">
                <?php if (empty($shop_items)): ?>
                    <div class="col-12">
                        <div class="card text-center">
                            <div class="card-body">
                                <i class="bi bi-shop fs-1 text-muted"></i>
                                <h5 class="text-muted">暂无商品</h5>
                                <p class="text-muted">商城暂时没有可兑换的商品</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($shop_items as $item): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="shop-item <?php echo $item['stock'] <= 0 ? 'out-of-stock' : ''; ?>">
                                <?php if ($item['image']): ?>
                                    <img src="../assets/uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                         class="item-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="item-image bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-gift fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="mb-2"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted mb-3"><?php echo htmlspecialchars($item['description']); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="item-price">
                                        <i class="bi bi-star"></i> <?php echo $item['cost']; ?> 积分
                                    </div>
                                    <div class="stock-info">
                                        <i class="bi bi-box"></i> 库存: <?php echo $item['stock']; ?>
                                    </div>
                                </div>
                                
                                <?php if ($item['stock'] > 0): ?>
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="action" value="purchase">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        
                                        <label class="form-label mb-0">数量:</label>
                                        <input type="number" name="quantity" class="quantity-input" 
                                               value="1" min="1" max="<?php echo min($item['stock'], 10); ?>">
                                        
                                        <button type="submit" class="btn btn-primary btn-purchase flex-grow-1"
                                                <?php echo ($user_points < $item['cost']) ? 'disabled' : ''; ?>>
                                            <?php if ($user_points < $item['cost']): ?>
                                                <i class="bi bi-x-circle"></i> 积分不足
                                            <?php else: ?>
                                                <i class="bi bi-cart-plus"></i> 立即兑换
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="bi bi-x-circle"></i> 暂无库存
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 数量输入验证
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max'));
                const min = parseInt(this.getAttribute('min'));
                let value = parseInt(this.value);
                
                if (value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
            });
        });
        
        // 购买确认
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const quantity = this.querySelector('input[name="quantity"]').value;
                const itemName = this.closest('.shop-item').querySelector('h5').textContent;
                
                if (!confirm(`确定要用 ${quantity} 个积分兑换 ${itemName} 吗？`)) {
                    e.preventDefault();
                }
            });
        });
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const items = document.querySelectorAll('.shop-item');
            items.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>