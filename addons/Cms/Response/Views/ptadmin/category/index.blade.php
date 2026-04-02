@extends('ptadmin.layouts.base')
@section("content")
    <div class="layui-card">
        <div class="layui-card-header ptadmin-card-header">
            <div class="ptadmin-card-header-left">
                <div class="layui-btn-group">
                    <button class="layui-btn layui-btn-sm " lay-submit lay-filter="create">
                        <i class="layui-icon layui-icon-addition"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm " lay-submit lay-filter="reload">
                        <i class="layui-icon layui-icon-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="layui-card-body ptadmin-temps-category-box">
            <table id="dataTable" lay-filter="dataTable"></table>
            <script type="text/html" id="options">
                <div class="layui-btn-group">
                    <a class="layui-btn layui-btn-xs layui-bg-blue" lay-event="add">
                        <i class="layui-icon layui-icon-addition"></i>
                    </a>
                    <a class="layui-btn layui-btn-xs" lay-event="edit">
                        <i class="layui-icon layui-icon-edit"></i>
                    </a>
                    <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="del">
                        <i class="layui-icon layui-icon-delete"></i>
                    </a>
                </div>
            </script>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['layer', 'treeTable', 'common', 'form'], function () {
            const { treeTable, layer, common, form } = layui

            const inst = treeTable.render({
                elem: '#dataTable',
                extend: {
                    index_url: '{{admin_route('cms/categories')}}',
                    status_url: "{{admin_route('cms/category-status')}}/{id}",
                },
                url: '{{admin_route('cms/categories')}}',
                maxHeight: window.outerHeight - 100,
                parseData: function (res) {
                    return {
                        "code": res.code,
                        "msg": res.message,
                        "count": res.data['total'] || 0,
                        "data": res.data['results'] || []
                    };
                },
                tree: {
                    customName: {icon: 'icon_show', name: 'title'},
                    view: {showIcon: false}
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 60},
                    {field: 'title', title: '标题'},
                    {field: 'mod.title', title: '扩展模型', width: 130, templet: function (data) {
                            return common.getTableColValue(data);
                        }},
                    {field: 'dir_name', title: '目录名称', width: 100},
                    {field: 'cover', title: '封面图', width: 150, align: 'center', templet: function (data) {
                            let val = common.getTableColValue(data);
                            if ('' !== val) {
                                return `<div class="ptadmin-temps-category-image"><img src="${val}" alt=""/></div>`;
                            }
                            return "-";
                        }
                    },
                    {field: 'document_num', title: '文档数量', width: 100},
                    {
                        field: 'is_single', title: '是否单页', width: 100, templet: function (data) {
                            let str = '';
                            if (data.is_single === 1) {
                                str += '<span class="layui-badge layui-bg-blue">是</span>'
                            } else {
                                str += '<span class="layui-badge">否</span>'
                            }
                            return str;
                        }
                    },
                    {
                        field: 'status', title: '状态', width: 70, templet: function (data) {
                            let str = '';
                            if (data.status === 1) {
                                str += '<span class="layui-badge layui-bg-blue">启用</span>'
                            } else {
                                str += '<span class="layui-badge">禁用</span>'
                            }
                            return str;
                        }
                    },
                    {fixed: 'right', title: '{{ __("system.btn_handle") }}', width: 120, toolbar: "#options"},
                ]],
                done:function(){
                    console.log('执行完成');
                },
                lineStyle: 'height: 80px;',
                page: false
            });

            form.on('submit(create)', function (data) {
                common.formOpen(`{{admin_route('cms/category')}}`, '添加分类')
            });

            form.on('submit(reload)', function (data) {
                inst.reload()
            });
            const events = {
                add: function (data) {
                    common.formOpen(`{{admin_route('cms/category')}}/?parent_id=${data.id}`, "添加分类")
                },
                edit: function (data) {
                    common.formOpen("{{admin_route('cms/category')}}/" + data.id + `?parent_id=${data.parent_id}`, "编辑分类")
                },
                del: function (data) {
                    layer.confirm('确定删除吗？', function (index) {
                        $.ajax({
                            url: "{{admin_route('cms/category')}}/" + data.id,
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
                }
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


            form.on('submit(table-search)', function (data) {
                treeTable.reload('dataTable', {
                    where: data.field
                });
                return false;
            });

            form.on('switch(ptadmin-switch)', function (data) {
                const value = data.elem.value
                let param = {
                    field: $(data.elem).attr('data-name'),
                    value: data.elem.checked === true ? 1 : 0,
                    is_edit: 1
                }
                common.put(`{{admin_route('cms/category')}}-status/${value}`, param)
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
