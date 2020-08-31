import request from '@/libs/request'
/**
 * @description 保存DIY数据
 * @param {Object} param data {Object} 传值参数
 */
export function diySave (id, data) {
    return request({
        url: 'diy/save/' + id,
        method: 'post',
        data: data
    });
}


/**
 * @description 获取DIY数据
 * @param {Object} param data {Object} 传值参数
 */
export function diyGetInfo (id, data) {
    return request({
        url: 'diy/get_info/' + id,
        method: 'get',
        params: data
    });
}

/**
 * @description 获取链接列表
 */
export function getUrl () {
    return request({
        url: 'diy/get_url',
        method: 'get'
    });
}
