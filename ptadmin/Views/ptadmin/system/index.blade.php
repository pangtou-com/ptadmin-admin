@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-card">
        @include('ptadmin.common.index_header')
        <div class="layui-card-body">
            <!--搜索区域后面构建-->
            <table id="dataTable" lay-filter="dataTable"></table>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTTable', 'PTRender', 'form', 'table'], function () {
            let {PTTable, PTRender, form, table} = layui;
            PTTable.render({
                extend: {
                    index_url: '{{admin_route('systems')}}',
                    create_url: "{{admin_route('system')}}",
                    edit_url: "{{admin_route('system')}}/{id}",
                    del_url: "{{admin_route('system')}}/{id}",
                },
                cols: [[
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
                    {field: 'status', width: 100, title: '{!! __("table.systems.status") !!}', templet: PTTable.format.whether},
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
                            let html = [];
                            html.push(PTRender.render({}, 'edit'))
                            html.push(PTRender.render({}, 'del'))
                            return PTRender.render({
                                class: "layui-btn-group",
                                name: 'div',
                                content: html.join("")
                            });
                        }
                    },
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
