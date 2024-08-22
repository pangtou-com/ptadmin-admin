@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-card ptadmin-page-container">
        <div class="layui-card-body">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTPage', 'common', 'form', 'table'], function () {
            const {PTPage, common, form, table} = layui;
            PTPage.make({
                urls: {index_url: "{{admin_route('operations')}}", show_url: '{{admin_route('operations')}}/{id}'},
                btn_left: ['refresh', 'del'],
                table: [
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'title', title: '名称', width: 100},
                    {field: 'url', title: '请求路径'},
                    {field: 'method', title: '请求方式', width: 100},
                    {field: 'controller', title: '控制器'},
                    {field: 'action', title: '方法', width: 100},
                    {
                        field: 'response_code', title: '状态码', width: 100, templet: function (data) {
                            const span = {200: 'layui-badge layui-bg-blue', 500: 'layui-badge'};
                            let val = common.getTableColValue(data);
                            let className = span[val] || "layui-badge";
                            return `<span class="${className}">${val}</span>`
                        }
                    },
                    {
                        field: 'response_time', title: '响应耗时(ms)', width: 120, templet: function (data) {
                            let val = common.getTableColValue(data);
                            if (val > 500) {
                                return `<span class="layui-badge">${val}</span>`
                            }
                            return val;
                        }
                    },
                    {field: 'nickname', title: '操作人', width: 100},
                    {
                        fixed: 'right',
                        width: 120,
                        title: '{{__("system.btn_handle")}}',
                        align: 'center',
                        operate: ['show']
                    },
                ],
            });
        })
    </script>
@endsection
