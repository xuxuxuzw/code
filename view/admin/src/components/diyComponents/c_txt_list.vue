<template>
    <div class="c_product" v-if="datas">
        <div class="title">{{datas[name].title}}</div>
        <div class="list-box">

                <div class="item" v-for="(item,index) in datas[this.name].list" :key="index">
                    <div class="content">
                        <div class="con-item" v-for="(list,key) in item.chiild" :key="key">
                            <span>{{list.title}}</span>
                            <Input v-model="list.val" :placeholder="list.pla" :maxlength="list.max"/>
                        </div>
                        <div class="con-item" v-if="item.link">
                            <span>{{item.link.title}}</span>
                            <Select v-model="item.link.activeVal" style="">
                                <Option v-for="(item,j) in item.link.optiops" :value="item.value" :key="j">{{ item.label}}
                                </Option>
                            </Select>
                        </div>
                    </div>
                    <div class="delete" @click.stop="bindDelete(index)">
                        <Icon type="ios-close-circle" size="26"/>
                    </div>
                </div>
        </div>
        <div v-if="datas[this.name]">
            <div class="add-btn" @click="addHotTxt" v-if="datas[this.name].list.length < datas[name].max">
                <Button type="primary" ghost style="width: 100%; height: 40px; border-color:#1890FF; color: #1890FF;">添加模块</Button>
            </div>
        </div>

    </div>
</template>

<script>

    export default {
        name: 'c_txt_list',
        props: {
            name: {
                type: String
            },
            configData:{
                type:null
            }
        },
        components: {
        },
        data () {
            return {
                defaults: {},
                itemObj: {},
                datas:{}
            }
        },
        mounted () {
            this.$nextTick(()=>{
                this.datas = this.configData
            })
        },
        watch: {
            configData: {
                handler (nVal, oVal) {
                    this.datas = nVal
                },
                deep: true
            }
        },
        methods: {
            addHotTxt () {
                if (this.datas[this.name].list.length == 0) {
                    this.datas[this.name].list.push(this.itemObj)
                } else {
                    let obj = JSON.parse(JSON.stringify(this.datas[this.name].list[this.datas[this.name].list.length - 1]));
                    this.datas[this.name].list.push(obj)
                }
            },
            // 删除数组
            bindDelete (index) {
                if (this.datas[this.name].list.length == 1) {
                    this.itemObj = this.datas[this.name].list[0]
                }
                this.datas[this.name].list.splice(index, 1)
            }
        }
    }
</script>

<style scoped lang="stylus">
    .c_product
        margin-bottom 20px
        .list-box
            .item
                position relative
                display flex
                margin-top 23px
                padding 18px 20px
                border: 1px solid rgba(238, 238, 238, 1);

                .delete
                    position absolute
                    right 0
                    top 0
                    right: -13px;
                    top: -14px;
                    color #999999
                    cursor pointer

            .move-icon
                display flex
                align-items center
                justify-content center
                width 50px
                cursor move

            .content
                flex 1

                .con-item
                    display flex
                    align-items center
                    margin-bottom 15px

                    &:last-child
                        margin-bottom 0
                    span
                        width 45px
                        font-size 13px

        .add-btn
            margin-top 18px
    .title
        font-size 12px
        color #999
    .iconfont-diy
        color #DDDDDD
        font-size 38px
</style>
