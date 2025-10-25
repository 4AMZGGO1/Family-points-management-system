<?php
/**
 * 小程序API接口
 * 积分管理系统 - 微信小程序后端API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';
require_once '../includes/functions.php';

// 获取请求方法和路径
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// 路由处理
try {
    switch ($path) {
        case '/user/info':
            if ($method === 'GET') {
                handleGetUserInfo();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/tasks/list':
            if ($method === 'GET') {
                handleGetTasks();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/tasks/categories':
            if ($method === 'GET') {
                handleGetCategories();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/submissions/pending':
            if ($method === 'GET') {
                handleGetPendingSubmissions();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/submissions/create':
            if ($method === 'POST') {
                handleCreateSubmission();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/transactions/recent':
            if ($method === 'GET') {
                handleGetRecentTransactions();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/shop/items':
            if ($method === 'GET') {
                handleGetShopItems();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/shop/purchase':
            if ($method === 'POST') {
                handlePurchaseItem();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        case '/upload/image':
            if ($method === 'POST') {
                handleUploadImage();
            } else {
                sendError('Method not allowed', 405);
            }
            break;
            
        default:
            sendError('API endpoint not found', 404);
            break;
    }
} catch (Exception $e) {
    sendError('Internal server error: ' . $e->getMessage(), 500);
}

/**
 * 获取用户信息
 */
function handleGetUserInfo() {
    $user_id = getRequestParam('user_id', 1);
    $user_info = getUserInfo($user_id);
    
    if (!$user_info) {
        sendError('User not found', 404);
        return;
    }
    
    sendSuccess([
        'id' => $user_info['id'],
        'username' => $user_info['username'],
        'points' => $user_info['points'],
        'role' => $user_info['role']
    ]);
}

/**
 * 获取任务列表
 */
function handleGetTasks() {
    $tasks = getAllTasks(true);
    
    $formatted_tasks = array_map(function($task) {
        return [
            'id' => $task['id'],
            'title' => $task['title'],
            'description' => $task['description'],
            'category' => $task['category'],
            'score' => $task['score'],
            'created_at' => $task['created_at']
        ];
    }, $tasks);
    
    sendSuccess($formatted_tasks);
}

/**
 * 获取任务分类
 */
function handleGetCategories() {
    $categories = getTaskCategories();
    sendSuccess($categories);
}

/**
 * 获取待审核申请
 */
function handleGetPendingSubmissions() {
    $user_id = getRequestParam('user_id', 1);
    $submissions = getUserSubmissions($user_id, 'pending');
    
    $formatted_submissions = array_map(function($submission) {
        return [
            'id' => $submission['id'],
            'title' => $submission['title'],
            'category' => $submission['category'],
            'score' => $submission['score'],
            'description' => $submission['description'],
            'status' => $submission['status'],
            'created_at' => $submission['created_at']
        ];
    }, $submissions);
    
    sendSuccess($formatted_submissions);
}

/**
 * 创建申请
 */
function handleCreateSubmission() {
    $user_id = getRequestParam('user_id', 1);
    $task_id = getRequestParam('task_id');
    $description = getRequestParam('description');
    $proof_images = getRequestParam('proof_images', []);
    
    if (!$task_id || !$description) {
        sendError('Missing required parameters', 400);
        return;
    }
    
    // 验证任务是否存在
    $task = getTaskById($task_id);
    if (!$task || !$task['is_active']) {
        sendError('Task not found or inactive', 404);
        return;
    }
    
    // 创建申请
    $submission_id = submitPointsApplication($user_id, $task_id, $description, json_encode($proof_images));
    
    if ($submission_id) {
        sendSuccess(['submission_id' => $submission_id], '申请提交成功');
    } else {
        sendError('Failed to create submission', 500);
    }
}

/**
 * 获取最近积分变动
 */
function handleGetRecentTransactions() {
    $user_id = getRequestParam('user_id', 1);
    $limit = getRequestParam('limit', 5);
    
    $transactions = getUserPointTransactions($user_id, $limit);
    
    $formatted_transactions = array_map(function($transaction) {
        return [
            'id' => $transaction['id'],
            'description' => $transaction['description'],
            'amount' => $transaction['amount'],
            'type' => $transaction['type'],
            'created_at' => $transaction['created_at']
        ];
    }, $transactions);
    
    sendSuccess($formatted_transactions);
}

/**
 * 获取商城物品
 */
function handleGetShopItems() {
    $items = getShopItems();
    
    $formatted_items = array_map(function($item) {
        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => $item['price'],
            'stock' => $item['stock'],
            'image' => $item['image'],
            'is_active' => $item['is_active']
        ];
    }, $items);
    
    sendSuccess($formatted_items);
}

/**
 * 购买物品
 */
function handlePurchaseItem() {
    $user_id = getRequestParam('user_id', 1);
    $item_id = getRequestParam('item_id');
    $quantity = getRequestParam('quantity', 1);
    
    if (!$item_id || $quantity <= 0) {
        sendError('Invalid parameters', 400);
        return;
    }
    
    $result = purchaseItem($user_id, $item_id, $quantity);
    
    if ($result) {
        sendSuccess([], '购买成功');
    } else {
        sendError('Purchase failed', 500);
    }
}

/**
 * 上传图片
 */
function handleUploadImage() {
    if (!isset($_FILES['image'])) {
        sendError('No image uploaded', 400);
        return;
    }
    
    $file = $_FILES['image'];
    $result = handleFileUpload($file);
    
    if ($result) {
        sendSuccess(['url' => $result], '图片上传成功');
    } else {
        sendError('Image upload failed', 500);
    }
}

/**
 * 获取请求参数
 */
function getRequestParam($key, $default = null) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        return isset($input[$key]) ? $input[$key] : $default;
    }
}

/**
 * 发送成功响应
 */
function sendSuccess($data = [], $message = 'Success') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 发送错误响应
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * 根据ID获取任务
 */
function getTaskById($task_id) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>