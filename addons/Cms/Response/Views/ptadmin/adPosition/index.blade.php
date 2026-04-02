@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
            <script type="text/html" id="options">
                <div class="layui-btn-group">
                    <a class="layui-btn layui-btn-sm layui-bg-blue" lay-event="ads">广告管理</a>
                    <a class="layui-btn layui-btn-sm btn-theme" lay-event="edit"> <i class="layui-icon layui-icon-edit"></i></a>
                    <a class="layui-btn layui-btn-sm layui-bg-red" lay-event="del"> <i class="layui-icon layui-icon-delete"></i></a>
                </div>
            </script>
        </div>
    </div>

@endsection

@section("script")
<script>
    layui.use(['PTPage'], function () {
        let {PTPage} = layui;
        const page = PTPage.make({
            urls: {
                index_url: "{{admin_route('cms/ad-positions')}}",
                create_url: "{{admin_route('cms/ad-position')}}",
                edit_url: "{{admin_route('cms/ad-position')}}/{id}",
                del_url: "{{admin_route('cms/ad-position')}}/{id}",
                status_url: "{{admin_route('cms/ad-position-status')}}/{id}",
                title: {
                    create: '新增广告位',
                    edit: '编辑广告位'
                }
            },
            btn_left: ['create','refresh'],
            table: {
                cols: [[
                    {field: 'id', title: 'ID',width:80},
                    {field: 'title', title: '广告位置标题'},
                    {field: 'type_name', title: '广告类型'},
                    {field: 'intro', title: '描述内容'},
                    {field: 'status',title: '状态',templet: PTPage.format.switch},
                    {fixed: 'right', width: 200, title:'{{__("system.btn_handle")}}', align:'center', operate: [{
                            class: "layui-btn layui-btn-xs layui-bg-blue",
                            event: 'ads',
                            icon: 'layui-icon layui-icon-set',
                        }, 'edit', 'del']},
                ]]
            }
        });
        page.on("ads",function (obj){
            let data = obj.data;
            let url = "{{admin_route('cms/ads')}}/" + data.id
            const text = `广告管理[${data.title}]`
            const root = parent === self ? layui : top.layui;
            root.main.openTab(text, url)
        });
    });
</script>
@endsection
