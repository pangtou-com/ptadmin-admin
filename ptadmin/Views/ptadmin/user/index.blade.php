@extends('ptadmin.layouts.base')

@section("content")

    <div class="layui-card">
        @include('ptadmin.common.index_header')
        <div class="layui-card-body">
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTTable'], function () {
            const { PTTable } = layui;
            PTTable.render({
                extend: {
                    index_url: '{{admin_route('users')}}',
                    create_url: "{{admin_route('user')}}",
                    edit_url: "{{admin_route('user')}}/{id}",
                    del_url: "{{admin_route('user')}}/{id}",
                },
                cols: [[
                    {type: 'checkbox', fixed: 'left'},
                    {field: 'id', title: 'ID', width: 80},
                    {
                        field: 'username', title: '{{ __("table.users.username") }}', search: {
                            type: 'text',
                            pl: '用户账号',
                            symbol: ["=", 'like'],
                        }
                    },
                    {
                        field: 'nickname', title: '{!! __("table.users.nickname") !!}', search: {
                            type: 'text',
                            pl: '用户昵称',
                            symbol: ["=", 'like'],
                        }
                    },
                    {
                        field: 'email', title: '{!! __("table.users.email") !!}', search: {
                            type: 'text',
                            pl: 'email',
                            symbol: ["=", 'like'],
                        }
                    },
                    {
                        field: 'mobile', title: '{!! __("table.users.mobile") !!}', search: {
                            type: 'text',
                            pl: 'mobile',
                            symbol: ["=", 'like'],
                        }
                    },
                    {field: 'money', title: '{!! __("table.users.money") !!}'},
                    {field: 'score', title: '{!! __("table.users.score") !!}'},
                    {
                        field: 'status',
                        width: 120,
                        title: '{!! __("table.users.status") !!}',
                        templet: PTTable.format.switch,
                        search: {
                            type: 'select_multiple',
                            options: [{label: '未启用', value: 0}, {label: '启用', value: 1}],
                        }
                    },
                    {
                        fixed: 'right',
                        width: 120,
                        title: '{{ __("system.btn_handle") }}',
                        align: 'center',
                        operate: ['edit', 'del', 'link']
                    },
                ]]
            });
        })
    </script>

@endsection
