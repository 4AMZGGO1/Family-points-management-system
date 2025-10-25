// pages/history/history.js
const app = getApp()

Page({
  data: {
    userPoints: 0,
    allTransactions: [],
    filteredTransactions: [],
    activeTab: 'all',
    loading: false,
    hasMore: true,
    page: 1,
    pageSize: 20
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
      this.setData({ loading: true })
      app.showLoading('加载中...')
      
      const [transactions, userPoints] = await Promise.all([
        this.getTransactions(),
        this.getUserPoints()
      ])

      this.setData({
        allTransactions: transactions,
        userPoints,
        page: 1,
        hasMore: transactions.length >= this.data.pageSize
      })

      this.filterTransactions()

    } catch (error) {
      console.error('加载数据失败:', error)
      app.showError('加载数据失败，请重试')
    } finally {
      this.setData({ loading: false })
      app.hideLoading()
    }
  },

  // 加载更多数据
  async loadMore() {
    if (this.data.loading || !this.data.hasMore) return

    try {
      this.setData({ loading: true })
      
      const nextPage = this.data.page + 1
      const transactions = await this.getTransactions(nextPage)
      
      if (transactions.length === 0) {
        this.setData({ hasMore: false })
        return
      }

      const allTransactions = [...this.data.allTransactions, ...transactions]
      
      this.setData({
        allTransactions,
        page: nextPage,
        hasMore: transactions.length >= this.data.pageSize
      })

      this.filterTransactions()

    } catch (error) {
      console.error('加载更多失败:', error)
      app.showError('加载更多失败')
    } finally {
      this.setData({ loading: false })
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

  // 获取交易记录
  async getTransactions(page = 1) {
    try {
      const result = await app.request({
        url: '/transactions/recent',
        method: 'GET',
        data: { 
          user_id: app.globalData.userId,
          limit: this.data.pageSize,
          page: page
        }
      })
      
      const transactions = result.data || []
      
      // 格式化时间
      return transactions.map(transaction => ({
        ...transaction,
        timeAgo: this.formatTimeAgo(transaction.created_at)
      }))
    } catch (error) {
      console.error('获取交易记录失败:', error)
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

  // 切换标签
  switchTab(e) {
    const tab = e.currentTarget.dataset.tab
    this.setData({
      activeTab: tab
    })
    this.filterTransactions()
  },

  // 筛选交易记录
  filterTransactions() {
    const { allTransactions, activeTab } = this.data
    let filteredTransactions = allTransactions

    if (activeTab === 'earn') {
      filteredTransactions = allTransactions.filter(item => 
        item.type === 'earn' || item.type === 'manual_add'
      )
    } else if (activeTab === 'spend') {
      filteredTransactions = allTransactions.filter(item => 
        item.type === 'spend' || item.type === 'purchase'
      )
    }

    this.setData({
      filteredTransactions
    })
  },

  // 格式化时间
  formatTimeAgo(timestamp) {
    const now = new Date()
    const time = new Date(timestamp)
    const diff = now - time
    
    const minutes = Math.floor(diff / 60000)
    const hours = Math.floor(diff / 3600000)
    const days = Math.floor(diff / 86400000)
    const weeks = Math.floor(days / 7)
    const months = Math.floor(days / 30)
    
    if (minutes < 1) return '刚刚'
    if (minutes < 60) return `${minutes}分钟前`
    if (hours < 24) return `${hours}小时前`
    if (days < 7) return `${days}天前`
    if (weeks < 4) return `${weeks}周前`
    if (months < 12) return `${months}个月前`
    
    return time.toLocaleDateString()
  },

  // 下拉刷新
  onPullDownRefresh() {
    this.loadData().then(() => {
      wx.stopPullDownRefresh()
    })
  },

  // 触底加载更多
  onReachBottom() {
    this.loadMore()
  }
})