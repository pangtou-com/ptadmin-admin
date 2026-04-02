<script type="module">
    const { createApp, ref, reactive, onMounted } = Vue
    const { Plus, Edit, Delete, Check, Close } = ElementPlusIconsVue
    const App = {
        setup() {
            const def = {
                title: "",
                desc: ""
            }

            const formRule = {
                title: [{required: true, message: "请输入标题"}],
                overview: [{required: true, message: "请输入服务概述"}],
                subtitle: [{required: true, message: "请输入服务概述"}],
                type: [{required: true, message: "请选择类型"}],
            }
            const loading = ref(false)
            const safetyType = ref(safety_type)
            const formRef = ref()
            const formData = reactive({
                title: "",
                subtitle: "",
                type: "",
                overview: "",
                overview_desc: "",
                cover:"",
                cover_title: "",
                banner: "",
                process: [
                    {...def}
                ],
                advantage: [
                    {...def}
                ],
                weight: 99,
                status: 1
            })

            const loadData = () => {
                if (current_id === 0) {
                    return
                }
                loading.value = true
                $.ajax(`/system/cms/safety-detail/${current_id}`, {
                    type: "get",
                    dataType: 'json',
                    success: (res) => {
                        loading.value = false
                        if (res.code === 0) {
                            for (const key in res.data ) {
                                formData[key] = res.data[key]
                            }
                        } else {
                            ElementPlus.ElMessage.error(res.message)
                        }
                    },

                })
            }


            const handleSubmit = async () => {
                try {
                    await formRef.value.validate()
                }catch (e) {
                    ElementPlus.ElMessage.error("请完善表单数据")
                    return
                }
                let url = `/system/cms/safety`
                let method = `post`

                if (current_id !== 0) {
                    url = url + "/" + current_id
                    method = "put"
                }
                loading.value = true
                $.ajax(url, {
                    type: method,
                    data: formData,
                    dataType: "json",
                    success: (res) => {
                        loading.value = false
                        if (res.code !== 0) {
                            ElementPlus.ElMessage.error(res.message)
                            return
                        }
                        ElementPlus.ElMessage.success("操作成功")
                        setTimeout(() => {
                            if (parent && parent.window['__callback__']) {
                                parent.window['__callback__']()
                            }
                        }, 1000)
                    }
                })
            }

            const handleAvatarSuccess= (type) => {
                return (response) => {
                    if (response.code !== 0) {
                        ElementPlus.ElMessage.error(response.message)
                        return
                    }
                    ElementPlus.ElMessage.success("上传成功")
                    if (type === "cover") {
                        formData.cover = response.data.url
                    } else {
                        formData.banner = response.data.url
                    }

                }
            }

            const handleAddList = (type) => {
                formData[type].push({...def})
            }

            const handleDelList = (type, index) => {
                formData[type].splice(index, 1)
            }

            onMounted(() => {
                loadData()
            })

            return {
                formData,
                formRule,
                formRef,
                safetyType,
                loading,
                loadData,
                handleSubmit,
                handleAvatarSuccess,
                handleAddList,
                handleDelList
            }
        },

    }


    const app = createApp(App)
    app.component('Plus', Plus)
    app.component('Edit', Edit)
    app.component('Delete', Delete)
    app.component('Check', Check)
    app.component('Close', Close)
    app.use(ElementPlus,{
        locale: ElementPlusLocaleZhCn
    })
    app.mount('#app')
</script>
