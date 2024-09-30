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
                    <div id="setting-container"></div>
                    <div class="container-footer layui-btn-group" >
                        <button class="layui-btn layui-bg-blue" lay-submit lay-filter="config">保存</button>
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
<script id="group_table_html" type="text/html"></script>
<script id="field_table_html" type="text/html"></script>
<dialog id="dialog">
    <div class="dialog-header">
        <span>Iframe Content</span>
        <button class="close-btn" id="closeDialogBtn">&times;</button>
    </div>
    <div class="dialog-body">
        <iframe src="{{admin_route("setting-group")}}" frameborder="0"></iframe>
    </div>

</dialog>

<script id="group_html" type="text/html">
    <li class="" ptadmin-event="group" data-id="@{{d.id}}" data-name="@{{d.name}}">
        @{{d.title}}
    </li>
</script>
@endsection

@section('script')
    <script>
        layui.use(['PTForm', 'PTSetting', 'treeTable', 'table'], function () {
            const {  common, layer, PTSetting, treeTable, table } = layui;
            const manage = [
                {title: '配置分组', id: 'group_table', name: 'GROUP'},
                {title: '分组字段', id: 'field_table', name: 'FIELD'},
            ]

            const events = {
                edit: function (id) {
                    common.formOpen(`{{admin_route("setting-group")}}/${id}`, '编辑分组')
                },
                delete: function (id) {
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
                },
                'field-create': function (id) {
                    common.formOpen(`{{admin_route("settings")}}?category_id=${id}`, '新增配置字段')
                },
                'field-del': function (id) {
                    layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                        common.del(`{{admin_route("setting")}}/${id}`, {}, function (res) {
                            if (res.code === 0) {
                                layer.msg(res.message, {icon: 1});
                                setTimeout(function () {
                                    location.reload()
                                }, 1000)
                            } else {
                                layer.msg(res.message, {icon: 3});
                            }
                        });
                        layer.close(index);
                    });
                },
                'field-edit': function (id) {
                    common.formOpen(`{{admin_route("setting")}}/${id}`, '编辑配置字段')
                },
            }

            const API_EXTEND = {
                group_table: {
                    create: {url: "{{admin_route("setting-group")}}", title: '新增配置分组'}
                },
                field_table: {
                    create: {url: "{{admin_route("setting")}}", title: '新增字段'}
                }
            }
            const tableHandle = {
                current_table_type: undefined,
                instance: {},
                loadTreeTable: function (elem) {
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
                            {field: 'options', title:'操作'},
                        ]]
                    })
                },
                loadTable: function (elem) {
                    return table.render({
                        url: "{{admin_route("setting-page")}}",
                        elem: `#${elem}`,
                        cols: [[
                            {field: 'id', title: 'ID', width: 60},
                            {field: 'name', title: '标识'},
                            {field: 'title', title: '标题'},
                            {field: 'category.title', title: '所属分组'},
                            {field: 'intro', title: '备注'},
                            {field: 'weight', title: '排序'},
                            {field: 'options', title:'操作'},
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
                    this.instance[this.current_table_type].reloadData()
                },
                create: function () {
                    common.formOpen(this.getApi().create.url, this.getApi().create.title, {
                        yes: function () {
                            console.log("操作成功")

                        }
                    })
                    window.addEventListener("message", function () {

                    })
                },
                getApi: function () {
                    return API_EXTEND[this.current_table_type]
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

            // 创建
            PTSetting.on("create", () => tableHandle.create())

            // 编辑
            PTSetting.on("edit", function () {
                const id = $(this).parent().data('id')
                common.formOpen(`{{admin_route("setting-group")}}/${id}`, '编辑分组')
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
