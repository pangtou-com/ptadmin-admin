@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <div class="layui-card">
            <div class="layui-card-header admin-card-header">
                <div class="layui-btn-group">
                    <button class="layui-btn layui-btn-sm btn-theme" lay-submit
                            lay-filter="add-addon">{{__("system.btn_create")}}</button>
                </div>
            </div>
            <div class="layui-card-body">
                <form class="layui-form layui-row layui-col-space16">
                    <div class="layui-col-md2">
                        <div class="layui-input-wrap">
                            <input type="text" name="title" value=""
                                   placeholder="{!! L(\App\Models\Addon::class, "title") !!}" class="layui-input"
                                   lay-affix="clear">
                        </div>
                    </div>
                    <div class="layui-col-md2">
                        <div class="layui-input-wrap">
                            <input type="text" name="code" value=""
                                   placeholder="{!! L(\App\Models\Addon::class, "code") !!}" class="layui-input"
                                   lay-affix="clear">
                        </div>
                    </div>
                    <div class="layui-col-md2">
                        <div class="layui-input-wrap">
                            <input type="text" name="version" value=""
                                   placeholder="{!! L(\App\Models\Addon::class, "version") !!}" class="layui-input"
                                   lay-affix="clear">
                        </div>
                    </div>
                    <div class="layui-btn-container layui-col-xs2">
                        <button class="layui-btn" lay-submit lay-filter="table-search">搜索</button>
                    </div>
                </form>
                <script type="text/html" id="options">
                    <div class="layui-btn-group">
                        <a class="layui-btn layui-btn-sm layui-btn-danger" lay-event="del" ptadmin-tips="删除">
                            <i class="layui-icon layui-icon-delete"></i>
                        </a>
                        <a class="layui-btn layui-btn-sm layui-btn-warning" lay-event="upload" ptadmin-tips="上传">
                            <i class="layui-icon layui-icon-upload"></i>
                        </a>
                    </div>
                </script>
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTTable', 'common', 'layer', 'form', 'table'], function () {
            const {PTTable, common, layer, form, table} = layui;
            const current = PTTable.render({
                extend: {
                    index_url: "{{admin_route('local-addons')}}",
                    create_url: "{{admin_route('local-addon')}}",
                    del_url: "{{admin_route('local-addon')}}/{id}",
                    title: {create: '上传附件', edit: '编辑模型', version: '上传版本'}
                },
                cols: [[
                    {field: 'id', title: 'ID', width: 60},
                    {field: 'title', title: '{!! L(\App\Models\Addon::class, "title") !!}'},
                    {field: 'code', title: '{!! L(\App\Models\Addon::class, "code") !!}'},
                    {field: 'intro', title: '{!! L(\App\Models\Addon::class, "intro") !!}'},
                    {field: 'version', title: '{!! L(\App\Models\Addon::class, "version") !!}'},
                    {
                        fixed: 'right',
                        width: 220,
                        title: '{{__("system.btn_handle")}}',
                        align: 'center',
                        templet: '#options'
                    },
                ]]
            });

            const handle = (title, url, method = 'put') => {
                layer.confirm(title, {icon: 3, title: 'Warning'}, function (index) {
                    common.post(url, {}, method, function (res) {
                        if (res.code === 0) {
                            current.reload();
                        } else {
                            layer.msg(res.message, {icon: 3});
                        }
                    });
                    layer.close(index);
                });
            };

            form.on('submit(add-addon)', function (data) {
                layer.open({
                    title: '新增',
                    type: 2,
                    area: ['1000px', '750px'],
                    content: `{{admin_route('local-addon')}}`,
                    fixed: false, // 不固定
                    maxmin: true,
                    shadeClose: true,
                    btn: false,
                });
            });

            form.on('switch(ptadmin-switch)', function (data) {
                const value = data.elem.value
                let fileName = $(data.elem).attr('data-name');
                let param = {
                    field: $(data.elem).attr('data-name'),
                    value: data.elem.checked === true ? 1 : 0,
                    is_edit: 1,
                    time_field: fileName == 'publish_status' ? 'published_at' : ''
                }
                common.put(`{{admin_route('addon')}}-field/${value}`, param)
            })

            table.on('tool(dataTable)', function (obj) {
                var data = obj.data;
                if (obj.event === 'upload') {
                    layer.open({
                        type: 2,
                        title: '文件上传',
                        shadeClose: true,
                        maxmin: true, //开启最大化最小化按钮
                        area: ['900px', '600px'],
                        content: `{{admin_route('local-addon-upload')}}/${data.id}`
                    });
                } else if (obj.event === 'edit') {

                    layer.open({
                        title: '编辑',
                        type: 2,
                        area: ['1000px', '750px'],
                        content: `{{admin_route('local-addon')}}/${data.id}`,
                        fixed: false, // 不固定
                        maxmin: true,
                        shadeClose: true,
                        btn: false,
                    });
                } else if (obj.event === 'del') {
                    let url = common.urlReplace(`{{admin_route('addon-del')}}/${data.id}`, obj.data);
                    layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                        common.del(url, {id: obj.data.id}, function (res) {
                            if (res.code === 0) {
                                PTTable.currentTable.reload();
                            } else {
                                layer.msg(res.message, {icon: 3});
                            }
                        });
                        layer.close(index);
                    });
                } else if (obj.event === 'version') {
                    layer.open({
                        title: '添加版本',
                        type: 2,
                        area: ['1000px', '750px'],
                        content: `{{admin_route('addon-store-version')}}/${data.id}`,
                        fixed: false, // 不固定
                        maxmin: true,
                        shadeClose: true,
                        btn: false,
                    });
                    {{--let url = common.urlReplace(`{{_route('addon-store-version')}}/${data.id}`, obj.data);--}}
                    {{--common.formOpen(url);--}}
                }
            })

            form.on('submit(table-search)', function (data) {
                var field = data.field; // 获得表单字段
                // 执行搜索重载
                table.reload('dataTable', {
                    page: {
                        curr: 1 // 重新从第 1 页开始
                    },
                    where: field // 搜索的字段
                });
                return false; // 阻止默认 form 跳转
            });

        });
    </script>
@endsection
