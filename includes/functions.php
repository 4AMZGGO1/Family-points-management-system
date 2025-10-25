<?php
/**
 * 常用函数库
 * 积分管理系统 - 通用函数
 */

require_once 'config.php';

/**
 * 获取用户信息
 */
function getUserInfo($user_id) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * 获取所有积分任务
 */
function getAllTasks($active_only = true) {
    $db = getDBConnection();
    $sql = "SELECT * FROM tasks";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY category, score DESC";
    
    $result = $db->query($sql);
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    return $tasks;
}

/**
 * 按分类获取任务
 */
function getTasksByCategory($category) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE category = ? AND is_active = 1 ORDER BY score DESC");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    return $tasks;
}

/**
 * 获取任务分类列表
 */
function getTaskCategories() {
    $db = getDBConnection();
    $result = $db->query("SELECT DISTINCT category FROM tasks WHERE is_active = 1 ORDER BY category");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

/**
 * 提交积分申请
 */
function submitPointsApplication($user_id, $task_id, $description, $proof_image = null) {
    $db = getDBConnection();
    
    $stmt = $db->prepare("INSERT INTO submissions (user_id, task_id, description, proof_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $task_id, $description, $proof_image);
    
    if ($stmt->execute()) {
        return $db->insert_id;
    }
    return false;
}

/**
 * 获取用户的申请记录
 */
function getUserSubmissions($user_id, $status = null) {
    $db = getDBConnection();
    
    $sql = "SELECT s.*, t.title, t.score, t.category 
            FROM submissions s 
            JOIN tasks t ON s.task_id = t.id 
            WHERE s.user_id = ?";
    
    if ($status) {
        $sql .= " AND s.status = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("is", $user_id, $status);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    return $submissions;
}

/**
 * 获取待审核的申请
 */
function getPendingSubmissions() {
    $db = getDBConnection();
    $sql = "SELECT s.*, t.title, t.score, t.category, u.username 
            FROM submissions s 
            JOIN tasks t ON s.task_id = t.id 
            JOIN users u ON s.user_id = u.id 
            WHERE s.status = 'pending' 
            ORDER BY s.created_at DESC";
    
    $result = $db->query($sql);
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    return $submissions;
}

/**
 * 审核申请
 */
function reviewSubmission($submission_id, $status, $parent_remark = '') {
    $db = getDBConnection();
    
    if ($status === 'approved') {
        // 获取申请信息
        $stmt = $db->prepare("SELECT s.user_id, t.score, t.title 
                              FROM submissions s 
                              JOIN tasks t ON s.task_id = t.id 
                              WHERE s.id = ? AND s.status = 'pending'");
        $stmt->bind_param("i", $submission_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($submission = $result->fetch_assoc()) {
            $user_id = $submission['user_id'];
            $task_score = $submission['score'];
            $task_title = $submission['title'];
            
            $db->begin_transaction();
            
            try {
                // 更新申请状态
                $stmt = $db->prepare("UPDATE submissions SET status = ?, parent_remark = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("ssi", $status, $parent_remark, $submission_id);
                $stmt->execute();
                
                // 更新用户积分
                $stmt = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $stmt->bind_param("ii", $task_score, $user_id);
                $stmt->execute();
                
                // 记录积分变动
                $stmt = $db->prepare("INSERT INTO point_transactions (user_id, type, amount, description, related_id) VALUES (?, 'earn', ?, ?, ?)");
                $description = "完成任务: " . $task_title;
                $stmt->bind_param("iisi", $user_id, $task_score, $description, $submission_id);
                $stmt->execute();
                
                $db->commit();
                return true;
                
            } catch (Exception $e) {
                $db->rollback();
                return false;
            }
        }
        return false;
    } else {
        // 拒绝申请
        $stmt = $db->prepare("UPDATE submissions SET status = ?, parent_remark = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssi", $status, $parent_remark, $submission_id);
        return $stmt->execute();
    }
}

/**
 * 获取商城物品
 */
function getShopItems($active_only = true) {
    $db = getDBConnection();
    $sql = "SELECT * FROM shop_items";
    if ($active_only) {
        $sql .= " WHERE is_active = 1 AND stock > 0";
    }
    $sql .= " ORDER BY cost ASC";
    
    $result = $db->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

/**
 * 购买商品
 */
function purchaseItem($user_id, $item_id, $quantity = 1) {
    $db = getDBConnection();
    
    // 开始事务
    $db->begin_transaction();
    
    try {
        // 获取商品信息
        $stmt = $db->prepare("SELECT cost, stock FROM shop_items WHERE id = ? AND is_active = 1");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        
        if (!$item || $item['stock'] < $quantity) {
            throw new Exception("商品库存不足");
        }
        
        $total_cost = $item['cost'] * $quantity;
        
        // 检查用户积分是否足够
        $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user['points'] < $total_cost) {
            throw new Exception("积分不足");
        }
        
        // 插入购买记录
        $stmt = $db->prepare("INSERT INTO purchases (user_id, item_id, quantity, total_cost) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $user_id, $item_id, $quantity, $total_cost);
        $stmt->execute();
        $purchase_id = $db->insert_id;
        
        // 扣除用户积分
        $stmt = $db->prepare("UPDATE users SET points = points - ? WHERE id = ?");
        $stmt->bind_param("ii", $total_cost, $user_id);
        $stmt->execute();
        
        // 记录积分变动
        $stmt = $db->prepare("INSERT INTO point_transactions (user_id, type, amount, description, related_id) VALUES (?, 'spend', ?, '购买商品', ?)");
        $stmt->bind_param("iii", $user_id, $total_cost, $purchase_id);
        $stmt->execute();
        
        // 减少库存
        $stmt = $db->prepare("UPDATE shop_items SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $item_id);
        $stmt->execute();
        
        // 提交事务
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * 获取用户购买记录
 */
function getUserPurchases($user_id) {
    $db = getDBConnection();
    $sql = "SELECT p.*, si.name, si.description 
            FROM purchases p 
            JOIN shop_items si ON p.item_id = si.id 
            WHERE p.user_id = ? 
            ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $purchases = [];
    while ($row = $result->fetch_assoc()) {
        $purchases[] = $row;
    }
    return $purchases;
}

/**
 * 获取用户积分变动记录
 */
function getUserPointTransactions($user_id, $limit = 50) {
    $db = getDBConnection();
    $sql = "SELECT * FROM point_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    return $transactions;
}

/**
 * 手动调整用户积分
 */
function adjustUserPoints($user_id, $amount, $type, $description) {
    $db = getDBConnection();
    
    $db->begin_transaction();
    
    try {
        // 更新用户积分
        if ($type === 'manual_add') {
            $sql = "UPDATE users SET points = points + ? WHERE id = ?";
        } else {
            $sql = "UPDATE users SET points = points - ? WHERE id = ?";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $amount, $user_id);
        $stmt->execute();
        
        // 记录积分变动
        $stmt = $db->prepare("INSERT INTO point_transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $user_id, $type, $amount, $description);
        $stmt->execute();
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

/**
 * 获取系统统计信息
 */
function getSystemStats() {
    $db = getDBConnection();
    
    $stats = [];
    
    // 总用户数
    $result = $db->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'child'");
    $stats['total_users'] = $result->fetch_assoc()['total_users'];
    
    // 待审核申请数
    $result = $db->query("SELECT COUNT(*) as pending_submissions FROM submissions WHERE status = 'pending'");
    $stats['pending_submissions'] = $result->fetch_assoc()['pending_submissions'];
    
    // 今日新增申请
    $result = $db->query("SELECT COUNT(*) as today_submissions FROM submissions WHERE DATE(created_at) = CURDATE()");
    $stats['today_submissions'] = $result->fetch_assoc()['today_submissions'];
    
    // 总积分发放
    $result = $db->query("SELECT SUM(amount) as total_points_earned FROM point_transactions WHERE type IN ('earn', 'manual_add')");
    $stats['total_points_earned'] = $result->fetch_assoc()['total_points_earned'] ?? 0;
    
    return $stats;
}

/**
 * 上传文件处理
 */
function handleFileUpload($file, $upload_dir = null) {
    // 如果没有指定上传目录，使用配置中的路径
    if ($upload_dir === null) {
        // 始终使用项目根目录的绝对路径
        // 使用realpath确保路径正确
        $project_root = realpath(dirname(__DIR__));
        $upload_dir = $project_root . '/' . UPLOAD_PATH;
    }
    
    // 确保路径是绝对路径
    if (!is_dir($upload_dir)) {
        error_log("尝试创建上传目录: " . $upload_dir);
        
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("无法创建上传目录: " . $upload_dir);
            return false;
        }
    }
    
    error_log("使用上传目录: " . $upload_dir);
    
    // 确保路径以斜杠结尾
    if (substr($upload_dir, -1) !== '/') {
        $upload_dir .= '/';
    }
    
    // 检查文件是否上传成功
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("文件上传错误: " . $file['error']);
        return false;
    }
    
    // 检查文件大小
    if ($file['size'] > MAX_FILE_SIZE) {
        error_log("文件大小超过限制: " . $file['size'] . " > " . MAX_FILE_SIZE);
        return false;
    }
    
    // 检查文件类型
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        error_log("不支持的文件类型: " . $mime_type);
        return false;
    }
    
    // 生成唯一文件名
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // 确保上传目录存在且可写
    if (!is_writable($upload_dir)) {
        error_log("上传目录不可写: " . $upload_dir);
        return false;
    }
    
    // 移动文件
    error_log("尝试移动文件: " . $file['tmp_name'] . " -> " . $filepath);
    error_log("临时文件存在: " . (file_exists($file['tmp_name']) ? "是" : "否"));
    error_log("目标目录可写: " . (is_writable($upload_dir) ? "是" : "否"));
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        error_log("文件上传成功: " . $filepath);
        return $filename;
    }
    
    error_log("文件移动失败: " . $file['tmp_name'] . " -> " . $filepath);
    error_log("错误信息: " . error_get_last()['message']);
    return false;
}

/**
 * 格式化时间显示
 */
function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return '刚刚';
    } elseif ($time < 3600) {
        return floor($time / 60) . '分钟前';
    } elseif ($time < 86400) {
        return floor($time / 3600) . '小时前';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . '天前';
    } else {
        return date('Y-m-d', strtotime($datetime));
    }
}

/**
 * 获取积分类型的中文名称
 */
function getPointTypeName($type) {
    $types = [
        'earn' => '获得积分',
        'spend' => '消费积分',
        'manual_add' => '手动加分',
        'manual_subtract' => '手动扣分'
    ];
    
    return $types[$type] ?? $type;
}

/**
 * 获取申请状态的中文名称
 */
function getSubmissionStatusName($status) {
    $statuses = [
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * 获取申请状态的CSS类
 */
function getSubmissionStatusClass($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger'
    ];
    
    return $classes[$status] ?? 'secondary';
}
?>