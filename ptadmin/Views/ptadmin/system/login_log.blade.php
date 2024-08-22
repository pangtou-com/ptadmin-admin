
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
        layui.use(['PTPage', 'common'], function () {
            let {PTPage, common} = layui;
            PTPage.make({
                urls: {
                    index_url: '{{admin_route('system/login')}}',
                },
                btn_left: ['refresh', 'del'],
                table: [
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
                ]
            });

        })
    </script>
@endsection


