# 家庭积分管理系统 - 微信小程序版

## 项目简介

这是家庭积分管理系统的微信小程序版本，专为学生端设计。学生可以通过小程序申请积分、查看积分商城、浏览历史记录等功能。

## 功能特性

### 🏠 首页
- 显示当前积分余额
- 快速操作入口（申请积分、积分商城、历史记录）
- 待审核申请列表
- 最近积分变动记录
- 任务分类快速入口

### 📝 申请积分
- 按分类筛选任务
- 选择任务并填写完成描述
- 上传完成证明图片（最多3张）
- 提交申请等待家长审核

### 🛍️ 积分商城
- 浏览可兑换商品
- 调整购买数量
- 确认购买信息
- 使用积分购买商品

### 📊 历史记录
- 查看所有积分变动记录
- 按类型筛选（全部/获得/消费）
- 分页加载更多记录
- 显示详细时间和描述

## 技术栈

- **前端**: 微信小程序原生开发
- **后端**: PHP + MySQL
- **UI框架**: 微信小程序原生组件
- **样式**: WXSS + 自定义样式

## 项目结构

```
miniprogram/
├── app.js                 # 小程序入口文件
├── app.json              # 小程序配置文件
├── app.wxss              # 全局样式文件
├── pages/                # 页面目录
│   ├── home/             # 首页
│   │   ├── home.js
│   │   ├── home.wxml
│   │   └── home.wxss
│   ├── apply/            # 申请积分页面
│   │   ├── apply.js
│   │   ├── apply.wxml
│   │   └── apply.wxss
│   ├── shop/             # 积分商城页面
│   │   ├── shop.js
│   │   ├── shop.wxml
│   │   └── shop.wxss
│   └── history/          # 历史记录页面
│       ├── history.js
│       ├── history.wxml
│       └── history.wxss
└── images/               # 图片资源目录
    ├── home.png
    ├── home-active.png
    ├── apply.png
    ├── apply-active.png
    ├── shop.png
    ├── shop-active.png
    ├── history.png
    └── history-active.png
```

## API接口

### 基础配置
- **Base URL**: `https://your-domain.com/api`
- **请求方式**: GET/POST
- **数据格式**: JSON

### 主要接口

#### 用户相关
- `GET /user/info` - 获取用户信息
- `GET /user/points` - 获取用户积分

#### 任务相关
- `GET /tasks/list` - 获取任务列表
- `GET /tasks/categories` - 获取任务分类

#### 申请相关
- `GET /submissions/pending` - 获取待审核申请
- `POST /submissions/create` - 创建新申请

#### 交易相关
- `GET /transactions/recent` - 获取最近交易记录

#### 商城相关
- `GET /shop/items` - 获取商城物品
- `POST /shop/purchase` - 购买商品

#### 文件上传
- `POST /upload/image` - 上传图片

## 部署说明

### 1. 后端部署
1. 将 `api/` 目录上传到服务器
2. 配置数据库连接信息
3. 确保PHP环境支持文件上传
4. 修改 `app.js` 中的 `baseUrl` 为实际服务器地址

### 2. 小程序部署
1. 下载微信开发者工具
2. 导入 `miniprogram` 目录
3. 配置小程序AppID
4. 修改 `app.js` 中的服务器地址
5. 上传代码并提交审核

### 3. 配置说明
在 `app.js` 中修改以下配置：
```javascript
globalData: {
  baseUrl: 'https://your-domain.com/api', // 替换为你的服务器域名
  userId: 1, // 默认用户ID
  username: 'child1' // 默认用户名
}
```

## 开发指南

### 添加新页面
1. 在 `pages/` 目录下创建新页面文件夹
2. 创建 `.js`, `.wxml`, `.wxss` 文件
3. 在 `app.json` 中注册页面路径
4. 如需要，在 `tabBar` 中添加标签页

### 添加新API
1. 在 `api/index.php` 中添加新的路由处理
2. 实现对应的处理函数
3. 在小程序中调用 `app.request()` 方法

### 样式规范
- 使用 `rpx` 单位确保不同设备适配
- 遵循微信小程序设计规范
- 保持与现有页面风格一致

## 注意事项

1. **图片上传**: 确保服务器支持文件上传，并设置合适的文件大小限制
2. **跨域问题**: 后端需要设置正确的CORS头
3. **数据验证**: 前后端都需要进行数据验证
4. **错误处理**: 实现完善的错误处理机制
5. **性能优化**: 合理使用分页加载，避免一次性加载大量数据

## 更新日志

### v1.0.0 (2024-01-XX)
- 初始版本发布
- 实现基础功能模块
- 完成UI设计和交互逻辑
- 添加API接口支持

## 联系方式

如有问题或建议，请联系开发团队。