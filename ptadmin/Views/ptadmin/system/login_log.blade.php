
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
        layui.use(['PTTable', 'common', 'form', 'table'], function () {
            let {PTTable, common, form, table} = layui;
            PTTable.render({
                extend: {
                    index_url: '{{admin_route('system/login')}}',
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'system.nickname', title: '{{ __("table.systems.nickname") }}'},
                    {
                        field: 'status', title: '{!! __("table.systems.status") !!}', templet: (data) => {
                            let val = common.getTableColValue(data);
                            if (val === 0) {
                                return `<span class="layui-badge layui-bg-red">失败</span>`
                            }
                            return `<span class="layui-badge layui-bg-green">成功</span>`
                        }
                    },
                    {field: 'login_at', title: '{!! __("table.systems.login_at") !!}'},
                    {field: 'login_ip', title: '{!! __("table.systems.login_ip") !!}'},
                ]]
            });
            form.on('submit(table-search)', function (data) {
                const field = data.field;
                table.reload('dataTable', {
                    page: {
                        curr: 1
                    },
                    where: field
                });
                return false;
            });

        })
    </script>
@endsection


