// 请求接口地址 如果没有配置自动获取当前网址路径
const Url = ''
const VUE_APP_API_URL = Url || process.env.VUE_APP_API_URL || `${location.origin}/adminapi`
const VUE_APP_WS_URL = process.env.VUE_APP_WS_URL || `ws:${location.hostname}:20002`
const Setting = {
    // 接口请求地址
    apiBaseURL: VUE_APP_API_URL,
    // socket连接
    wsSocketUrl: VUE_APP_WS_URL,
    // 路由模式，可选值为 history 或 hash
    routerMode: 'history',
    // 页面切换时，是否显示模拟的进度条
    showProgressBar: true
}

export default Setting
