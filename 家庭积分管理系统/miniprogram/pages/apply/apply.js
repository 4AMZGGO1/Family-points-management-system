// pages/apply/apply.js
const app = getApp()

Page({
  data: {
    userPoints: 0,
    tasks: [],
    categories: [],
    selectedCategory: '',
    filteredTasks: [],
    selectedTask: null,
    selectedTaskId: null,
    description: '',
    proofImages: [],
    submitting: false,
    showSuccess: false
  },

  onLoad(options) {
    // 如果有分类参数，设置默认分类
    if (options.category) {
      this.setData({
        selectedCategory: options.category
      })
    }
    this.loadData()
  },

  onShow() {
    this.loadUserPoints()
  },

  // 加载数据
  async loadData() {
    try {
      app.showLoading('加载中...')
      
      const [tasks, categories] = await Promise.all([
        this.getTasks(),
        this.getCategories()
      ])

      this.setData({
        tasks,
        categories
      })

      this.filterTasks()

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

  // 获取任务列表
  async getTasks() {
    try {
      const result = await app.request({
        url: '/tasks/list',
        method: 'GET'
      })
      return result.data || []
    } catch (error) {
      console.error('获取任务列表失败:', error)
      return []
    }
  },

  // 获取分类列表
  async getCategories() {
    try {
      const result = await app.request({
        url: '/tasks/categories',
        method: 'GET'
      })
      return result.data || []
    } catch (error) {
      console.error('获取分类列表失败:', error)
      return []
    }
  },

  // 选择分类
  selectCategory(e) {
    const category = e.currentTarget.dataset.category
    this.setData({
      selectedCategory: category,
      selectedTask: null,
      selectedTaskId: null,
      description: ''
    })
    this.filterTasks()
  },

  // 筛选任务
  filterTasks() {
    const { tasks, selectedCategory } = this.data
    let filteredTasks = tasks

    if (selectedCategory) {
      filteredTasks = tasks.filter(task => task.category === selectedCategory)
    }

    this.setData({
      filteredTasks
    })
  },

  // 选择任务
  selectTask(e) {
    const task = e.currentTarget.dataset.task
    this.setData({
      selectedTask: task,
      selectedTaskId: task.id,
      description: ''
    })
  },

  // 输入描述
  onDescriptionInput(e) {
    this.setData({
      description: e.detail.value
    })
  },

  // 选择图片
  chooseImage() {
    const { proofImages } = this.data
    const remaining = 3 - proofImages.length

    if (remaining <= 0) {
      app.showError('最多只能上传3张图片')
      return
    }

    wx.chooseImage({
      count: remaining,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        const tempFiles = res.tempFilePaths
        const newImages = [...proofImages, ...tempFiles]
        
        this.setData({
          proofImages: newImages
        })
      },
      fail: (error) => {
        console.error('选择图片失败:', error)
        app.showError('选择图片失败')
      }
    })
  },

  // 删除图片
  deleteImage(e) {
    const index = e.currentTarget.dataset.index
    const { proofImages } = this.data
    proofImages.splice(index, 1)
    
    this.setData({
      proofImages
    })
  },

  // 提交申请
  async submitApplication() {
    const { selectedTask, description, proofImages, submitting } = this.data

    if (submitting) return

    if (!selectedTask) {
      app.showError('请选择要申请的任务')
      return
    }

    if (!description.trim()) {
      app.showError('请填写完成描述')
      return
    }

    if (description.length < 10) {
      app.showError('完成描述至少需要10个字符')
      return
    }

    try {
      this.setData({ submitting: true })
      app.showLoading('提交中...')

      // 上传图片
      let uploadedImages = []
      if (proofImages.length > 0) {
        uploadedImages = await this.uploadImages(proofImages)
      }

      // 提交申请
      const result = await app.request({
        url: '/submissions/create',
        method: 'POST',
        data: {
          user_id: app.globalData.userId,
          task_id: selectedTask.id,
          description: description.trim(),
          proof_images: uploadedImages
        }
      })

      if (result.success) {
        this.setData({
          showSuccess: true,
          selectedTask: null,
          selectedTaskId: null,
          description: '',
          proofImages: []
        })
        app.showSuccess('申请提交成功！')
      } else {
        app.showError(result.message || '提交失败，请重试')
      }

    } catch (error) {
      console.error('提交申请失败:', error)
      app.showError('提交失败，请重试')
    } finally {
      this.setData({ submitting: false })
      app.hideLoading()
    }
  },

  // 上传图片
  async uploadImages(images) {
    const uploadPromises = images.map(imagePath => {
      return new Promise((resolve, reject) => {
        wx.uploadFile({
          url: app.globalData.baseUrl + '/upload/image',
          filePath: imagePath,
          name: 'image',
          header: {
            'Authorization': wx.getStorageSync('token') || ''
          },
          success: (res) => {
            try {
              const data = JSON.parse(res.data)
              if (data.success) {
                resolve(data.data.url)
              } else {
                reject(new Error(data.message))
              }
            } catch (error) {
              reject(error)
            }
          },
          fail: reject
        })
      })
    })

    try {
      return await Promise.all(uploadPromises)
    } catch (error) {
      console.error('上传图片失败:', error)
      throw new Error('图片上传失败')
    }
  },

  // 关闭成功提示
  closeSuccess() {
    this.setData({
      showSuccess: false
    })
    // 返回首页
    wx.switchTab({
      url: '/pages/home/home'
    })
  },

  // 计算是否可以提交
  get canSubmit() {
    const { selectedTask, description, submitting } = this.data
    return selectedTask && description.trim().length >= 10 && !submitting
  }
})