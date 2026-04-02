@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
<script>
    layui.use(['PTPage'], function () {
        const {PTPage} = layui;
        PTPage.make({
            urls: {
                index_url: "{{admin_route("cms/links")}}",
                create_url: "{{admin_route("cms/link")}}",
                edit_url: "{{admin_route("cms/link")}}/{id}",
                del_url: "{{admin_route("cms/link")}}/{id}",
                status_url: "{{admin_route("cms/link-status")}}/{id}",
                title: {
                    create: '新增链接',
                    edit: '编辑链接'
                }
            },
            btn_left: ['create','refresh'],
            table: {
                cols: [[
                    {field: 'id', title: 'ID', width:80},
                    {field: 'title', title: '网站名称', minWidth:300, search:'like'},
                    {field: 'url', title: '链接地址', minWidth:300, templet: PTPage.format.url},
                    {field: 'weight', title: '排序', width:80},
                    {field: 'type_text', title: '类型', width:100,search:{type: 'select', options: @json(\Addon\Cms\Enum\TypeEnum::getMapToOptions())}},
                    {field: 'status', title: '状态', width:100, align: 'center', templet: PTPage.format.switch},
                    {fixed: 'right', width: 120, title:'{{__("system.btn_handle")}}', align:'center', operate: ['edit', 'del']},
                ]]
            }
        });

    });

</script>
@endsection
