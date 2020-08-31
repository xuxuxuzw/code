<template>
    <div>
    <div class="i-layout-page-header">
        <div class="i-layout-page-header">
            <span class="ivu-page-header-title">页面设计</span>
        </div>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt">
    <div class="flex-wrapper">
        <!-- :src="iframeUrl" -->
        <iframe class="iframe-box":src="iframeUrl" frameborder="0" ref="iframe"></iframe>
        <div>
            <div class="content">
                <rightConfig :name="configName"></rightConfig>
            </div>
        </div>
    </div>
    </Card>
    </div>
</template>

<script>
    import { diyGetInfo } from  '@/api/diy'
    import { mapMutations } from 'vuex'
    import rightConfig from '@/components/rightConfig/index'
    export default {
        name: "index",
        components:{
            rightConfig
        },
        data(){
            return {
                configName:'',
                iframeUrl:''

            }
        },
        created() {
            this.iframeUrl = `${location.origin}?type=iframe`
            diyGetInfo(1).then(res=>{
                this.upData(res.data.info.value)
            })
        },
        mounted() {
            //监听子页面给当前页面传值
            window.addEventListener("message", this.handleMessage,false)
        },
        methods:{
            //接收iframe值
            handleMessage (event) {
                if(event.data.name){
                    this.configName = event.data.name
                    this.add(event.data.name)
                }
            },
            ...mapMutations({
                add: 'diy/setConfig',
                upData:'diy/updataConfig'
            })
        }
    }
</script>

<style scoped lang="stylus">
    .flex-wrapper
        display flex
    .iframe-box
        width: 375px;
        height: 700px;
        border: 1px solid #ddd;
        border-radius: 4px;
    .right-box
        width 400px
        margin-left 50px
        border:1px solid #ddd;
        border-radius 4px
        .title-bar
            width 100%
            height 38px
            line-height 38px
            padding-left 24px
            color #333
            border-radius 4px
            border-bottom 1px solid #eee

</style>
