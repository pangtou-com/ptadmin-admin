@extends('ptadmin.layouts.base')
@section('content')
    <div class="configure ptadmin-categorize-box">
	    <header class="ptadmin-categorize-header">
            <div class="title">系统设置</div>
            <div class="right layui-btn-group">
                <button type="button" class="layui-btn category-created" id="created"><i class="layui-icon layui-icon-addition"></i>系统设置</button>
                <button type="button" class="layui-btn category-created" id="created_next"><i class="layui-icon layui-icon-addition"></i>新增分组</button>
            </div>
        </header>
        <div class="ptadmin-categorize-container">
            <aside class="ptadmin-categorize-aside">
                <!--系统设置分组-->
                <ul class="lists" id="ptadmin-categorize-aside"></ul>
            </aside>
            <main class="ptadmin-categorize-main">
                <div class="layui-card">
                    <div class="card-header">
                        <!--左侧tag分组区域-->
                        <ul class="ptadmin-categorize-tabs"></ul>
                        <div class="layui-btn-group">
                            <a href="javascript:void(0)" ptadmin-event="changeBox" data-type="form" class="layui-btn layui-btn-sm layui-bg-blue">
                                <i class="layui-icon layui-icon-form"></i>
                                <i class="layui-icon layui-icon-cols"></i>
                            </a>
                            <button class="layui-btn layui-btn-sm layui-bg-purple" ptadmin-tips="在当前分组下新增下级分类" id="create-parent">
                                <i class="layui-icon layui-icon-addition"></i>
                            </button>
                        </div>
                    </div>
                    <div class="layui-card-body">
                        <div id="setting-container"></div>
                        <div class="container-footer layui-btn-group" >
                            <button class="layui-btn layui-bg-blue" lay-submit lay-filter="config">保存</button>
                            <button class="layui-btn ">列表</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

<script id="group_html" type="text/html">
    <li class="" ptadmin-event="group"  date-id="@{{d.id}}" data-name="@{{d.name}}">
        @{{d.title}}
        <div class="layui-btn-group" data-id="@{{d.id}}">
            <button class="layui-btn-xs layui-btn layui-bg-orange" ptadmin-event="edit" ptadmin-event-stop ptadmin-tips="编辑内容">
                <i class="layui-icon layui-icon-edit"></i>
            </button>
            <button class="layui-btn-xs layui-btn layui-bg-red" ptadmin-event="delete" ptadmin-event-stop ptadmin-tips="删除内容">
                <i class="layui-icon layui-icon-delete"></i>
            </button>
        </div>
    </li>
</script>

<script id="table_html" type="text/html">
    <table class="layui-table active">
        <colgroup>
            <col width="200">
            <col width="200">
            <col>
            <col width="90">
            <col width="120">
            <col width="90">
        </colgroup>
        <thead>
        <tr>
            <th>标题</th>
            <th>标识</th>
            <th>备注</th>
            <th>排序</th>
            <th>类型</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
            @{{# layui.each(d.data, function(index, $child) { }}
                <tr>
                    <td>@{{= $child.title}}</td>
                    <td>@{{= $child.name}}</td>
                    <td>@{{= $child.intro}}</td>
                    <td>@{{= $child.weight}}</td>
                    <td>@{{= $child.type}}</td>
                    <td>
                        <div class="layui-btn-group" data-id="@{{= $child.id}}">
                            <button class="layui-btn layui-btn-xs" ptadmin-event="field-edit"><i class="layui-icon layui-icon-edit"></i></button>
                            <button class="layui-btn layui-btn-xs layui-btn-danger" ptadmin-event="field-del"><i class="layui-icon layui-icon-delete"></i></button>
                        </div>
                    </td>
                </tr>
            @{{# }) }}
            @{{# if(d.data.length === 0) { }}
                <tr>
                    <td colspan="6" style="text-align: center;">数据为空</td>
                </tr>
            @{{# } }}
        </tbody>
    </table>
</script>
@endsection

@section('script')
    <script>
        layui.use(['PTForm', 'PTSetting'], function () {
            const {  common, layer, PTSetting } = layui;
            const events = {
                edit: function (id) {
                    common.formOpen(`{{admin_route("setting-group")}}/${id}`, '编辑分组')
                },
                delete: function (id) {
                    layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                        common.del(`{{admin_route("setting-group")}}/${id}`, {}, function (res) {
                            if (res.code === 0) {
                                layer.msg(res.message, {icon: 1});
                                setTimeout(function () {
                                    location.href = "{{admin_route('settings')}}"
                                }, 1000)
                            } else {
                                layer.msg(res.message, {icon: 3});
                            }
                        });
                        layer.close(index);
                    });
                },
                'field-create': function (id) {
                    common.formOpen(`{{admin_route("settings")}}?category_id=${id}`, '新增配置字段')
                },
                'field-del': function (id) {
                    layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                        common.del(`{{admin_route("setting")}}/${id}`, {}, function (res) {
                            if (res.code === 0) {
                                layer.msg(res.message, {icon: 1});
                                setTimeout(function () {
                                    location.reload()
                                }, 1000)
                            } else {
                                layer.msg(res.message, {icon: 3});
                            }
                        });
                        layer.close(index);
                    });
                },
                'field-edit': function (id) {
                    common.formOpen(`{{admin_route("setting")}}/${id}`, '编辑配置字段')
                },
            }

            const init = function () {
                $.ajax({
                    url: '{{admin_route("settings")}}',
                    type: 'get',
                    dataType: 'json',
                    success: function (res) {
                        if (res.code === 0) {

                            PTSetting.init(res.data)
                        }
                    }
                })
            }



            // 定义回调信息
            PTSetting.on("*", function () {
                console.log(arguments)
            })
            init()

        });
    </script>
@endsection
