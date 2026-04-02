@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <x-hint>
            <div><strong>安全服务</strong></div>
        </x-hint>
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <script type="text/html" id="options">
                    <div class="layui-btn-group">
                        <button class="layui-btn layui-btn-xs layui-bg-blue" lay-event="edit">
                            {{__("system.btn_edit")}}
                        </button>
                        <button class="layui-btn layui-btn-xs layui-bg-red" lay-event="del">
                            删除
                        </button>
                    </div>
                </script>
                <script type="text/html" id="expand">
                    <div class="ptadmin-page-expand-image-box">
                        @{{# if(d.cover !== ""){ }}
                        <i class="layui-icon-picture layui-icon image" data-url="@{{d.cover}}"></i>
                        @{{# } }}
                        <div class="content">@{{= d.title }}  <a href="/safety/@{{= d.id }}.html" target="_blank">【查看】</a></div>
                    </div>
                </script>
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['common', 'layer', 'dropdown', 'PTPage', 'table'], function () {
            const {common, layer, dropdown, table, PTPage, form, element} = layui;
            const events = {
                del: function ({data}) {
                    handle('确认要删除此模型吗?', `{{admin_route('cms/model')}}/${data.id}`, 'delete')
                }
            }
            const page = PTPage.make({
                urls: {
                    index_url: "{{admin_route('cms/safeties')}}",
                    create_url: "{{admin_route('cms/safety')}}",
                    edit_url: "{{admin_route('cms/safety')}}/{id}",
                    del_url: "{{admin_route('cms/safety')}}/{id}",
                    status_url: "{{admin_route('cms/safety-status')}}/{id}",
                    title: {create: '添加安全服务', edit: '编辑安全服务'}
                },
                btn_left:[{event: 'create', theme: 'info', text: '添加'}],
                search: false,
                table: {
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '标题', templet: "#expand"},
                        {field: 'type_text', title: '类型', width: 150},
                        {field: 'weight', title: '排序', width: 80},
                        {field: 'status', title: '有效状态', width: 90, templet: PTPage.format.switch},
                        {fixed: 'right', width: 120, title: '{{__("system.btn_handle")}}', align: 'center', templet: '#options'},
                    ]]
                }
            })

            page.on("create", function () {
                common.open(`{{admin_route("cms/safety")}}`, `添加安全服务`)
            })

            page.on("edit", function ({data}) {
                common.open(`{{admin_route("cms/safety")}}/${data.id}`, `编辑安全服务`)
            })

            const handle = (title, url, method = 'put') => {
                layer.confirm(title, {icon: 3, title: 'Warning'}, function (index) {
                    common.post(url, {}, method, function (res) {
                        if (res.code === 0) {
                            page.reload();
                        } else {
                            layer.msg(res.message, {icon: 3});
                        }
                    });
                    layer.close(index);
                });
            }

            // 弹出层完成后的回调处理
            window['__callback__'] = () => {
                layer.closeAll()
                page.reload();
            }
        });
    </script>
@endsection
