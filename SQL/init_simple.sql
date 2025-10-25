-- 积分管理系统数据库初始化脚本（简化版）
-- 创建时间: 2024
-- 数据库: MySQL 8.0+

-- 创建数据库
CREATE DATABASE IF NOT EXISTS points_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE points_system;

-- 1. 用户表
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('parent', 'child') NOT NULL DEFAULT 'child',
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. 积分任务规则表
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    score INT NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. 孩子提交申请表
CREATE TABLE submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    proof_image VARCHAR(255),
    description TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    parent_remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- 4. 商城物品表
CREATE TABLE shop_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    cost INT NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. 购买记录表
CREATE TABLE purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_cost INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES shop_items(id) ON DELETE CASCADE
);

-- 6. 积分变动记录表
CREATE TABLE point_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('earn', 'spend', 'manual_add', 'manual_subtract') NOT NULL,
    amount INT NOT NULL,
    description VARCHAR(255),
    related_id INT, -- 关联的submission_id或purchase_id
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 插入示例数据

-- 插入家长用户（密码: admin123）
INSERT INTO users (username, password, role, points) VALUES 
('admin', 'admin123', 'parent', 0);

-- 插入孩子用户（密码: child123）
INSERT INTO users (username, password, role, points) VALUES 
('王文锦', 'child123', 'child', 100);

-- 插入积分任务规则
INSERT INTO tasks (category, title, score, description) VALUES 
-- 学习主动性
('学习主动性', '主动找老师问问题', 5, '主动找老师问问题'),
('学习主动性', '主动找舅舅解决不会的题', 4, '主动找舅舅解决不会的题'),

-- 考试名次
('考试名次', '月考/期中/期末年级前10名', 1000, '月考/期中/期末年级前10名'),
('考试名次', '月考/期中/期末年级第10-20名', 500, '月考/期中/期末年级第10-20名'),
('考试名次', '月考/期中/期末年级第21-30名', 250, '月考/期中/期末年级第21-30名'),
('考试名次', '月考/期中/期末年级第31-50名', 100, '月考/期中/期末年级第31-50名'),

-- 错题整理
('错题整理', '整理错题集（全科）', 2, '每页完整记录并订正'),
('错题整理', '英语生词整理', 2, '每10个单词'),

-- 考试科目加分
('考试科目加分', '数学130+', 50, '月考/期中/期末'),
('考试科目加分', '英语125+', 100, '月考/期中/期末'),
('考试科目加分', '语文115+', 100, '月考/期中/期末'),
('考试科目加分', '政治85+', 30, '月考/期中/期末'),
('考试科目加分', '历史90+', 30, '月考/期中/期末'),

-- 生活自理
('生活自理', '做一次家务', 1, '做一次家务'),
('生活自理', '自己骑自行车上下学', 2, '自己骑自行车上下学'),
('生活自理', '听写过关', 3, '一大周听写2次，需主动找家长'),

-- 字迹检查
('字迹检查', '每周作业字迹合格（每科）', 5, '每周作业字迹合格（每科）'),

-- 高质量作业
('高质量作业', '语文一课练习', 2, '字迹工整+批改订正完整'),
('高质量作业', '数学一页《初中必刷题》', 3, '一页《初中必刷题》'),
('高质量作业', '英语一篇阅读/完形填空/排序', 1, '一篇阅读/完形填空/排序'),
('高质量作业', '英语听力', 2, '英语听力'),
('高质量作业', '语/数/外一张完整试卷', 5, '字迹工整+订正完整'),

-- 扣分项目
('扣分项目', '月考/期中/期末年级掉出80名以外', -30, '月考/期中/期末年级掉出80名以外'),
('扣分项目', '英语<90分', -20, '英语<90分'),
('扣分项目', '数学<110分', -30, '数学<110分'),
('扣分项目', '语文<90分', -20, '语文<90分'),
('扣分项目', '每周每科字迹丑陋', -30, '每周每科字迹丑陋');

-- 插入商城物品
INSERT INTO shop_items (name, cost, stock, description) VALUES 
('肯德基套餐（50元左右）', 60, 999, '肯德基套餐（50元左右）'),
('bilibili会员一个月', 20, 999, 'bilibili会员一个月'),
('Netflix会员一个月', 40, 999, 'Netflix会员一个月'),
('大陆不可看动漫 12集', 15, 999, '大陆不可看动漫 12集'),
('轻小说（一本）', 15, 999, '轻小说（一本）'),
('天空之城游戏厅100币', 60, 999, '天空之城游戏厅100币'),
('寒假去北京', 1300, 1, '寒假去北京'),
('日本旅游', 2500, 1, '日本旅游'),
('《荒野大镖客》', 300, 1, '《荒野大镖客》'),
('《双人成行/奇境》+舅舅陪玩', 400, 1, '《双人成行/奇境》+舅舅陪玩'),
('游戏手柄', 400, 1, '游戏手柄'),
('笔记本电脑', 6000, 1, '笔记本电脑'),
('ipad', 8000, 1, 'ipad'),
('电视', 150000, 1, '电视');

-- 创建索引优化查询性能
CREATE INDEX idx_submissions_user_id ON submissions(user_id);
CREATE INDEX idx_submissions_status ON submissions(status);
CREATE INDEX idx_point_transactions_user_id ON point_transactions(user_id);
CREATE INDEX idx_point_transactions_type ON point_transactions(type);
CREATE INDEX idx_purchases_user_id ON purchases(user_id);

-- 创建视图：用户积分统计
CREATE VIEW user_points_summary AS
SELECT 
    u.id,
    u.username,
    u.role,
    u.points as current_points,
    COALESCE(SUM(CASE WHEN pt.type IN ('earn', 'manual_add') THEN pt.amount ELSE 0 END), 0) as total_earned,
    COALESCE(SUM(CASE WHEN pt.type IN ('spend', 'manual_subtract') THEN pt.amount ELSE 0 END), 0) as total_spent
FROM users u
LEFT JOIN point_transactions pt ON u.id = pt.user_id
GROUP BY u.id, u.username, u.role, u.points;