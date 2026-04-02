@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <x-hint>
            <div><strong>内容模型【{{$mod->title}}】</strong></div>
            设置内容模型字段内容
        </x-hint>
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <script type="text/html" id="options">
                    <div class="layui-btn-group">
                        @{{# if(d.deleted_at != null){ }}
                            <a class="layui-btn layui-btn-sm" lay-event="restore">{{__("system.btn_restore")}}</a>
                            <a class="layui-btn layui-btn-sm layui-btn-danger"
                               lay-event="thorough_del">{{__("system.btn_thorough_del")}}</a>
                        @{{# }else{ }}
                            <a class="layui-btn layui-btn-sm" lay-event="edit">{{__("system.btn_edit")}}</a>
                            @{{# if (!d.is_sys_field){ }}
                                <a class="layui-btn layui-btn-sm layui-btn-danger" lay-event="del">{{__("system.btn_del")}}</a>
                            @{{# } }}
                        @{{# } }}
                    </div>
                </script>
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use([ 'common','PTPage'], function () {
            const { PTPage, common } = layui;

            const page = PTPage.make({
                urls: {
                    index_url: "{{admin_route("cms/models/field/${id}")}}",
                    create_url: "{{admin_route('cms/model/field')}}?mod_id={{$id}}",
                    edit_url: "{{admin_route('cms/model/field')}}/{id}",
                    del_url: "{{admin_route('cms/model/field')}}/{id}",
                    status_url: "{{admin_route('cms/model/field-status')}}/{id}",
                    title: {create: '添加字段', edit: '编辑字段'}
                },
                btn_left:[
                    {event: 'create', theme: 'info', text: '添加'},
                    {event: 'preview',theme: 'warn',text: '预览'},
                    {event: 'recycle',theme: 'danger', text: '回收站'}
                ],
                btn_right: [{event: 'return',theme: 'info',text: '返回模型'}],
                search: false,
                table: {
                    cols: [[
                        {type: 'numbers', width: 40},
                        {field: 'title', title: '字段标题'},
                        {field: 'name', title: '字段名称'},
                        {field: 'type', title: '字段类型', width: 120},
                        {field: 'default_val', title: '默认值', width: 80},
                        {field: 'is_release', title: '是否投稿', width: 90, templet: PTPage.format.whether},
                        {field: 'is_search', title: '是否搜索', width: 90, templet: PTPage.format.whether},
                        {field: 'is_table', title: '列表展示', width: 90, templet: PTPage.format.whether},
                        {field: 'is_required', title: '是否必填', width: 90, templet: PTPage.format.whether},
                        {field: 'weight', title: '权重', width: 80},
                        {field: 'status', title: '状态', width: 90, templet: PTPage.format.switch},
                        {fixed: 'right', width: 160, title: '{{__("system.btn_handle")}}', align: 'center', templet: '#options'},
                    ]]
                }
            });
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

            // 回收站
            page.on('recycle', function (obj) {
                obj = $(obj.target)
                const val = obj.attr('ptadmin-event-val')
                if (val === 'lists') {
                    obj.attr('ptadmin-event-val', '')
                    obj.html('回收站')
                    page.reload({'recycle': 0}, 1)
                } else {
                    obj.attr('ptadmin-event-val', 'lists')
                    obj.html('返回列表')
                    page.reload({'recycle': 1}, 1)
                }
            });

            // 返回模型
            page.on('return', function () {
                window.location.href = "{{admin_route('cms/models')}}";
            });
            // 恢复
            page.on('restore',function (obj){
                let data = obj.data;
                let title = '确认要恢复此项目吗?';
                let url = `{{admin_route('cms/model/field-restore')}}/${data.id}?mod_id={{$id}}`;
                {{--handle('确认要恢复此项目吗?', `{{admin_route('cms/model/field-restore')}}/${data.id}?mod_id={{$id}}`);--}}
                layer.confirm(title, {icon: 3, title: 'Warning'}, function (index) {
                    common.post(url, {}, 'put', function (res) {
                        if (res.code === 0) {
                            window.location.href = `{{admin_route('cms/models/field')}}/{{$id}}`;
                            // page.reload();
                        } else {
                            layer.msg(res.message, {icon: 3});
                        }
                    });
                    layer.close(index);
                });
            });
            // 彻底删除
            page.on('thorough_del',function (obj){
                let data = obj.data;
                let title = '确认要彻底删除此项目吗?';
                let url = `{{admin_route('cms/model/field-thorough')}}/${data.id}?mod_id={{$id}}`;
                {{--handle('确认要彻底删除此项目吗?', `{{admin_route('cms/model/field-thorough')}}/${data.id}`, 'delete')--}}
                layer.confirm(title, {icon: 3, title: 'Warning'}, function (index) {
                    common.post(url, {}, 'delete', function (res) {
                        if (res.code === 0) {
                            window.location.href = `{{admin_route('cms/models/field')}}/{{$id}}`;
                            // page.reload();
                        } else {
                            layer.msg(res.message, {icon: 3});
                        }
                    });
                    layer.close(index);
                });
            });
            page.on("preview", function () {
                const index = common.formOpen(`{{admin_route('cms/model-preview')}}/{{$id}}`, '{{__("system.btn_preview")}}', {
                    yes: () => {
                        layer.close(index)
                    }
                })
            })
        });
    </script>
@endsection
