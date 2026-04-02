@extends('ptadmin.layouts.base')
@section("content")
    <div class="layui-card ptadmin-page-container">
        <div class="layui-card-body ptadmin-temps-category-box">
            <table id="dataTable" lay-filter="dataTable"></table>
            <script type="text/html" id="options">
                <div class="layui-btn-group">
                    <a class="layui-btn layui-btn-xs" lay-event="edit">
                        <i class="layui-icon layui-icon-edit"></i>
                    </a>
                    <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="del">
                        <i class="layui-icon layui-icon-delete"></i>
                    </a>
                    <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="preview">
                        <i class="layui-icon layui-icon-eye"></i>
                    </a>
                    <a class="layui-btn layui-btn-xs" style="background-color: #ffb800" lay-event="association">
                        <i class="layui-icon layui-icon-link"></i>
                    </a>
                </div>
            </script>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['layer', 'PTPage', 'common', 'form', 'table'], function () {

            const {layer, PTPage, common, form, table} = layui
            const page = PTPage.make({
                urls: {
                    index_url: "{{admin_route('cms/tags')}}",
                    create_url: "{{admin_route('cms/tag')}}",
                    edit_url: "{{admin_route('cms/tag')}}/{id}",
                    del_url: "{{admin_route('cms/tag')}}/{id}",
                    status_url: "{{admin_route('cms/tag-status')}}/{id}",
                    title: {create: '添加模型', edit: '编辑模型'}
                },
                btn_left: ['create','refresh'],
                table: {
                    cols: [[
                        {type: 'checkbox'},
                        {field: 'id', title: 'ID', width: 70, align: "center"},
                        {field: 'title', title: '标签', minWidth: 400},
                        {field: 'views', title: '点击次数', align: "center", width: 100},
                        {field: 'has_archive_num', title: '关联数量', align: "center", width: 100},
                        {field: 'weight', title: '权重', align: "center", width: 100},
                        {field: 'status', title: '状态', width: 100, align: "center", templet: PTPage.format.switch},
                        {
                            fixed: 'right',
                            title: '{{ __("system.btn_handle") }}',
                            width: 200,
                            align: 'center',
                            toolbar: "#options"
                        },
                    ]],
                }
            });

            page.on('preview', function (){
                console.log('预览');
            });

            page.on('association', function (obj){
                let data = obj.data;
                common.open(`{{admin_route('cms/tag-archive-list')}}?id=`+data.id+'&checked=0');
            });

            // 触发排序事件
            table.on('sort(dataTable)', function (obj) {
                table.reload('dataTable', {
                    initSort: obj,
                    where: {
                        field: obj.field, // 排序字段
                        order: obj.type // 排序方式
                    }
                });
            });
        });
    </script>
@endsection

@section("head")
    <style>
        .layui-table-tree-iconCustom i {
            margin-right: 5px;
        }
    </style>
@endsection
