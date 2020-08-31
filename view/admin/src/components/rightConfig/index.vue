<template>
    <div class="right-box" v-if="rCom.length">
        <div class="title-bar">模块配置</div>
        <div class="mobile-config">

            <div v-for="(item,key) in rCom" :key="key">
                <component :is="item.components.name" :name="item.configNme" :configData="configData"></component>
            </div>
            <div style="text-align: center;" v-if="rCom.length">
                <Button type="primary" style="width:80%;margin:0 auto;" @click="saveConfig">保存</Button>
            </div>
        </div>
    </div>

</template>

<script>
    import toolCom from '@/components/diyComponents/index.js'
    import { diySave } from '@/api/diy'
    import { mapMutations } from 'vuex'
    export default {
        name: "rightConfig",
        components:{
          ...toolCom
        },
        props:{
            name: {
                type: null,
                default:''
            },
        },
        watch:{
            name:{
                handler (nVal, oVal) {
                    this.rCom = []
                    this.configData = this.$store.state.diy.defaultConfig[nVal]
                    this.rCom = this.$store.state.diy.component[nVal].list
                },
                deep: true
            }
        },
        data(){
            return {
                rCom:[
                ],
                configData:{}
            }
        },
        mounted() {
            this.$nextTick(res=>{
                console.log(this.name,'name')
            })
        },
        methods:{
            // 保存数据
            saveConfig(){
                let data = this.$store.state.diy.defaultConfig
                console.log(this.$store.state.diy)
                diySave(1,{
                    value:data
                }).then(res=>{
                    this.$Message.success('保存成功')
                })
            },
        }
    }
</script>

<style scoped lang="stylus">
    .right-box
        width 400px
        margin-left 50px
        border:1px solid #ddd;
        border-radius 4px
        height 700px
        overflow-y scroll
        &::-webkit-scrollbar {
            /*滚动条整体样式*/
            width : 4px;  /*高宽分别对应横竖滚动条的尺寸*/
            height: 1px;
        }
        &::-webkit-scrollbar-thumb {
            /*滚动条里面小方块*/
            border-radius: 4px;
            box-shadow   : inset 0 0 5px rgba(0, 0, 0, 0.2);
            background   : #535353;
        }
        &::-webkit-scrollbar-track {
            /*滚动条里面轨道*/
            box-shadow   : inset 0 0 5px #fff;
            border-radius: 4px;
            background   : #fff;
        }
    .title-bar
        width 100%
        height 38px
        line-height 38px
        padding-left 24px
        color #333
        border-radius 4px
        border-bottom 1px solid #eee
</style>
