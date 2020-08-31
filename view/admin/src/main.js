// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import App from './App'
import router from './router'
import store from './store'
import iView from 'iview'
// import ViewUI from 'view-design'

import Router from 'vue-router'

import 'view-design/dist/styles/iview.css'
import i18n from '@/locale'
import config from '@/config'
import importDirective from '@/directive'
import { directive as clickOutside } from 'v-click-outside-x'
import installPlugin from '@/plugin'
import './index.less'
import '@/assets/icons/iconfont.css'
import TreeTable from 'tree-table-vue'
import VOrgTree from 'v-org-tree'
import 'xe-utils'
import 'v-org-tree/dist/v-org-tree.css'
import './styles/index.less'
import 'swiper/css/swiper.css'
import 'viewerjs/dist/viewer.css'
import 'codemirror/lib/codemirror.css'
import 'vxe-table/lib/index.css'
// swiper
import VueAwesomeSwiper from 'vue-awesome-swiper'
// 懒加载
import VueLazyload from 'vue-lazyload'
import VXETable from 'vxe-table'
import Viewer from 'v-viewer'
import VueDND from 'awe-dnd'
import formCreate from '@form-create/iview'
import modalForm from '@/utils/modalForm'
import videoCloud from '@/utils/videoCloud'
import { modalSure } from '@/utils/public'
import { authLapse } from '@/utils/authLapse'
import auth from '@/utils/auth'
import VueCodeMirror from 'vue-codemirror'
const routerPush = Router.prototype.push
Router.prototype.push = function push (location) {
    return routerPush.call(this, location).catch(error => error)
}
// 实际打包时应该不引入mock
/* eslint-disable */
if (process.env.NODE_ENV !== 'production') require('@/mock');
window.Promise = Promise;
Vue.prototype.$modalForm = modalForm;
Vue.prototype.$modalSure = modalSure;
Vue.prototype.$videoCloud = videoCloud;
Vue.prototype.$authLapse = authLapse;
Vue.use(iView, {
  i18n: (key, value) => i18n.t(key, value)
});
// Vue.use(ViewUI);
Vue.use(auth);
Vue.use(formCreate);
Vue.use(VueCodeMirror);
Vue.use(VueDND);
Vue.use(TreeTable);
Vue.use(VOrgTree);
Vue.use(VueAwesomeSwiper);
Vue.use(VXETable);
Vue.use(VueLazyload, {
  preLoad: 1.3,
  error: require('./assets/images/no.png'),
  loading: require('./assets/images/moren.jpg'),
  attempt: 1,
  listenEvents: ['scroll', 'wheel', 'mousewheel', 'resize', 'animationend', 'transitionend', 'touchmove']
});
Vue.use(Viewer, {
  defaultOptions: {
    zIndex: 9999
  }
});
/**
 * @description 注册admin内置插件
 */
installPlugin(Vue);
/**
 * @description 生产环境关掉提示
 */
Vue.config.productionTip = false;
/**
 * @description 全局注册应用配置
 */
Vue.prototype.$config = config;
/**
 * 注册指令
 */
importDirective(Vue);
Vue.directive('clickOutside', clickOutside);

/* eslint-disable no-new */
new Vue({
  el: '#app',
  router,
  i18n,
  store,
  render: h => h(App)
});
