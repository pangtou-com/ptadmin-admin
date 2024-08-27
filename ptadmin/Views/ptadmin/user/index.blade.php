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
        layui.use(['PTPage'], function () {
            const { PTPage } = layui;
            PTPage.make({
                urls: {
                    create_url: "{{admin_route('user')}}",
                    edit_url: "{{admin_route('user')}}/{id}",
                    del_url: "{{admin_route('user')}}/{id}",
                },
                table: [
                    {type: 'checkbox'},
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'username', title: '{{ __("table.users.username") }}', search: true},
                    {field: 'nickname', title: '{!! __("table.users.nickname") !!}', search: {op: ["=", 'like'] }},
                    {field: 'email', title: '{!! __("table.users.email") !!}', search: { op: ["=", 'like']}},
                    {field: 'mobile', title: '{!! __("table.users.mobile") !!}', search: { placeholder: 'mobile' }},
                    {field: 'money', title: '{!! __("table.users.money") !!}', search: { type: "number" }},
                    {field: 'score', title: '{!! __("table.users.score") !!}', search: { type: "number_range" }},
                    {field: 'status', width: 120, title: '{!! __("table.users.status") !!}',
                        templet: PTPage.format.switch,
                        search: {type: 'select', options: [{label: '未启用', value: 0}, {label: '启用', value: 1}]}
                    },
                    {fixed: 'right', width: 120, title: '{{ __("system.btn_handle") }}', align: 'center', operate: true},
                ]
            })

        })
    </script>

@endsection
