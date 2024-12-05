layui.define(['layer', 'laytpl', 'PTForm', 'form'],function (exports) {
    const {$, laytpl, layer, PTForm, form} = layui
    const MOD_NAME = 'PTSetting'
    /** 分组ELE */
    const GROUP_ELE = $("#ptadmin-categorize-aside")
    const MAIN_ELE = $(".ptadmin-categorize-main")
    /** 容器ELE */
    const SETTING_ELE = $(".ptadmin-categorize-box")
    const SETTING_TAG_ELE = $(".ptadmin-categorize-tabs")

    // 配置容器
    const CONTAINER_ELE = $("#setting-container")
    const GROUP_TYPE_SETTING = 'setting'
    const GROUP_TYPE_MANAGE = 'manage'
    // 事件集合
    const events = {}
    /** 当前操作数据对象 */
    let current = undefined
    /** 当前操作类型，系统配置（setting）or 配置管理（manage）*/
    let current_group_type = GROUP_TYPE_SETTING
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
        if (current_group_type === GROUP_TYPE_SETTING) {
            MAIN_ELE.find(".ptadmin-setting-form").show()
            MAIN_ELE.find(".ptadmin-setting-table").hide()
            renderTags(id, name)
            return
        }
        MAIN_ELE.find(".ptadmin-setting-form").hide()
        MAIN_ELE.find(".ptadmin-setting-table").show()
        action(id)
    }

    /**
     * 配置标签点击事件，切换配置
     */
    events.tag = function () {
        $(this).addClass('active').siblings().removeClass("active")
        const data = SETTING_TAG_ELE.find(".active").data()
        renderForm(data)
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
     * @param group     分组数据
     * @param data      配置信息
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

        if (!data['view'] || '' === data['view']) {
            CONTAINER_ELE.finish().hide().html(`<div>暂无配置数据</div>`).fadeIn(1000)
            return ;
        }
        renderFieldIntro(data);
        CONTAINER_ELE.finish().hide().html(`<form action="" class="layui-form" data-id="${id}" lay-filter="save-config-data">${data['view']}</form>`).fadeIn(1000)
        form.render()
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
        laytpl(temp).render({data: data['setting']}, function (str) {
            CONTAINER_ELE.finish().hide().html(str).fadeIn(1200)
        })
    }

    /**
     * 渲染表格
     * @param type
     */
    const renderAjaxTable = function (type) {
        console.log(type)

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
    const renderGroup = function (data) {
        const html = []
        const temp = $("#group_html").html()
        for (const key in data) {
            laytpl(temp).render({
                name: data[key].name,
                title: data[key].title,
                id: data[key].id,
            }, function (str) {
                html.push(str)
            })
        }
        GROUP_ELE.html(html.join(""))
        action.call(GROUP_ELE.find("li").eq(0), "group")
    }

    /**
     * 渲染字段介绍.
     *
     * @param data
     * @param fieldName
     */
    const renderFieldIntro = function (data,fieldName) {
        let groupTitle = data.title;
        let groupName = data.name;
        let fieldShowMap = new Map();
        for (let item of data.setting) {
            if (fieldName === undefined) {
                fieldShowMap.set('title', item.title).set('command', "{$pt."+groupName+"."+item.name+"}").set('intro', item.intro);
                break;
            }
            if (item.name === fieldName) {
                fieldShowMap.set('title', item.title).set('command', "{$pt."+groupName+"."+fieldName+"}").set('intro', item.intro);
                break;
            }
        }
        if (fieldShowMap.size === 0) {
            layer.msg('字段不存在', { icon: 2 });
            return;
        }
        let command = $("#setting-command");
        command.find("[name='field-group']").html(groupTitle);
        command.find("[name='field-title']").html(fieldShowMap.get('title'));
        command.find("[name='field-command']").html(fieldShowMap.get('command'));
        command.find("[name='field-intro']").html(fieldShowMap.get('intro') ?? "无");
    }

    /**
     * 事件绑定
     */
    const eventBind = function () {
        SETTING_ELE.off('click', "*[ptadmin-event]").on("click", "*[ptadmin-event]", function (e) {
            const event = $(this).attr("ptadmin-event")
            const stop = $(this).attr("ptadmin-event-stop")
            if (stop !== undefined) {
                e.stopPropagation()
            }
            action.call(this, event, e)
        })
    }

    /**
     * 执行事件
     */
    const action = function (event, e) {
        const param = {
            events: events,
            event: event,
            ele: this,
            e
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
            renderGroup(group_data)
            eventBind()
        },
        on: function (event, callback) {
            events[event] = callback
        },
        change: function (type, menu) {
            current_group_type = type
            renderGroup(type === GROUP_TYPE_MANAGE ? menu : group_data )
        },
        getData: function (fieldGroupKey, fieldKey) {
            let thisGroup = dataMap.get(fieldGroupKey);
            if (thisGroup === undefined) {
                return ;
            }
            if (thisGroup.setting === undefined || !Array.isArray(thisGroup.setting)) {
                return ;
            }
            renderFieldIntro(thisGroup, fieldKey)
        }
    }

    exports(MOD_NAME, setting)
})
