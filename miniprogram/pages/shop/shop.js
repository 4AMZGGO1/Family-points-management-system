// pages/shop/shop.js
const app = getApp()

Page({
  data: {
    userPoints: 0,
    shopItems: [],
    showPurchaseModal: false,
    selectedItem: null,
    purchasing: false,
    showSuccess: false
  },

  onLoad() {
    this.loadData()
  },

  onShow() {
    this.loadUserPoints()
  },

  // 加载数据
  async loadData() {
    try {
      app.showLoading('加载中...')
      
      const [shopItems, userPoints] = await Promise.all([
        this.getShopItems(),
        this.getUserPoints()
      ])

      // 为每个商品添加数量属性
      const itemsWithQuantity = shopItems.map(item => ({
        ...item,
        quantity: 1
      }))

      this.setData({
        shopItems: itemsWithQuantity,
        userPoints
      })

    } catch (error) {
      console.error('加载数据失败:', error)
      app.showError('加载数据失败，请重试')
    } finally {
      app.hideLoading()
    }
  },

  // 加载用户积分
  async loadUserPoints() {
    try {
      const result = await app.request({
        url: '/user/info',
        method: 'GET',
        data: { user_id: app.globalData.userId }
      })
      this.setData({
        userPoints: result.data.points || 0
      })
    } catch (error) {
      console.error('获取用户积分失败:', error)
    }
  },

  // 获取商城物品
  async getShopItems() {
    try {
      const result = await app.request({
        url: '/shop/items',
        method: 'GET'
      })
      return result.data || []
    } catch (error) {
      console.error('获取商城物品失败:', error)
      return []
    }
  },

  // 获取用户积分
  async getUserPoints() {
    try {
      const result = await app.request({
        url: '/user/info',
        method: 'GET',
        data: { user_id: app.globalData.userId }
      })
      return result.data.points || 0
    } catch (error) {
      console.error('获取用户积分失败:', error)
      return 0
    }
  },

  // 减少数量
  decreaseQuantity(e) {
    const item = e.currentTarget.dataset.item
    const { shopItems } = this.data
    
    const updatedItems = shopItems.map(shopItem => {
      if (shopItem.id === item.id) {
        return {
          ...shopItem,
          quantity: Math.max(1, shopItem.quantity - 1)
        }
      }
      return shopItem
    })
    
    this.setData({
      shopItems: updatedItems
    })
  },

  // 增加数量
  increaseQuantity(e) {
    const item = e.currentTarget.dataset.item
    const { shopItems } = this.data
    
    const updatedItems = shopItems.map(shopItem => {
      if (shopItem.id === item.id) {
        return {
          ...shopItem,
          quantity: Math.min(shopItem.stock, shopItem.quantity + 1)
        }
      }
      return shopItem
    })
    
    this.setData({
      shopItems: updatedItems
    })
  },

  // 购买商品
  purchaseItem(e) {
    const item = e.currentTarget.dataset.item
    const { userPoints } = this.data
    
    if (item.price > userPoints) {
      app.showError('积分不足')
      return
    }
    
    if (item.stock <= 0) {
      app.showError('商品缺货')
      return
    }
    
    this.setData({
      selectedItem: item,
      showPurchaseModal: true
    })
  },

  // 关闭购买弹窗
  closePurchaseModal() {
    this.setData({
      showPurchaseModal: false,
      selectedItem: null
    })
  },

  // 确认购买
  async confirmPurchase() {
    const { selectedItem, purchasing } = this.data
    
    if (purchasing) return
    
    try {
      this.setData({ purchasing: true })
      app.showLoading('购买中...')
      
      const result = await app.request({
        url: '/shop/purchase',
        method: 'POST',
        data: {
          user_id: app.globalData.userId,
          item_id: selectedItem.id,
          quantity: selectedItem.quantity
        }
      })
      
      if (result.success) {
        this.setData({
          showPurchaseModal: false,
          showSuccess: true,
          selectedItem: null
        })
        
        // 刷新数据
        this.loadData()
        app.showSuccess('购买成功！')
      } else {
        app.showError(result.message || '购买失败，请重试')
      }
      
    } catch (error) {
      console.error('购买失败:', error)
      app.showError('购买失败，请重试')
    } finally {
      this.setData({ purchasing: false })
      app.hideLoading()
    }
  },

  // 关闭成功提示
  closeSuccess() {
    this.setData({
      showSuccess: false
    })
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.loadData().then(() => {
      wx.stopPullDownRefresh()
    })
  }
})