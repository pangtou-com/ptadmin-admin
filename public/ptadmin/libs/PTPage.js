/**
 * 列表页面
 * Author:  Zane
 * Email: 873934580@qq.com
 */
/**
 * 按钮的结构
 * btn = {
 *     text: 'submit',
 *     icon: 'layui-icon-edit',
 *     class: '自定义样式',
 *     event: '事件名称',
 *     type: 'default' // 按钮类型对应按钮主题
 * }
 */
layui.define(['table', 'common', 'PTRender', 'form', 'PTSearchFormat'], function (exports) {
    "use strict";
    const MOD_NAME = "PTPage";
    const DEFAULT_TABLE_ELE = "dataTable"
    const { PTRender, common, $, form, table, PTSearchFormat } = layui;

    const ELE = ".ptadmin-page-container";

    /** 各场景url键名 */
    const SCENE_URL = {
        index: 'index_url',
        create: 'create_url',
        edit: 'edit_url',
        del: 'del_url',
        show: 'show_url',
        export: 'export_url',
        import: 'import_url',
        status: 'status_url'
    }

    /** 默认页面配置信息 */
    const DEFAULT_CONFIG = {
        urls: {index_url: "", create_url: "", edit_url: "", del_url: "", show_url: "", status_url: "", import_url:'', export_url: ''},
        title: { create: 'New Add', edit: 'Edit', export: 'Export', del_confirm: '' }, // 各个事件对应名称
        btn_left: ['create', 'refresh', 'del'], // 左侧按钮组
        // btn_right: ['export', 'import'], // 右侧按钮组
        search: {
            label: false, // 搜索区域是否展示label
        },
        info: {             // 信息展示区域设置（暂未实现）
            theme: 'default',
            icon: '',
            title: '',
            intro: ''
        },
        tabs: {}        // tab选项卡设置 （暂未实现）
    }

    /** 默认按钮配置信息 */
    const DEFAULT_BTN = {
        create: {icon: 'layui-icon layui-icon-add-1', event: 'create', theme: 'primary'},
        refresh: {icon: 'layui-icon layui-icon-refresh', event: 'refresh', theme: 'info'},
        del: {icon: 'layui-icon layui-icon-delete', event: 'batch_del', theme: 'danger', selected: true},
        export: {icon: 'layui-icon layui-icon-download-circle', event: 'export', theme: 'default'},
        import: {icon: 'layui-icon layui-icon-upload-drag', event: 'import', theme: 'default'},
        search: {icon: 'layui-icon layui-icon-search', event: 'search', theme: 'warn'},
        show: {icon: 'layui-icon layui-icon-eye', event: 'show', theme: 'warn'},
        edit: {icon: 'layui-icon layui-icon-edit', event: 'edit', theme: 'warn'},
    }

    /** 按钮主题对应的样式组 */
    const BTN_THEME = {
        default: 'layui-btn-primary layui-btn layui-btn-sm', // 默认
        primary: 'layui-bg-blue layui-btn layui-btn-sm', // 主要
        danger: 'layui-bg-red layui-btn layui-btn-sm',  // 危险
        success: 'layui-bg-purple layui-btn layui-btn-sm', // 成功
        info: 'layui-btn layui-btn-sm', // 信息
        warn: 'layui-bg-orange layui-btn layui-btn-sm', // 警告
    }

    /**
     * 列表按钮规则
     * @param type
     * @returns {{}|(*&{event, class: *})}
     */
    const getTableBtnOptions = (type) => {
        if (DEFAULT_BTN[type] === undefined) {
            return {}
        }

        return {
            ...DEFAULT_BTN[type],
            class: BTN_THEME[DEFAULT_BTN[type].theme].replace("layui-btn-sm", "layui-btn-xs"),
            event: type,
            'lay-event': type
        }
    }

    /**
     * 将参数合并提取处理，合并默认参数并分类提取出表格参数和预设参数
     * @param option
     * @param url
     */
    const getTableOptions = function (option = {}, url) {
        let tableCon = {elem: `#${DEFAULT_TABLE_ELE}`, url: '', cols: [], toolbar: false, page: true}
        $.extend(true, tableCon, option);
        if (tableCon.url === "" || tableCon.url === undefined) {
            tableCon.url = url
        }
        const operateHandle = function (item, i, a) {
            // 如果开启了工具栏或自定义模版渲染，就不需要在使用预定义的方案处理了
            if (item.toolbar !== undefined || item.templet !== undefined) {
                return
            }
            // 未指定的情况下设置为输出全部预设按钮
            let operates = item.operate || true;
            let type = layui._typeof(operates);
            const action = {
                'array': function () {
                    let html = [];
                    for (let i in operates) {
                        let type = layui._typeof(operates[i])
                        const option = type === 'string' ? getTableBtnOptions(operates[i]): operates[i];
                        html.push(PTRender.render(option))
                    }
                    return PTRender.render({class: "layui-btn-group", tagName: 'div', text: html.join("")});
                },
                'object': function () {
                    let html = [];
                    for (let i in  operates) {
                        html[i] = PTRender.render(operates[i]);
                    }
                    return PTRender.render({class: "layui-btn-group", tagName: 'div', text: html.join("")});
                },
                'string': function () {
                    return PTRender.render(getTableBtnOptions(operates));
                },
                'function': function (data) {
                    return operates.call(this, data);
                }
            }
            if (type === 'boolean' && operates !== false) {
                type = 'array';
                operates = ['edit', 'del']
            }

            // 未指定的类型
            if (action[type] === undefined) {
                console.error("未定义的类型：" + type);
                return
            }
            tableCon['cols'][i][a]['templet'] = action[type];
        }
        const { cols } = tableCon;
        const search = []
        for (let i in cols) {
            for (let a in cols[i]) {
                if (cols[i][a].fixed === 'right' && cols[i][a].operate !== undefined) {
                    operateHandle(cols[i][a], i, a)
                    continue
                }
                if (cols[i][a].search !== undefined) {
                    search.push(cols[i][a])
                }
                if (tableCon['cols'][i][a]['templet'] === "" || tableCon['cols'][i][a]['templet'] === undefined) {
                    tableCon['cols'][i][a]['templet'] = PTTableFormat.default;
                    continue
                }
                const templet = tableCon['cols'][i][a]['templet']
                if (typeof templet === 'string' && templet[0] !== '#') {
                    tableCon['cols'][i][a]['templet'] = PTTableFormat[tableCon['cols'][i][a]['templet']] || PTTableFormat.default;
                }
            }
        }

        return {
            option: tableCon,
            search: search
        }
    };

    /**
     * 列表表格格式化工具
     */
    const PTTableFormat = {
        default: function (data) {
            let val = common.getTableColValue(data);
            if (val === undefined || val === "" || val === null) {
                return "-";
            }
            return val;
        },
        icon: function (data) {
            let val = common.getTableColValue(data);
            return val ? `<i class="${val}"></i>` : "";
        },
        image: function () {

        },
        images: function () {

        },
        tips: function (data) {

        },
        switch: function (data) {
            let val = common.getTableColValue(data);
            let id = common.getTableColValue(data, 'id');
            let checked = val ? ' checked ': '';
            let field = data.LAY_COL.field;
            return `<input type="checkbox" data-name="${field}" value="${id}" lay-filter="ptadmin-switch" lay-skin="switch" lay-text="ON|OFF" ${checked} >`
        },
        url: function (data) {
            let url = common.getTableColValue(data);
            return `<a href="${url}" target="_blank">${url}</a>`
        },
        datetime: function (data){
            let val = common.getTableColValue(data);
            if (val && moment !== undefined) {
                let m = moment(val);
                if (m.isValid()) {
                    return val; // 事件日期无效 原样返回
                }
                return m.format('YYYY-MM-DD HH:mm:ss')
            }
            return val;
        },
        label: function () {

        },
        tags: function (data) {
            return "123";
        },
        whether: function (data) {
            let val = common.getTableColValue(data);
            let text = ['否', '是'];
            let textClass = [
                'layui-badge layui-bg-orange',
                'layui-badge btn-theme'
            ];
            return `<span class="${textClass[val]}">${text[val]}</span>`
        }
    }

    const PTTableEvent = {
        create: function (ele) {
            let url = $(ele).data('url');
            if (url === undefined || url === '') {
                url = this.getSceneUrl(SCENE_URL.create);
            }
            common.formOpen(url, this.config.title.create);
        },
        edit: function (obj) {
            let thiz = this
            let url = common.urlReplace(thiz.getSceneUrl(SCENE_URL.edit), obj.data);
            common.formOpen(url, thiz.config.title.edit);
        },
        del: function (obj) {
            let thiz = this
            let url = common.urlReplace(thiz.getSceneUrl(SCENE_URL.del), obj.data);
            layer.confirm(thiz.config.title.del_confirm || '确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function(index){
                common.del(url, {id: obj.data.id}, function (res) {
                    if (res.code === 0) {
                        thiz.getCurrentTable.reloadData();
                    } else {
                        layer.msg(res.message, {icon: 3});
                    }
                });
                layer.close(index);
            });
        },
        show: function(obj) {
            let thiz = this
            let url = common.urlReplace(thiz.getSceneUrl(SCENE_URL.show), obj.data);
            common.formOpen(url);
        },
        commonSwitch: function (data) {
            let thiz = this
            const param = {
                field: $(data.elem).attr('data-name'),
                value: data.elem.checked === true ? 1 : 0,
                is_edit: 1
            }
            let url = common.urlReplace(thiz.getSceneUrl(SCENE_URL.status), {id: data.value, value: param.value});
            common.put(url, param, function (res) {
                if (res.code !== 0) {
                    layer.msg(res.message, { icon: 2 });
                    data.elem.checked = !data.elem.checked
                    setTimeout(() => {
                        form.render("checkbox")
                    }, 1000)
                    return
                }
                layer.msg(res.message, { icon: 1 });
            })
        },
        refresh: function () {
            this.getCurrentTable.reload();
        }
    }

    /**
     * 列表页面搜索构建
     */
    class PTSearch {
        handle = undefined
        config = undefined
        page = undefined
        constructor(config, _page) {
            this.config = config
            this.page = _page
        }

        mount() {
            const html = this.__wrap(this.__buildSearchHtml())
            this.handle = $(html)
            const thiz = this
            $(`${ELE}`).prepend(this.handle)
            form.render()
            this.handle.on("click", "*[ptadmin-event]", function () {
                const event = $(this).attr("ptadmin-event")
                if (event === 'reset') {
                    thiz.page.reload()
                    return
                }
                thiz.page.action(event, {
                    target: this,
                    event: event
                });
            })

            form.on("submit(search-submit)", function ({ field }) {
                thiz.page.reload(field)
                return false
            })
        }

        /**
         * 包装搜索
         * @private
         */
        __wrap(html) {
            html = html + this.__buildSearchBtnHtml()
            return `<div class="ptadmin-page-box">
                        <form class="layui-form layui-row layui-col-space16 layui-form-pane ptadmin-search-form" action="">
                            ${html}
                        </form>
                     </div>`
        }

        /**
         * 构建搜索表单
         * @returns {string}
         * @private
         */
        __buildSearchHtml() {
            const html = [];
            for (const key in this.config) {
                html.push(this.__parseSearchRule(this.config[key]))
            }

            return html.join("")
        }

        /**
         * 构建搜索按钮
         * @private
         */
        __buildSearchBtnHtml() {
            return `<div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3">
                        <button class="layui-btn-primary layui-btn layui-btn-sm" lay-submit lay-filter="search-submit">
                            <i class="layui-icon layui-icon-search"></i>
                            搜索
                        </button>
                        <button type="reset" class="layui-btn layui-btn-sm" ptadmin-event="reset">重置</button>
                    </div>`
        }

        /**
         * 解析规则
         * @param rule
         * @private
         */
        __parseSearchRule(rule) {
            const search = rule.search
            // 支持使用函数方式定义搜索类型
            if (layui._typeof(search) === 'function') {
                return search(rule)
            }
            let type = search.type !== undefined ? search.type : 'text'
            if (PTSearchFormat[type] === undefined) {
                console.error(`未定义的搜索类型【${type}】`)
                return
            }

            const input = PTSearchFormat[type](rule)
            const label = this.__getLabelHtml(rule)
            const html = label + this.__getSymbolHtml(rule) + input

            return `<div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">${html}</div>`
        }

        /**
         * 获取 label
         * @param rule
         * @returns {string}
         * @private
         */
        __getLabelHtml(rule) {
            const label = common.data_get(this.page.config, 'search.label')
            if (label !== false) {
                return `<label for="${rule.field}" class="layui-form-label ptadmin-label">${rule.title}</label>`
            }
            return ""
        }

        __getSymbolHtml(rule) {
            const { op } = rule.search || {}
            if (op === undefined) {
                return ""
            }

            return PTSearchFormat["op"](rule)
        }
    }

    /**
     * 列表页面按钮
     */
    class PTPageBtn {
        /** 页面对象 */
        page = undefined

        /** 按钮工具栏对象 */
        handle = undefined

        /** 原始配置信息 */
        config = undefined

        /** 是否开启简易关键词搜索功能 */
        __search = false

        constructor(config, _page) {
            this.config = config
            this.page = _page
        }

        mount() {
            const html = this.__wrap()
            this.handle = $(html)
            const thiz = this
            $(`${ELE}`).prepend(this.handle)
            this.handle.on("click", "*[ptadmin-event]", function () {
                const event = $(this).attr("ptadmin-event")
                thiz.page.action(event, {
                    target: this,
                    event: event
                });
            })
            this.onSearchSubmit()
        }

        /**
         * 触发表单监听事件
         */
        onSearchSubmit() {
            if (this.__search === false) {
                return
            }
            const thiz = this
            const onSearchEvent = ()  => {
                const keyword = $("input[name=keywords]").val()
                thiz.page.reload({
                    keywords: keyword
                })
            }
            this.handle.on("submit", function () {
                onSearchEvent()
                return false
            })
            this.handle.on("click", "*[ptadmin-event=keywords]", function () {
                onSearchEvent()
            })
        }

        setOpenSearch() {
            this.__search = true
        }

        /**
         * 包装头部按钮
         * @private
         */
        __wrap() {
            let letHtml = this.__getBtnHtml(this.config['btn_left'] || undefined)
            let rightHtml = this.__getBtnHtml(this.config['btn_right'] || undefined)
            // 如果没有显示声明取消按钮分组 则默认加上分组设置
            if (this.config['btn_group'] !== false) {
                if (letHtml !== "") {
                    letHtml = `<div class="layui-btn-group">${letHtml}</div>`
                }
                if (rightHtml !== '') {
                    rightHtml = `<div class="layui-btn-group">${rightHtml}</div>`
                }
            }

            let search = this.__getSearchHtml();

            return `<div class="layui-card-header ptadmin-header">
                        <div class="left">${letHtml}</div>
                        <div class="right">${search}${rightHtml}</div>
                    </div>`
        }

        /**
         * 关键词搜索框
         * @returns {string}
         * @private
         */
        __getSearchHtml() {
            if (this.__search === false) {
                return ""
            }
            let html = `<input type="text" placeholder="请输入关键词" value="" name="keywords" class="layui-input">`
            let btn = ` <div class="layui-input-split layui-input-suffix" ptadmin-event="keywords">
                            <i class="layui-icon layui-icon-search"></i>
                        </div>`
            return `<form class="layui-form"><div class="layui-input-group">${html}${btn}</div></form>`
        }

        /**
         * 获取按钮组html
         * @param btn
         * @returns {string}
         * @private
         */
        __getBtnHtml(btn) {
            if (btn === undefined) {
                return ""
            }
            const html = [];
            for (let item of btn) {
                if (layui._typeof(item) === 'string') {
                    if (DEFAULT_BTN[item] === undefined) {
                        console.error(`${item} 配置不存在`)
                        continue
                    }
                    item = DEFAULT_BTN[item]
                }
                if (item.theme !== undefined && BTN_THEME[item.theme] !== undefined) {
                    item['class'] = BTN_THEME[item.theme]
                }

                html.push(PTRender.render(item))
            }

            return html.join("")
        }
    }

    /**
     * 列表页面构建
     * @see https://docs.pangtou.com/pages/table
     */
    class PTPage {
        /** 当前table对象 */
        current = undefined

        /** 当前页面事件集合 */
        events = PTTableEvent

        /** 当前搜索配置项 */
        search = undefined

        /** table 配置项 */
        options = undefined

        /** 页面配置项目 */
        config = DEFAULT_CONFIG

        /** 页面按钮对象 */
        btn = undefined

        /**
         * @param options 选项设置
         * @see https://www.pangtou.com
         */
        constructor(options) {
            let table_options = options['table'];
            delete options['table'];
            let config = Object.assign(DEFAULT_CONFIG, options)
            if (layui._typeof(table_options) === 'array') {
                table_options = {
                    cols: layui._typeof(table_options[0]) === 'array' ? table_options : [table_options]
                }
            }

            const { option, search }= getTableOptions(table_options, this.getSceneUrl());
            this.options = option

            this.__initialize(config, search)
        }

        /**
         * 初始化
         * @param config
         * @param search
         * @private
         */
        __initialize(config, search) {
            this.config = Object.assign(this.config, config)
            this.btn = new PTPageBtn(this.config, this)
            // 如果显示声明取消搜索区域则不展示搜索信息
            if (config['search'] !== false) {
                // 挂载搜索信息
                if (search.length > 0) {
                    this.search = new PTSearch(search, this)
                } else {
                    // 没有设置搜索信息则使用默认搜索
                    this.btn.setOpenSearch()
                }
            }
        }

        /**
         * 监听事件
         * @param event
         * @param callback
         * @returns {PTPage}
         */
        on = (event, callback) => {
            this.events[event] = callback

            return this
        }

        /**
         * 移除事件
         * @param event
         */
        off = (event) => {
            if (this.events[event] !== undefined) {
                delete this.events[event]
            }
        }

        /**
         * 判断事件是否存在
         * @param event
         * @returns {boolean}
         */
        hasEvent = (event) => {
            return this.events[event] !== undefined
        }

        /**
         * 触发事件
         * @param event
         * @param option
         */
        action = (event, option) => {
            if (this.events[event] !== undefined) {
                this.events[event].call(this, option)
            }
        }

        /**
         * 挂载
         */
        mount () {
            if (this.current !== undefined) {
                return
            }
            this.current = true
            const thiz = this
            setTimeout(() => {
                thiz.current = table.render(thiz.options);
                table.on(`tool(${thiz.current.config.id})`, function(obj) {
                    thiz.action(obj['event'], obj)
                })
                table.on(`checkbox(${thiz.current.config.id})`, function(obj) {
                    obj['event'] = 'checkbox'
                    thiz.action(obj['event'], obj)
                })
                table.on(`radio(${thiz.current.config.id})`, function(obj) {
                    obj['event'] = 'radio'
                    thiz.action(obj['event'], obj)
                })
                thiz.bindFormEvent()
            }, 0)

            // 页面按钮挂载
            this.btn.mount()
            // 页面搜索挂载
            this.search?.mount()
        }

        /**
         * 绑定列表中的表单事件
         */
        bindFormEvent(){
            const thiz = this
            // 切换按钮处理事件
            form.on('switch(ptadmin-switch)', function(data){
                thiz.action("commonSwitch", data)
            });
        }

        /**
         * 获取场景的URL请求地址
         * @param scene
         * @returns {*}
         */
        getSceneUrl(scene = SCENE_URL.index) {
            let url = this.config["urls"][scene]
            if (url !== undefined && url !== "") {
                return url
            }
            url = common.getUrl()
            if (scene !== SCENE_URL.index) {
                url = `${url}/{id}`
            }
            return url
        }

        reload(search = {}, page = 1) {
            if (this.hasEvent('search')) {
                this.action('search', search)
                return
            }
            this.current?.reload({
                where: search,
                page
            })
        }

        /**
         * 获取当前table对象
         * @returns {undefined|table}
         */
        get getCurrentTable() {
            return this.current
        }

        /**
         * 单元格格式化工具
         */
        static get format () {
            return PTTableFormat
        }

        static make = (options) => {
            let thiz = new PTPage(options)
            thiz.mount()
            return thiz
        }
    }


    exports(MOD_NAME, PTPage)
})



