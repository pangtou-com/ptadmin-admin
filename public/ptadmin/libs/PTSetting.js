layui.define(['layer', 'laytpl', 'PTForm'],function (exports) {
    const {$, laytpl, layer, PTForm} = layui
    const MOD_NAME = 'PTSetting'
    /** 分组ELE */
    const GROUP_ELE = $("#ptadmin-categorize-aside")
    /** 容器ELE */
    const SETTING_ELE = $(".ptadmin-categorize-box")
    const SETTING_TAG_ELE = $(".ptadmin-categorize-tabs")
    // 配置容器
    const CONTAINER_ELE = $("#setting-container")
    // 事件集合
    const events = {}
    /** 当前操作数据对象 */
    let current = undefined
    // 系统设置配置信息
    const group_data = {}
    // 系统配置下分组数据
    const group_tab_data = new Map()
    /** 数据集合 */
    const dataMap = new Map()

    /**
     * 系统设置点击事件，切换菜单
     */
    events.group = function () {
        const obj = $(this)
        obj.addClass('active').siblings().removeClass("active")
        const id = obj.data("id")
        const name = obj.data("name")
        renderTags(id, name)
    }

    /**
     * 配置标签点击事件，切换配置
     */
    events.tag = function () {
        $(this).addClass('active').siblings().removeClass("active")
        const type = $("[ptadmin-event=changeBox]").attr("data-type")
        const data = SETTING_TAG_ELE.find(".active").data()
        if (type === 'form') {
            renderForm(data)
        } else {
            renderTable(data)
        }
    }

    events.changeBox = function () {
        let type = $(this).attr('data-type') === 'form' ? 'table' : 'form'
        $(this).attr('data-type', type)
        const data = SETTING_TAG_ELE.find(".active").data()
        if (data === undefined) {
            return
        }
        if (type === 'form') {
            renderForm(data)
        } else {
            renderTable(data)
        }
    }

    /**
     * 解析数据
     * @param group
     * @param data
     */
    const parserData = function ({group, data}) {
        for (const key in group) {
            group_data[group[key].name] = group[key]
        }
        for (const key in data) {
            group_tab_data.set(key, data[key])
            for (const index in data[key]) {
                dataMap.set(data[key][index]['name'], data[key][index])
            }
        }
    }

    /**
     * 渲染表单
     * @param data
     */
    const renderForm = function ({id, name}) {
        if (!dataMap.has(name)) {
            // 渲染空配置
            return
        }
        const data = dataMap.get(name)
        if (!data['view']) {
            return;
        }
        CONTAINER_ELE.stop()
        CONTAINER_ELE.hide().html(data['view']).fadeIn(1200)
        PTForm.init()
    }

    /**
     * 渲染表格
     * @param id
     * @param name
     */
    const renderTable = function ({id, name}) {
        if (!dataMap.has(name)) {
            // 渲染空配置
            return
        }
        const data = dataMap.get(name)
        const temp = $("#table_html").html()
        if (!data['setting']) {
            return;
        }

        CONTAINER_ELE.stop()
        laytpl(temp).render({data: data['setting']}, function (str) {
            CONTAINER_ELE.hide().html(str).fadeIn(1200)
        })
    }

    /**
     * 渲染配置列表
     * @param id
     * @param name
     */
    const renderTags = function (id, name) {
        if (!group_tab_data.has(name)) {
            // 渲染空配置
            return
        }
        const children = group_tab_data.get(name)
        const header = []
        for (const key in children) {
            header.push(`<li class="tab" ptadmin-event="tag" data-name="${children[key].name}" data-id="${children[key].id}">${children[key].title}</li>`)
        }
        SETTING_TAG_ELE.html(header.join(""))
        action.call(SETTING_TAG_ELE.find("li").eq(0), "tag")
    }

    /**
     * 渲染系统设置页面
     */
    const renderGroup = function () {
        const html = []
        const temp = $("#group_html").html()
        for (const key in group_data) {
            laytpl(temp).render({
                name: group_data[key].name,
                title: group_data[key].title,
                id: group_data[key].id,
            }, function (str) {
                html.push(str)
            })
        }
        GROUP_ELE.html(html.join(""))
        action.call(GROUP_ELE.find("li").eq(0), "group")
    }

    /**
     * 事件绑定
     */
    const eventBind = function () {
        SETTING_ELE.on("click", "*[ptadmin-event]", function (e) {
            const event = $(this).attr("ptadmin-event")
            const stop = $(this).attr("ptadmin-event-stop")
            if (stop !== undefined) {
                e.stopPropagation()
            }
            action.call(this, event)
        })
    }

    /**
     * 执行事件
     */
    const action = function (event) {
        const param = {
            events: events,
            event: event,
            ele: this,
        }
        if (events["*"] !== undefined) {
            events["*"].call(param.ele, param)
        }
        if (events[event] !== undefined) {
            events[event].call(param.ele, param)
        }
    }

    const setting = {
        init: function (data) {
            if (data === undefined) {
                return
            }
            parserData(data)
            renderGroup()
            eventBind()
        },
        on: function (event, callback) {
            events[event] = callback
        }
    }

    exports(MOD_NAME, setting)
})
