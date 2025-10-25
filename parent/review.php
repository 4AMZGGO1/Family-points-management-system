<?php
/**
 * 家长端审核申请页面
 * 积分管理系统 - 审核孩子申请
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 检查登录状态和权限
requireRole('parent');

$success_message = '';
$error_message = '';

// 处理审核操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $submission_id = (int)$_POST['submission_id'];
    $action = $_POST['action'];
    $parent_remark = sanitizeInput($_POST['parent_remark'] ?? '');
    
    if ($action === 'approve') {
        if (reviewSubmission($submission_id, 'approved', $parent_remark)) {
            $success_message = "申请已通过，积分已发放！";
        } else {
            $error_message = "操作失败，请重试";
        }
    } elseif ($action === 'reject') {
        if (reviewSubmission($submission_id, 'rejected', $parent_remark)) {
            $success_message = "申请已拒绝";
        } else {
            $error_message = "操作失败，请重试";
        }
    }
}

// 获取待审核申请
$pending_submissions = getPendingSubmissions();

// 获取所有申请记录（用于查看历史）
$db = getDBConnection();
$result = $db->query("SELECT s.*, t.title, t.score, t.category, u.username 
                     FROM submissions s 
                     JOIN tasks t ON s.task_id = t.id 
                     JOIN users u ON s.user_id = u.id 
                     ORDER BY s.created_at DESC");
$all_submissions = [];
while ($row = $result->fetch_assoc()) {
    $all_submissions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>审核申请 - 家庭积分管理系统</title>
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
        .submission-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .submission-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .submission-card.pending {
            border-left: 5px solid #ffc107;
        }
        .submission-card.approved {
            border-left: 5px solid #28a745;
        }
        .submission-card.rejected {
            border-left: 5px solid #dc3545;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        .status-pending {
            background: linear-gradient(135deg, #ffc107 0%, #ff8c00 100%);
            color: white;
        }
        .status-approved {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .status-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }
        .btn-approve {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-reject {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }
        .proof-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .proof-image:hover {
            transform: scale(1.05);
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
                        <a class="nav-link active" href="review.php">
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
            <!-- 页面标题 -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-white text-center mb-0">
                        <i class="bi bi-check-circle"></i> 审核申请
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

            <!-- 申请列表 -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="submissionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" 
                                    data-bs-target="#pending" type="button" role="tab">
                                <i class="bi bi-clock"></i> 待审核
                                <?php if (count($pending_submissions) > 0): ?>
                                    <span class="badge bg-warning text-dark ms-2"><?php echo count($pending_submissions); ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="all-tab" data-bs-toggle="tab" 
                                    data-bs-target="#all" type="button" role="tab">
                                <i class="bi bi-list"></i> 全部申请
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="submissionTabsContent">
                    <!-- 待审核申请 -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <div class="card-body">
                            <?php if (empty($pending_submissions)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-check-circle fs-1"></i>
                                    <h5>暂无待审核申请</h5>
                                    <p>所有申请都已处理完毕</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pending_submissions as $submission): ?>
                                    <div class="submission-card pending">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="mb-2"><?php echo htmlspecialchars($submission['title']); ?></h5>
                                                        <div class="mb-2">
                                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($submission['category']); ?></span>
                                                            <span class="badge bg-success">+<?php echo $submission['score']; ?> 积分</span>
                                                        </div>
                                                        <p class="text-muted mb-2">
                                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($submission['username']); ?> · 
                                                            <i class="bi bi-clock"></i> <?php echo formatTimeAgo($submission['created_at']); ?>
                                                        </p>
                                                    </div>
                                                    <span class="status-badge status-pending">
                                                        <?php echo getSubmissionStatusName($submission['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($submission['description']): ?>
                                                    <div class="mb-3">
                                                        <h6>任务完成情况：</h6>
                                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($submission['description'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($submission['proof_image']): ?>
                                                    <div class="mb-3">
                                                        <h6>证明图片：</h6>
                                                        <img src="../assets/uploads/<?php echo htmlspecialchars($submission['proof_image']); ?>" 
                                                             class="proof-image" alt="证明图片"
                                                             onclick="showImageModal(this.src)">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <form method="POST" class="mb-3">
                                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="remark_<?php echo $submission['id']; ?>" class="form-label">审核备注：</label>
                                                        <textarea class="form-control" id="remark_<?php echo $submission['id']; ?>" 
                                                                  name="parent_remark" rows="3" 
                                                                  placeholder="请输入审核备注（可选）"></textarea>
                                                    </div>
                                                    
                                                    <div class="d-grid gap-2">
                                                        <button type="submit" name="action" value="approve" 
                                                                class="btn btn-success btn-approve"
                                                                onclick="return confirm('确定要通过这个申请吗？')">
                                                            <i class="bi bi-check-circle"></i> 通过申请
                                                        </button>
                                                        <button type="submit" name="action" value="reject" 
                                                                class="btn btn-danger btn-reject"
                                                                onclick="return confirm('确定要拒绝这个申请吗？')">
                                                            <i class="bi bi-x-circle"></i> 拒绝申请
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 全部申请 -->
                    <div class="tab-pane fade" id="all" role="tabpanel">
                        <div class="card-body">
                            <?php if (empty($all_submissions)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-file-earmark-x fs-1"></i>
                                    <h5>暂无申请记录</h5>
                                    <p>还没有孩子提交积分申请</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($all_submissions as $submission): ?>
                                    <div class="submission-card <?php echo $submission['status']; ?>">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h5 class="mb-2"><?php echo htmlspecialchars($submission['title']); ?></h5>
                                                        <div class="mb-2">
                                                            <span class="badge bg-primary me-2"><?php echo htmlspecialchars($submission['category']); ?></span>
                                                            <span class="badge bg-success">+<?php echo $submission['score']; ?> 积分</span>
                                                        </div>
                                                        <p class="text-muted mb-2">
                                                            <i class="bi bi-person"></i> <?php echo htmlspecialchars($submission['username']); ?> · 
                                                            <i class="bi bi-clock"></i> <?php echo formatTimeAgo($submission['created_at']); ?>
                                                        </p>
                                                    </div>
                                                    <span class="status-badge status-<?php echo $submission['status']; ?>">
                                                        <?php echo getSubmissionStatusName($submission['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if ($submission['description']): ?>
                                                    <div class="mb-3">
                                                        <h6>任务完成情况：</h6>
                                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($submission['description'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($submission['parent_remark']): ?>
                                                    <div class="mb-3">
                                                        <h6>家长备注：</h6>
                                                        <p class="text-info"><?php echo nl2br(htmlspecialchars($submission['parent_remark'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($submission['proof_image']): ?>
                                                    <div class="mb-3">
                                                        <h6>证明图片：</h6>
                                                        <img src="../assets/uploads/<?php echo htmlspecialchars($submission['proof_image']); ?>" 
                                                             class="proof-image" alt="证明图片"
                                                             onclick="showImageModal(this.src)">
                                                    </div>
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

    <!-- 图片预览模态框 -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">证明图片</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="证明图片">
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
        
        function showImageModal(src) {
            document.getElementById('modalImage').src = src;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }
        
        // 页面加载动画
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.submission-card');
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