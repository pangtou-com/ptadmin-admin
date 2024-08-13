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
    layui.use(['PTTable', 'common'], function () {
        const { PTTable, common } = layui;
        PTTable.render({
            extend: {
                index_url: '{{admin_route('roles')}}',
                create_url: "{{admin_route('role')}}",
                edit_url: "{{admin_route('role')}}/{id}",
                del_url: "{{admin_route('role')}}/{id}",
            },
            cols: [[
                {field: 'id', title: 'ID', width: 80},
                {field: 'name', title: '{{ __("table.roles.name") }}'},
                {field: 'title', title: '{!! __("table.roles.title") !!}'},
                {field: 'note', title: '{!! __("table.roles.note") !!}'},
                {field: 'status', title: '{!! __("table.roles.status") !!}', templet: PTTable.format.switch},
                {
                    fixed: 'right', width: 160, title: '{{ __("system.btn_handle") }}', align: 'center',
                    operate: [{
                        class: "layui-btn layui-btn-sm layui-bg-blue",
                        event: 'set',
                        icon: 'layui-icon layui-icon-set'
                    }, 'edit', 'del']
                },
            ]]
        });

        PTTable.on("set", function ({data}) {
            common.formOpen(`{{admin_route('roles-permission')}}/${data.id}`, '设置权限')
        })
    })
</script>
@endsection
