# 图标资源说明

由于小程序需要图标文件，但无法直接创建图片，请按以下方式处理：

## 方案一：使用Emoji图标（推荐）
当前配置已使用Emoji图标，无需额外图片文件。

## 方案二：添加自定义图标
如果需要使用自定义图标，请将以下尺寸的PNG图片放入此目录：

- `home.png` - 首页图标 (81x81px)
- `home-active.png` - 首页选中图标 (81x81px)
- `apply.png` - 申请图标 (81x81px)
- `apply-active.png` - 申请选中图标 (81x81px)
- `shop.png` - 商城图标 (81x81px)
- `shop-active.png` - 商城选中图标 (81x81px)
- `history.png` - 记录图标 (81x81px)
- `history-active.png` - 记录选中图标 (81x81px)

## 图标要求
- 格式：PNG
- 尺寸：81x81像素
- 背景：透明
- 颜色：普通状态为灰色，选中状态为主题色

添加图标后，需要修改 `app.json` 中的 `tabBar` 配置，将 `text` 字段替换为 `iconPath` 和 `selectedIconPath`。