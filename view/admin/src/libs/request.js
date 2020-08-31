import axios from 'axios'
import { Message } from 'iview'
import { getCookies, removeCookies } from '@/libs/util'
import Setting from '@/setting'
import router from '@/router'
const service = axios.create({
    baseURL: Setting.apiBaseURL,
    timeout: 10000 // 请求超时时间
})

axios.defaults.withCredentials = true// 携带cookie

// 请求拦截器
service.interceptors.request.use(
    config => {
        const token = getCookies('token')
        if (token) {
            config.headers['Authori-zation'] = 'Bearer ' + token
        }
        return config
    },
    error => {
        // do something with request error
        return Promise.reject(error)
    }
)

// response interceptor
service.interceptors.response.use(

    response => {
        let status = response.data ? response.data.status : 0
        const code = status
        switch (code) {
        case 200:
            return response.data
        case 400:case 400011:case 400012:
            return Promise.reject(response.data || { msg: '未知错误' })
        case 410000:
        case 410001:
        case 410002:
          console.log(code);
            localStorage.clear()
            removeCookies('token')
            removeCookies('expires_time')
            removeCookies('uuid')
            router.replace({path:'/admin/login'})
            break
        default:
            break
        }
    },
    error => {
        Message.error(error.msg)
        return Promise.reject(error)
    }
)

export default service
