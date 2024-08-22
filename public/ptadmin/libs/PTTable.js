/**
 * 列表页面
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2021/12/7.
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
layui.define(['table', 'common', 'PTRender', 'form', 'PTSearch'], function (exports) {
    "use strict";
    const MOD_NAME = "PTTable";
    const { PTRender, common, $, form, table, PTSearch } = layui;
    const $body = $('body')

    // table 表单默认参数
    const DEFAULT_TABLE_OPTIONS = {
        elem: "#dataTable",
        url: '',
        cols: [],
        toolbar: false,
        page: true,
    };

    const DEFAULT_OPTIONS = {
        event: {
            create: 'ptadmin-event-create',
            edit: 'ptadmin-event-edit',
            del: 'ptadmin-event-del',
            search: 'ptadmin-event-search',
            export: 'ptadmin-event-export',
            show: 'ptadmin-event-show',
        },
        urls: {
            index_url: "",
            create_url: "",
            edit_url: "",
            del_url: "",
            show_url: "",
            status_url: "",
            import_url:'',
            export_url: '',
        },
        title: {
            create: 'New Add',
            edit: 'Edit',
            export: 'Export'
        },
        btn_left: ['create', 'refresh', 'del'],
        btn_right: ['export', 'import', 'search'],
    }

    /**
     * 将参数合并提取处理，合并默认参数并分类提取出表格参数和预设参数
     * @param option
     */
    const getTableOptions = function (option = {}) {
        let tableCon = JSON.parse(JSON.stringify(DEFAULT_TABLE_OPTIONS))
        let preset = JSON.parse(JSON.stringify(DEFAULT_OPTIONS))

        // 分离两种配置信息
        for (let i in option) {
            if (DEFAULT_OPTIONS[i] === undefined) {
                tableCon[i] = option[i];
                continue
            }
            preset[i] = option[i];
        }

        // 处理URL
        if (tableCon.url === "" || tableCon.url === undefined) {
            if (!preset.urls.index_url) {
                console.error("未设置请求地址, 请设置index_url地址");
                return;
            }
            tableCon.url = preset.urls.index_url;
        }

        const operateHandle = function (item, i, a) {
            // 如果开启了工具栏和自定义模版渲染，就不需要在使用预定义的方案处理了
            if (item.toolbar !== undefined || item.templet !== undefined) {
                return true;
            }
            // 未指定的情况下设置为输出全部预设按钮
            let operates = item.operate || true;
            let type = layui._typeof(operates);
            const action = {
                'array': function () {
                    let html = [];
                    for (let i in operates) {
                        let type = layui._typeof(operates[i])
                        const option = type !== 'string' ?  operates[i] : {};
                        html[i] = PTRender.render(option, type === 'string' ? operates[i] : operates[i]);
                    }
                    return PTRender.render({class: "layui-btn-group", name: 'div', content: html.join("")});
                },
                'object': function () {
                    let html = [];
                    for (let i in  operates) {
                        html[i] = PTRender.render(operates[i], i);
                    }
                    return PTRender.render({class: "layui-btn-group", name: 'div', content: html.join("")});
                },
                'string': function () {
                    return PTRender.render({}, operates);
                },
                'function': function (data) {
                    return operates.call(this, data);
                }
            }
            if (type === 'boolean' ) {
                type = 'object';
                operates = {
                    edit: {  className: '', icon: '', title: '' },
                    del: {}
                }
            }

            // 未指定的类型
            if (action[type] === undefined) {
                console.error("未定义的类型：" + type);
                return true;
            }
            tableCon['cols'][i][a]['templet'] = action[type];
            return true;
        }

        // 操作列处理, 这里应该有多种参数类型 array，object, bool
        // function如果不设置就提供默认的edit，del， 也可以指定不显示操作行
        const { cols } = tableCon;
        const search = []
        for (let i in cols) {
            for (let a in cols[i]) {
                if (cols[i][a].fixed === 'right') {
                    operateHandle(cols[i][a], i, a)
                    continue
                }
                if (cols[i][a].search !== undefined) {
                    search.push(cols[i][a])
                }

                if (tableCon['cols'][i][a]['templet'] === "" || tableCon['cols'][i][a]['templet'] === undefined) {
                    tableCon['cols'][i][a]['templet'] = PTTable.format.default;
                    continue
                }
                const templet = tableCon['cols'][i][a]['templet']
                if (typeof templet === 'string' && templet[0] !== '#') {
                    tableCon['cols'][i][a]['templet'] = PTTable.format[tableCon['cols'][i][a]['templet']];
                }
            }
        }

        return {
            config: tableCon,
            options: preset,
            search: search
        }
    };


    const PTTableEvent = {
        edit: function (obj) {
            let url = common.urlReplace(DEFAULT_OPTIONS.urls.edit_url, obj.data);
            common.formOpen(url, DEFAULT_OPTIONS.title.edit);
        },
        del: function (obj) {
            let url = common.urlReplace(DEFAULT_OPTIONS.urls.del_url, obj.data);
            layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function(index){
                common.del(url, {id: obj.data.id}, function (res) {
                    if (res.code === 0) {
                        PTTable.currentTable.reloadData();
                    } else {
                        layer.msg(res.message, {icon: 3});
                    }
                });
                layer.close(index);
            });
        },
        show: function(obj) {
            let url = common.urlReplace(DEFAULT_OPTIONS.urls.show_url, obj.data);
            common.formOpen(url);
        },
        status: function (obj, dataTable) {},
        commonSwitch: function (obj, dataTable) {
            console.log(obj);
        }
    }

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
        // 展示只有两种状态的开关0，1
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

    /**
     * 表单头部按钮组
     */
    class PTTableHeaderBtn {
        // 按钮组
        buttons = {}
        constructor(config) {

        }

        /**
         * 挂载按钮组对象
         */
        mount(elm){}

        /**
         * 按钮组事件监听
         */
        on = () => {}
    }

    const PTTable = {
        currentTable: null,
        event: PTTableEvent,
        format: PTTableFormat,
        search: function () {
            const table = {
                config: {
                    form: "#form",
                    click: "#search",
                    tableObj: undefined,
                    callback: undefined
                },
                init:(option = {}) => {
                    Object.assign(table.config, option);
                    $(table.config.click).click(function () {
                        table.query();
                    });
                },
                query:() => {
                    let input = $(table.config.form).find("input");
                    let select = $(table.config.form).find("select");
                    let where = table.getParam(input);
                    Object.assign(where, table.getParam(select));
                    if (Object.keys(where).length === 0) return;
                    if (typeof table.config.callback === "function") {
                        table.config.callback(where);
                        return;
                    }
                    if (PTTable.currentTable !== undefined ){
                        PTTable.currentTable.reload({
                            where: where,
                            page: {
                                curr: 1
                            }
                        });
                    }
                },
                getParam: (obj) => {
                    let where = {};
                    if (obj.length === 0) {
                        return where;
                    }
                    obj.each(function (i, a) {
                        let name = $(a).attr('name');
                        if (name !== undefined && $(a).val() !== "") {
                            where[name] = $(a).val();
                        }
                    });
                    return where;
                },
            }
            return {init: table.init};
        },
        bindFormEvent: function (options) {
            let thiz = this;
            form.on('switch(ptadmin-switch)', function(data){
                if (!options.urls.status_url) {
                    console.error("未定义状态地址")
                    return
                }
                const param = {
                    field: $(data.elem).attr('data-name'),
                    value: data.elem.checked === true ? 1 : 0,
                    is_edit: 1
                }
                let url = common.urlReplace(options.urls.status_url, {id: data.value, value: param.value});
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
            });

            const events = {
                // 创建窗口
                create: function () {
                    let url = options.urls.create_url;
                    if (!url) {
                        url = $(this).attr('data-url');
                    }
                    common.formOpen(url, options.title.create);
                },
                // 简易关键词搜索功能
                keyword: function () {
                    let keyword = $($(this).siblings()).find('input').val();
                    if (keyword) {
                        thiz.currentTable.reloadData({
                            where: { keywords: keyword },
                            current: 1
                        })
                    }
                },
                search: function () {
                    $("#zane-search").slideToggle();
                },
                reload: function () {
                    thiz.currentTable.reloadData({current: 1})
                },
                recycle: function (obj) {
                    obj = $(obj[0])
                    const val = obj.attr('ptadmin-event-val')
                    if (val !== 'recycle') {
                        obj.attr('ptadmin-event-val', 'recycle')
                        obj.html('返回列表')
                        thiz.currentTable.reloadData({where: {recycle: 1}, current: 1})
                    } else {
                        obj.attr('ptadmin-event-val', '')
                        obj.html('回收站')
                        thiz.currentTable.reloadData({where: {recycle: 0}, current: 1})
                    }
                }
            }

            $body.on('click', '*[ptadmin-event]', function () {
                const $this = this
                const event = $($this).attr('ptadmin-event')
                if (events[event] && typeof events[event] === 'function') {
                    events[event].call($this, $($this))
                }else if(thiz.event[event] && typeof thiz.event[event] === 'function') {
                    thiz.event[event].call($this, $($this))
                }
            })
        },
        render: function (option = {}) {
            const thiz = this;
            const { config, options, search }= getTableOptions(option);
            const current = table.render(config);
            thiz.currentTable = current
            // 绑定控制行事件
            table.on(`tool(${current.config.id})`, function(obj) {
                bindTableEvent(obj)
            });

            table.on(`checkbox(${current.config.id})`, function(obj) {
                obj['event'] = 'checkbox'
                bindTableEvent(obj)
            });

            const bindTableEvent = function (obj) {
                if (thiz.event[obj.event] !== undefined) {
                    thiz.event[obj.event].call(this, obj, current);
                    return;
                }
                if (options.event[obj.event] !== undefined) {
                    options.event[obj.event].call(this, obj, current);
                    return;
                }
                console.error(`未定义的响应事件【${obj.event}】`);
            }

            // 绑定事件
            this.bindFormEvent();

            return current;
        },
        on: function (event, callback){
            if (!this.currentTable) {
                console.error("请在渲染表格之前调用on方法")
                return
            }
            this.event[event] = callback;
        },
        getData: function () {
            return table.getData('dataTable')
        }
    }

    exports(MOD_NAME, PTTable)
});
