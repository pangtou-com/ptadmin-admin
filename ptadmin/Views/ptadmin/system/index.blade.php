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
        layui.use(['PTPage', 'PTRender', 'form', 'table'], function () {
            let {PTPage, PTRender} = layui;
            PTPage.make({
                urls: {
                    create_url: "{{admin_route('system')}}",
                    edit_url: "{{admin_route('system')}}/{id}",
                    del_url: "{{admin_route('system')}}/{id}",
                },
                table: [
                    {field: 'id', title: 'ID', width: 80},
                    {field: 'username', title: '{{ __("table.systems.username") }}'},
                    {field: 'nickname', title: '{!! __("table.systems.nickname") !!}'},
                    {
                        field: 'is_founder', title: '{!! __("table.systems.is_founder") !!}', templet: (data) => {
                            if (data['is_founder'] === 1) {
                                return `<span class="layui-badge layui-bg-blue">是</span>`
                            }
                            return data['role_name'] || "-"
                        }
                    },
                    {field: 'mobile', title: '{!! __("table.systems.mobile") !!}'},
                    {field: 'status', width: 100, title: '{!! __("table.systems.status") !!}', templet: PTPage.format.whether},
                    {field: 'login_at', title: '{!! __("table.systems.login_at") !!}'},
                    {field: 'login_ip', title: '{!! __("table.systems.login_ip") !!}'},
                    {
                        fixed: 'right',
                        width: 120,
                        title: '{{ __("system.btn_handle") }}',
                        align: 'center',
                        operate: function (data) {
                            if (data['is_founder'] === 1) {
                                return "-"
                            }
                            let html = [
                                PTRender.render({class: "layui-bg-orange layui-btn layui-btn-xs", event: 'edit', icon: 'layui-icon layui-icon-edit',}),
                                PTRender.render({class: "layui-bg-red layui-btn layui-btn-xs", event: 'del', icon: 'layui-icon layui-icon-delete',}),
                            ]
                            return PTRender.render({
                                class: "layui-btn-group",
                                tagName: 'div',
                                text: html.join("")
                            });
                        }
                    },
                ]
            });
        })
    </script>
@endsection
