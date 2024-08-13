@extends('ptadmin.layouts.base')
@section("content")
    <div class="layui-card">
        <div class="layui-card-header ptadmin-card-header">
            <div class="ptadmin-card-header-left">
                <div class="layui-btn-group">
                    <button class="layui-btn layui-btn-sm  layui-bg-blue" ptadmin-event="create">
                        <i class="layui-icon layui-icon-addition"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm" ptadmin-event="reload">
                        <i class="layui-icon layui-icon-refresh"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="layui-card-body">
            <!--搜索区域后面构建-->
            <table id="dataTable" lay-filter="dataTable"></table>
            <script type="text/html" id="options">
                <div class="layui-btn-group">
                    @{{# if(d.lv !=2 ){ }}
                    <a class="layui-btn layui-btn-sm layui-bg-blue" lay-event="add">
                        <i class="layui-icon layui-icon-addition"></i></a>
                    @{{# } }}
                    <a class="layui-btn layui-btn-sm" lay-event="edit">
                        <i class="layui-icon layui-icon-edit"></i>
                    </a>
                    <a class="layui-btn layui-btn-sm layui-btn-danger" lay-event="del">
                        <i class="layui-icon layui-icon-delete"></i>
                    </a>
                </div>
            </script>
        </div>
    </div>
@endsection

@section("script")
    <script>
        const menu_text = @json(\PTAdmin\Admin\Enum\MenuTypeEnum::getMaps());
        layui.use(['layer', 'treeTable', 'PTTable', 'common', 'form'], function () {
            const {treeTable, layer, PTTable, common, form} = layui
            const inst = treeTable.render({
                elem: '#dataTable',
                url: '{{admin_route('permissions')}}',
                parseData: function (res) {
                    return {
                        "code": res.code,
                        "msg": res.message,
                        "count": res.data['total'] || 0,
                        "data": res.data['results'] || []
                    };
                },
                tree: {
                    customName: {
                        icon: 'icon_show',
                    },
                    view: {
                        showIcon: false
                    }
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 60, fixed: 'left'},
                    {field: 'name', width: 200, title: '{{ __("table.permissions.name") }}', fixed: 'left'},
                    {field: 'icon', width: 70, title: 'ICON', fixed: 'left', templet: PTTable.format.icon},
                    {field: 'title', title: '{{ __('table.permissions.title') }}'},
                    {
                        field: 'type', title: '{{ __('table.permissions.type') }}', templet: function (data) {
                            let val = common.getTableColValue(data);

                            return `<span class="layui-badge">${menu_text[val]}</span>`
                        }, width: 100
                    },
                    {field: 'route', title: '{{ __('table.permissions.route') }}'},
                    {field: 'note', title: '{{ __('table.permissions.note') }}'},
                    {
                        field: 'status',
                        title: '{{ __('table.permissions.status') }}',
                        width: 120,
                        sort: true,
                        templet: PTTable.format.switch
                    },
                    {fixed: "right", title: "操作", width: 200, align: "center", toolbar: "#options"}
                ]],
                page: false
            });

            const events = {
                create: function () {
                    common.formOpen("{{admin_route('permission')}}", "新增权限")
                },
                add: function (data) {
                    common.formOpen(`{{admin_route('permission')}}?parent_id=${data.id}`, "新增权限")
                },
                edit: function (data) {
                    common.formOpen("{{admin_route('permission')}}/" + data.id, "编辑权限")
                },
                del: function (data) {
                    layer.confirm('确定删除吗？', function (index) {
                        $.ajax({
                            url: "{{admin_route('permission')}}/" + data.id,
                            type: 'delete',
                            dataType: 'json',
                            success: function (response) {
                                if (response.code === 0) {
                                    layer.msg('删除成功', {icon: 1});
                                    inst.reload()
                                } else {
                                    layer.msg(response.message, {icon: 2});
                                }
                            }
                        });
                        layer.close(index);
                    });
                },
            }

            treeTable.on("tool(dataTable)", function (obj) {
                const data = obj.data;
                const event = obj.event;
                if (events[event]) {
                    events[event].call(this, data)
                    return
                }
                console.error(`未定义事件: 【${event}】`)
            })

            form.on('switch(ptadmin-switch)', function (data) {
                const value = data.elem.value
                let param = {
                    field: $(data.elem).attr('data-name'),
                    value: data.elem.checked === true ? 1 : 0,
                    is_edit: 1
                }
                common.put(`{{admin_route('permission')}}-field/${value}`, param)
            })

            $('body').on('click', '*[ptadmin-event]', function () {
                const event = $(this).attr('ptadmin-event')
                if (events[event]) {
                    events[event].call(this)
                    return
                }
                console.error(`未定义事件: 【${event}】`)
            })
        });
    </script>
@endsection

@section("head")
    <style>
        .layui-table-tree-iconCustom i {
            margin-right: 5px;
        }
    </style>
@endsection
