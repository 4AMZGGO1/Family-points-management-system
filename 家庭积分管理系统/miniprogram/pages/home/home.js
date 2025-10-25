// pages/home/home.js
const app = getApp()

Page({
  data: {
    username: 'child1',
    userPoints: 0,
    pendingSubmissions: [],
    recentTransactions: [],
    categories: []
  },

  onLoad() {
    this.loadUserData()
  },

  onShow() {
    // 每次显示页面时刷新数据
    this.loadUserData()
  },

  // 加载用户数据
  async loadUserData() {
    try {
      app.showLoading('加载中...')
      
      // 并行加载所有数据
      const [userInfo, pendingSubmissions, recentTransactions, categories] = await Promise.all([
        this.getUserInfo(),
        this.getPendingSubmissions(),
        this.getRecentTransactions(),
        this.getCategories()
      ])

      this.setData({
        username: userInfo.username || 'child1',
        userPoints: userInfo.points || 0,
        pendingSubmissions: pendingSubmissions.map(item => ({
          ...item,
          statusText: this.getStatusText(item.status)
        })),
        recentTransactions: recentTransactions.map(item => ({
          ...item,
          timeAgo: this.formatTimeAgo(item.created_at)
        })),
        categories
      })

    } catch (error) {
      console.error('加载数据失败:', error)
      app.showError('加载数据失败，请重试')
    } finally {
      app.hideLoading()
    }
  },

  // 获取用户信息
  async getUserInfo() {
    try {
      const result = await app.request({
        url: '/user/info',
        method: 'GET',
        data: { user_id: app.globalData.userId }
      })
      return result.data
    } catch (error) {
      console.error('获取用户信息失败:', error)
      return { username: 'child1', points: 0 }
    }
  },

  // 获取待审核申请
  async getPendingSubmissions() {
    try {
      const result = await app.request({
        url: '/submissions/pending',
        method: 'GET',
        data: { user_id: app.globalData.userId }
      })
      return result.data || []
    } catch (error) {
      console.error('获取待审核申请失败:', error)
      return []
    }
  },

  // 获取最近积分变动
  async getRecentTransactions() {
    try {
      const result = await app.request({
        url: '/transactions/recent',
        method: 'GET',
        data: { 
          user_id: app.globalData.userId,
          limit: 5
        }
      })
      return result.data || []
    } catch (error) {
      console.error('获取积分变动失败:', error)
      return []
    }
  },

  // 获取任务分类
  async getCategories() {
    try {
      const result = await app.request({
        url: '/tasks/categories',
        method: 'GET'
      })
      return result.data || []
    } catch (error) {
      console.error('获取任务分类失败:', error)
      return []
    }
  },

  // 跳转到申请页面
  goToApply(e) {
    const category = e.currentTarget.dataset.category
    wx.navigateTo({
      url: `/pages/apply/apply${category ? '?category=' + category : ''}`
    })
  },

  // 获取状态文本
  getStatusText(status) {
    const statusMap = {
      'pending': '待审核',
      'approved': '已通过',
      'rejected': '已拒绝'
    }
    return statusMap[status] || '未知'
  },

  // 格式化时间
  formatTimeAgo(timestamp) {
    const now = new Date()
    const time = new Date(timestamp)
    const diff = now - time
    
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(diff / 3600000)
    const days = Math.floor(diff / 86400000)
    
    if (minutes < 1) return '刚刚'
    if (minutes < 60) return `${minutes}分钟前`
    if (hours < 24) return `${hours}小时前`
    if (days < 7) return `${days}天前`
    
    return time.toLocaleDateString()
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.loadUserData().then(() => {
      wx.stopPullDownRefresh()
    })
  }
})