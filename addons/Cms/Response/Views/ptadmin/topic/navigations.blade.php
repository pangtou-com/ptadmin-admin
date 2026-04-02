@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-topic-container layui-card">
        <div class="layui-panel">
            <x-hint>
                <div><strong>专题导航</strong></div>
            </x-hint>
            <div class="layui-card-header ptadmin-card-header">
                    <div class="layui-btn-group">
                        <button type="button" class="layui-btn layui-btn-sm layui-bg-blue" lay-submit lay-filter="create">
                            <i class="layui-icon layui-icon-addition"></i>
                        </button>
                        <button type="button" class="layui-btn layui-btn-sm"  lay-submit lay-filter="reload">
                            <i class="layui-icon layui-icon-refresh"></i>
                        </button>
                    </div>
                    <a href="#" class="layui-btn layui-btn-sm layui-bg-blue" lay-submit lay-filter="back">返回列表</a>
            </div>

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
                    </div>
                </script>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm','form', 'PTPage', 'common'], function () {

            const { PTForm, form, common, PTPage } = layui
            PTForm.init();

            PTPage.make({
                // event: events,
                urls: {
                    index_url: "",
                    create_url: "",
                    edit_url: "{{admin_route('cms/topic/navForm')}}/"+`{{$id}}`+"/{id}",
                    del_url: "{{admin_route('cms/topic/navForm')}}/{id}",
                    status_url: "{{admin_route('cms/topic/status')}}/navigation/{id}",
                },
                search: false,
                table: {
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '导航标题'},
                        {field: 'subtitle', title: '导航副标题'},
                        {field: 'remark', title: '备注信息'},
                        {field: 'weight', title: '排序', width: 80},
                        {field: 'navigation_type', title: '类型', templet: function (data) {
                                return data.navigation_type === 1 ? 'url' : '已有栏目';
                            }
                        },
                        {field: 'status', title: '有效状态', width: 90, templet: PTPage.format.switch},
                        {fixed: 'right', width: 200, title: '操作', align: 'center', templet: '#options'},
                    ]],
                    done: function (res) { }
                }
            })

            form.on('submit(create)', function (data) {
                common.formOpen(`{{admin_route('cms/topic/navForm')}}`+'/'+`{{$id}}`, '添加导航')
            });

            form.on('submit(reload)', function () {
                location.reload();
            });

            form.on('submit(back)', function (data) {
                location.href = '{{admin_route('cms/topics')}}'
            });
        })
    </script>
@endsection
