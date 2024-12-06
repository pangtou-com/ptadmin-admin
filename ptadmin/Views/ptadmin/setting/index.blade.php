@extends('ptadmin.layouts.base')
@section('content')
<div class="configure ptadmin-categorize-box">
    <header class="ptadmin-categorize-header">
        <div class="title">系统配置</div>
        <div class="right layui-btn-group">
            <button type="button" class="layui-btn layui-bg-blue" ptadmin-event="setting" data-type="setting">
                <i class="layui-icon layui-icon-windows"></i> 系统配置
            </button>
            <button type="button" class="layui-btn" ptadmin-event="setting" data-type="manage">
                <i class="layui-icon layui-icon-tabs"></i> 配置管理
            </button>
        </div>
    </header>
    <div class="ptadmin-categorize-container">
        <aside class="ptadmin-categorize-aside">
            <!--左侧导航-->
            <ul class="lists" id="ptadmin-categorize-aside"></ul>
        </aside>
        <main class="ptadmin-categorize-main">
            <div class="layui-card ptadmin-setting-form">
                <div class="card-header">
                    <ul class="ptadmin-categorize-tabs"></ul>
                </div>
                <div class="layui-card-body">
                    <div id="setting-container" ptadmin-event="form-click" class="layui-col-xs6">
                    </div>
                    <div id="setting-command" class="layui-col-xs6">
                        <x-hint>字段配置信息</x-hint>
                        <div class="layui-form-item">
                            <label class="layui-form-label">字段分组：</label>
                            <p class="layui-input-block" name="field-group"></p>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">字段名称：</label>
                            <p class="layui-input-block" name="field-title"></p>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">字段指令：</label>
                            <p class="layui-input-block" name="field-command"></p>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">字段描述：</label>
                            <p class="layui-input-block" name="field-intro"></p>
                        </div>
                    </div>
                    <div class="container-footer layui-btn-group" style="display: block">
                        <button class="layui-btn layui-bg-blue" id="saveConfig" ptadmin-event="saveConfig">保存</button>
                    </div>
                </div>
            </div>
            <div class="layui-card ptadmin-setting-table">
                <div class="layui-card-header">
                    <div class="layui-btn-group">
                        <button class="layui-btn layui-btn-sm layui-bg-blue" ptadmin-event="create">
                            <i class="layui-icon layui-icon-add-1"></i>
                        </button>
                        <button class="layui-btn layui-btn-sm" ptadmin-event="refresh">
                            <i class="layui-icon layui-icon-refresh"></i>
                        </button>
                    </div>
                </div>
                <div class="layui-card-body">
                    <div id="group_table_box">
                        <table class="layui-table" id="group_table" lay-filter="group_table"></table>
                    </div>
                    <div id="field_table_box">
                        <table class="layui-table" id="field_table" lay-filter="field_table"></table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script id="group_table_html" type="text/html">
    <div class="layui-btn-group">
        @{{# if(d.parent_id === 0 ){ }}
            <a class="layui-btn layui-btn-xs layui-bg-blue" lay-event="create"><i class="layui-icon layui-icon-addition"></i></a>
        @{{# } }}
        @{{# if(d.parent_id !== 0 ){ }}
        <a class="layui-btn layui-btn-xs layui-bg-orange" lay-event="createField"><i class="layui-icon layui-icon-addition"></i></a>
        @{{# } }}
        <a class="layui-btn layui-btn-xs" lay-event="edit">
            <i class="layui-icon layui-icon-edit"></i>
        </a>
        @{{# if(!(d.parent_id === 0 && d.children.length !== 0) ){ }}
            <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delete">
                <i class="layui-icon layui-icon-delete"></i>
            </a>
        @{{# } }}
    </div>
</script>
<script id="field_table_html" type="text/html">
    <div class="layui-btn-group">
        <a class="layui-btn layui-btn-xs" lay-event="edit">
            <i class="layui-icon layui-icon-edit"></i>
        </a>
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delete">
            <i class="layui-icon layui-icon-delete"></i>
        </a>
    </div>
</script>

<script id="group_html" type="text/html">
    <li class="" ptadmin-event="group" data-id="@{{d.id}}" data-name="@{{d.name}}">
        @{{d.title}}
    </li>
</script>
@endsection

@section('script')
    <script>
        layui.use(['PTForm', 'PTSetting', 'treeTable', 'table', "layer","form"], function () {
            const {  common, layer, PTSetting, treeTable, table, form } = layui;
            const manage = [
                {title: '配置分组', id: 'group_table', name: 'GROUP'},
                {title: '分组字段', id: 'field_table', name: 'FIELD'},
            ]

            const API_EXTEND = {
                group_table: {
                    create: {url: "{{admin_route("setting-group")}}", title: '新增配置分组'},
                    edit: {url: "{{admin_route("setting-group")}}", title: '编辑分组'},
                    delete: {url: "{{admin_route("setting-group")}}", title: '删除分组'},
                },
                field_table: {
                    create: {url: "{{admin_route("setting")}}", title: '新增字段'},
                    edit: {url: "{{admin_route("setting")}}", title: '编辑字段'},
                    delete: {url: "{{admin_route("setting")}}", title: '删除字段'},
                }
            }
            const tableHandle = {
                current_table_type: undefined,
                instance: {},
                loadTreeTable: function (elem) {
                    treeTable.on(`tool(${elem})`, function (obj) {
                        const data = obj.data;
                        const event = obj.event;
                        if (tableHandle.events[event]) {
                            tableHandle.events[event].call(tableHandle, data)
                        }
                    })
                    return treeTable.render({
                        elem: $(`#${elem}`),
                        url: '{{admin_route("setting-groups")}}',
                        parseData: function (res) {
                            if (res.code !== 0) {
                                return false
                            }
                            return {
                                "code": res.code,
                                "msg": res.message,
                                "count": res.data['total'] || 0,
                                "data": res.data['results'] || []
                            }
                        },
                        tree: {view: {expandAllDefault: true}},
                        cols: [[
                            {field: 'id', title: 'ID', width: 60},
                            {field: 'name', title: '标识'},
                            {field: 'title', title: '标题'},
                            {field: 'intro', title: '备注'},
                            {field: 'weight', title: '排序'},
                            {fixed: "right", title: "操作", width: 120, align: "center", toolbar: "#group_table_html"}
                        ]]
                    })
                },
                loadTable: function (elem) {
                    table.on(`tool(${elem})`, function (obj) {
                        const data = obj.data;
                        const event = obj.event;
                        if (tableHandle.events[event]) {
                            tableHandle.events[event].call(tableHandle, data)
                        }
                    })
                    return table.render({
                        url: "{{admin_route("setting-page")}}",
                        elem: `#${elem}`,
                        cols: [[
                            {field: 'id', title: 'ID', width: 60},
                            {field: 'name', title: '标识'},
                            {field: 'title', title: '标题'},
                            {field: 'category', title: '所属分组', templet: function ({category}) {
                                return category !== null ? category.title || '---' : '---'
                            }},
                            {field: 'intro', title: '备注'},
                            {field: 'weight', title: '排序'},
                            {fixed: "right", title: "操作", width: 120, align: "center", toolbar: "#field_table_html"}
                        ]],
                        page: true,
                        limit: 50
                    })
                },
                init: function (type) {
                    if (type === manage[0].id) {
                        return this.loadTreeTable(type)
                    }
                    return  this.loadTable(type)
                },
                showTable: function (type) {
                    if (this.current_table_type === type) {
                        return
                    }
                    this.current_table_type = type
                    for (const item of manage) {
                        $(`#${item.id}_box`).css('display', item.id === type ? "block" : 'none')
                    }
                    if (this.instance[type] === undefined) {
                        this.instance[type] = this.init(type)
                        return;
                    }
                    this.refresh()
                },
                refresh: function () {
                    if (this.instance[this.current_table_type] === undefined) {
                        console.error('table instance is undefined')
                        return;
                    }
                    this.instance[this.current_table_type].reload()
                },
                getApi: function () {
                    return API_EXTEND[this.current_table_type]
                },
                events: {
                    submit: function (index, box) {
                        let obj = window[box.find('iframe')[0]['name']];
                        obj.form_submit().then((res) => {
                            if (res.code === 0) {
                                layer.close(index)
                                tableHandle.refresh()
                                obj = null
                                return
                            }
                            let zIndex = parseInt(box.css("z-index"))
                            layer.msg(res.message, {icon: 2, zIndex: (zIndex + 2)})
                        })
                    },
                    edit: function ({id}) {
                        common.formOpen(`${tableHandle.getApi().edit.url}/${id}`, tableHandle.getApi().edit.title, {
                            yes: tableHandle.events.submit
                        })
                    },
                    delete: function ({id}) {
                        layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                            common.del(`${tableHandle.getApi().delete.url}/${id}`, {}, function (res) {
                                if (res.code === 0) {
                                    layer.msg(res.message, {icon: 1});
                                    tableHandle.refresh()
                                } else {
                                    layer.msg(res.message, {icon: 3});
                                }
                            })
                            layer.close(index);
                        })
                    },
                    create: function (data = undefined) {
                        let url = tableHandle.getApi().create.url
                        if (data !== undefined) {
                            url = url + `?parent_id=${data.id}`
                        }
                        common.formOpen(url, tableHandle.getApi().create.title, {
                            yes: tableHandle.events.submit
                        })
                    },
                    createField: function (data = undefined) {
                        let apiExtend = API_EXTEND['field_table'];
                        let url = apiExtend.create.url
                        if (data !== undefined) {
                            url = url + `?parent_id=${data.id}`
                        }
                        common.formOpen(url, apiExtend.create.title, {
                            yes: tableHandle.events.submit
                        })
                    },
                    saveConfig: function () {
                        form.submit('save-config-data', function(data){
                            let id = $('.layui-form').data('id');
                            let field = data.field;
                            let fieldLength = Object.keys(field).length;
                            if(fieldLength === 0 || fieldLength === undefined) {
                                layer.msg("当前配置未设置字段，请先设置字段！", { icon: 2 });
                                return
                            }
                            field.ids = [id];
                            // 执行提交
                            $.ajax({
                                url: "{{admin_route("setting-val")}}",
                                type: 'post',
                                data: field,
                                dataType: "json",
                                success: function (data) {
                                    if (data.code !== 0) {
                                        layer.msg(data.message, { icon: 2 });
                                        return
                                    }
                                    layer.msg(data.message);
                                },
                            });
                        });
                    },
                }
            }

            const init = function () {
                $.ajax({
                    url: '{{admin_route("settings")}}',
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        if (res.code === 0) {
                            PTSetting.init(res.data)
                        }
                    }
                })
            }

            // 加载配置分组列表
            PTSetting.on("group_table", ({event}) => tableHandle.showTable(event))

            // 加载配置字段列表
            PTSetting.on("field_table", ({event}) => tableHandle.showTable(event))

            // 刷新
            PTSetting.on("refresh", () => tableHandle.refresh())

            // 保存配置
            PTSetting.on("saveConfig", () => tableHandle.events.saveConfig())

            // 创建
            PTSetting.on("create", () => tableHandle.events.create())

            // 创建字段
            PTSetting.on("createField", () => tableHandle.events.createField())

            // 编辑
            PTSetting.on("edit", function () {
                const id = $(this).parent().data('id')
                tableHandle.events.edit({id})
            })

            // 删除
            PTSetting.on("delete", function () {
                const id = $(this).parent().data('id')
                layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                    common.del(`{{admin_route("setting-group")}}/${id}`, {}, function (res) {
                        if (res.code === 0) {
                            layer.msg(res.message, {icon: 1});
                            setTimeout(function () {
                                location.href = "{{admin_route('settings')}}"
                            }, 1000)
                        } else {
                            layer.msg(res.message, {icon: 3});
                        }
                    });
                    layer.close(index);
                });
            })

            PTSetting.on("form-click", function ({e}) {
                let { target } = e;
                if (!target) return;
                let formItem = target.closest('.layui-form-item');
                if (!formItem) return;

                let label = formItem.querySelector('label');
                let labelField = $(label).data('field');
                let fieldKeyArr = labelField.split('.');
                PTSetting.getData(fieldKeyArr[0], fieldKeyArr[1]);
            })

            // 管理和配置界面
            PTSetting.on("setting", function () {
                const obj = $(this)
                if (obj.hasClass("layui-bg-blue")) {
                    return
                }
                obj.addClass("layui-bg-blue").siblings().removeClass("layui-bg-blue")
                PTSetting.change(obj.data('type'), manage)
            })

            init()

        });
    </script>
@endsection
