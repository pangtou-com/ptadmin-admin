{{--操作日志--}}
@extends('ptadmin.layouts.base')

@section("content")

    <div class="layui-card">
        <div class="layui-card-body">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTTable'], function () {
            let {PTTable} = layui;
            PTTable.render({
                extend: {
                    index_url: '{{admin_route('systems')}}',
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'title', title: '名称', width: 100},
                    {field: 'url', title: '请求路径'},
                    {field: 'method', title: '请求方式', width: 100},
                    {field: 'controller', title: '控制器'},
                    {field: 'action', title: '方法', width: 100},
                    {
                        field: 'response_code', title: '状态码', width: 100, templet: function (data) {
                            const span = {200: 'layui-badge layui-bg-blue', 500: 'layui-badge'};
                            let val = PTTable.getTableColValue(data);
                            let className = span[val] || "layui-badge";
                            return `<span class="${className}">${val}</span>`
                        }
                    },
                    {
                        field: 'response_time', title: '响应耗时(ms)', width: 120, templet: function (data) {
                            let val = PTTable.getTableColValue(data);
                            if (val > 500) {
                                return `<span class="layui-badge">${val}</span>`
                            }
                            return val;
                        }
                    },
                    {field: 'manage_name', title: '操作人', width: 100},
                    {
                        fixed: 'right',
                        width: 120,
                        title: '{{__("system.btn_handle")}}',
                        align: 'center',
                        operate: ['show']
                    },
                ]]
            });
        })
    </script>
@endsection
