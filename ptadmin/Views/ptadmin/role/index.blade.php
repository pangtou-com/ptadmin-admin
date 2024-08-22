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
        const { PTPage, common } = layui;
        const page = PTPage.make({
            urls: {
                create_url: "{{admin_route('role')}}",
                edit_url: "{{admin_route('role')}}/{id}",
                del_url: "{{admin_route('role')}}/{id}",
            },
            table: [
                {field: 'id', title: 'ID', width: 80},
                {field: 'name', title: '{{ __("table.roles.name") }}'},
                {field: 'title', title: '{!! __("table.roles.title") !!}'},
                {field: 'note', title: '{!! __("table.roles.note") !!}'},
                {field: 'status', title: '{!! __("table.roles.status") !!}', templet: PTPage.format.switch},
                {
                    fixed: 'right', width: 160, title: '{{ __("system.btn_handle") }}', align: 'center',
                    operate: [{
                        class: "layui-btn layui-btn-xs layui-bg-blue",
                        event: 'set',
                        icon: 'layui-icon layui-icon-set'
                    }, 'edit', 'del']
                },
            ],
        });

        page.on("set", function ({data}) {
            console.log("123")
            common.formOpen(`{{admin_route('roles-permission')}}/${data.id}`, '设置权限')
        })
    })
</script>
@endsection
