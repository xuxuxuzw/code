/**
 * diy配置
 * */

import toolCom from '@/components/diyComponents/index.js'

export default {
    namespaced: true,
    state: {
        activeName: {},
        defaultConfig: {
            a_headerSerch: {
                imgUrl:{
                    title: '最多可添加1张图片，图片建议宽度118 * 42px',
                    url: '',
                },
                hotList: {
                    title:'热词最多20个字',
                    max:99,
                    list:[
                        {
                            val: '',
                            maxlength:20
                        }
                    ]
                },
            },
            b_swiperBg: {
                imgList:{
                    title: '最多可添加10张图片，建议宽度750px',
                    max: 10,
                    list:[
                        {
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/a32307fd1043c350932a462839288d38.jpg',
                            info: [
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/906d46eb6f734eaf1fd820601893af0d.jpg',
                            info: [
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        }
                    ]
                },
            },
            c_menus: {
                imgList:{
                    title: '最多可添加8张图片，建议宽度82 * 82px',
                    max: 8,
                    list:[
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/723bb4d18893a5aa6871c94d19f3bc4d.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '商品分类',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/goods_cate/goods_cate',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/e908c8f088db07a0f4f6fddc2a7b96f9.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '领优惠券',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/users/user_get_coupon/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/1a9a1189bf4a1e9970517d31bcb00bbc.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '行业资讯',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/news_list/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/dded4f4779e705d54cf640826d1b5558.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '我的收藏',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/users/user_goods_collection/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/f95dd1f3f71fef869e80533df9ccb1a0.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '拼团活动',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_combination/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/8bf36e0cd9f9490c1f06abcd7efe8c2d.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '秒杀活动',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_seckill/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/5cbdc6eda8c4a2c92c88abffee50d1ff.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '砍价活动',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_bargain/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        },
                        {
                            img: 'http://admin.crmeb.net/uploads/attach/2020/05/20200515/fdb67663ea188163b0ad863a05f77fbf.png',
                            info: [
                                {
                                    title: '标题',
                                    value: '地址管理',
                                    maxlength: 5,
                                    tips: '请填写标题',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_bargain/index',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ]
                        }
                    ]
                },
            },
            d_news: {
                imgUrl:{
                    title: '最多可添加10个模板，图片建议宽度124 * 28px',
                    url: 'http://v4.crmeb.net/uploads/attach/2020/08/20200804/623086850799a5c38791dbf4990c52a8.png',
                },
                newList:{
                    max: 10,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: 'CRMEB_PRO 1.1正式公测啦',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '链接',
                                val: '链接',
                                max: 99,
                                pla: '选填'
                            }
                        ]
                    }]
                },
            },
            e_activity: {
                imgList: {
                    isDelete: true,
                    title: '最多可添加3组模块，第一张260*260px,后两张416*124px',
                    max: 3,
                    list: [
                        {
                            img: 'http://datong.crmeb.net/public/uploads/attach/2019/03/28/5c9ccf7e9f4d0.jpg',
                            info: [
                                {
                                    title: '标题',
                                    value: '一起来拼团',
                                    maxlength: 20,
                                    tips: '标题',
                                },
                                {
                                    title: '描述',
                                    value: '优惠多多',
                                    maxlength: 20,
                                    tips: '描述',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_combination/index',
                                    maxlength: 999,
                                    tips: '链接',
                                }
                            ]
                        },
                        {
                            img: 'http://datong.crmeb.net/public/uploads/attach/2019/03/28/5c9ccf7e97660.jpg',
                            info: [
                                {
                                    title: '标题',
                                    value: '秒杀专区',
                                    maxlength: 20,
                                    tips: '标题',
                                },
                                {
                                    title: '描述',
                                    value: '新能源汽车优惠多多',
                                    maxlength: 20,
                                    tips: '描述',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_seckill/index',
                                    maxlength: 999,
                                    tips: '链接',
                                }
                            ]
                        },
                        {
                            img: 'http://datong.crmeb.net/public/uploads/attach/2019/03/28/5c9ccfc86a6c1.jpg',
                            info: [
                                {
                                    title: '标题',
                                    value: '砍价活动',
                                    maxlength: 20,
                                    tips: '标题',
                                },
                                {
                                    title: '描述',
                                    value: '呼朋唤友来砍价~~',
                                    maxlength: 20,
                                    tips: '描述',
                                },
                                {
                                    title: '链接',
                                    value: '/pages/activity/goods_bargain/index',
                                    maxlength: 999,
                                    tips: '链接',
                                }
                            ]
                        }
                    ],
                },


                max: 3
            },
            f_scroll_box: {
                titleInfo:{
                    title: '修改标题和描述',
                    max:1,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: '快速选择',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '简介',
                                val: '诚意推荐品质商品',
                                max: 20,
                                pla: '选填'
                            }
                        ]
                    }]
                },
            },
            g_recommend: {
                titleInfo:{
                    title: '修改标题和描述',
                    max:1,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: '精品推荐',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '简介',
                                val: '诚意推荐品质商品',
                                max: 20,
                                pla: '选填'
                            }
                        ]
                    }]
                },
                imgList: {
                    title: '最多可添加10个模板，图片建议宽度750 * 280px',
                    max: 10,
                    list: [
                        {
                            info:[
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ],
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/505554c6d46688d5b4541861e5056335.jpg',
                        },
                        {
                            info:[
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ],
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/aeee0e4c7432bb37b34857fa3a7b3916.jpg',
                        },
                    ],
                },
            },
            h_popular:{
                titleInfo:{
                    title: '修改标题和描述',
                    max:1,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: '热门榜单',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '简介',
                                val: '根据销量、搜索、好评等综合得出',
                                max: 20,
                                pla: '选填'
                            }
                        ]
                    }]
                },
            },
            i_m_banner:{
                imgList: {
                    title: '最多可添加10个模板，图片建议宽度750 * 190px',
                    max: 10,
                    list: [
                        {
                            info:[
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ],
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/a32307fd1043c350932a462839288d38.jpg',
                        },
                        {
                            info:[
                                {
                                    title: '链接',
                                    value: '',
                                    maxlength: 999,
                                    tips: '请填写链接',
                                }
                            ],
                            img: 'http://kaifa.crmeb.net/uploads/attach/2020/03/20200319/906d46eb6f734eaf1fd820601893af0d.jpg',
                        },
                    ],
                },
            },
            i_new_goods:{
                titleInfo:{
                    title: '修改标题和描述',
                    max:1,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: '首发新品',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '简介',
                                val: '多个优质商品最新上架',
                                max: 20,
                                pla: '选填'
                            }
                        ]
                    }]
                },
            },
            j_promotion:{
                titleInfo:{
                    title: '修改标题和描述',
                    max:1,
                    list:[{
                        chiild: [
                            {
                                title: '标题',
                                val: '促销单品',
                                max: 20,
                                pla: '选填，不超过四个字'
                            },
                            {
                                title: '简介',
                                val: '库存商品优惠促销活动',
                                max: 20,
                                pla: '选填'
                            }
                        ]
                    }]
                },
            }
        },
        component: {
            a_headerSerch: {
                list: [
                    {
                        components: toolCom.c_upload_img,
                        configNme: 'imgUrl'
                    },
                    {
                        components: toolCom.c_hot_word,
                        configNme: 'hotList'
                    },
                ]
            },
            b_swiperBg: {
                list: [
                    {
                        components: toolCom.c_upload_list,
                        configNme: 'imgList'
                    },
                ]
            },
            c_menus: {
                list: [
                    {
                        components: toolCom.c_upload_list,
                        configNme: 'imgList'
                    },
                ]
            },
            d_news: {
                list: [
                    {
                        components: toolCom.c_upload_img,
                        configNme: 'imgUrl'
                    },
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'newList'
                    },
                ]
            },
            e_activity: {
                list: [
                    {
                        components: toolCom.c_upload_list,
                        configNme: 'imgList'
                    },
                ]
            },
            f_scroll_box: {
                list: [
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'titleInfo'
                    },
                ]
            },
            g_recommend: {
                list: [
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'titleInfo'
                    },
                    {
                        components: toolCom.c_upload_list,
                        configNme: 'imgList'
                    },
                ]
            },
            h_popular:{
                list: [
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'titleInfo'
                    },
                ]
            },
            i_new_goods:{
                list: [
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'titleInfo'
                    },
                ]
            },
            j_promotion:{
                list: [
                    {
                        components: toolCom.c_txt_list,
                        configNme: 'titleInfo'
                    },
                ]
            },
            i_m_banner:{
                list: [
                    {
                        components: toolCom.c_upload_list,
                        configNme: 'imgList'
                    },
                ]
            }
        }


    },
    mutations: {
        /**
         * @description 设置选中name
         * @param {Object} state vuex state
         * @param {String} name
         */
        setConfig(state, name) {
            state.activeName = name
        },
        /**
         * @description 更新默认数据
         * @param {Object} state vuex state
         * @param {Object} data
         */
        updataConfig(state,data){
            state.defaultConfig = data
            let value = Object.assign({}, state.defaultConfig);
            state.defaultConfig = value
        }
    },
    actions: {}
}
