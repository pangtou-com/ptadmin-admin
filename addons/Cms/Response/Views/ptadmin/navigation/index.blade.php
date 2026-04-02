@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-card">
        <div class="layui-card-body">
            <div class="layui-tab" lay-filter="navigation_group">
                <ul class="layui-tab-title">
                    @foreach($group as $k => $v)
                        <li @if($k == 0)class="layui-this" @endif lay-id="{{$v['id']}}">
                            {{$v['title']}}
                            @if($k > 0)
                                <i class="layui-icon layui-icon-close layui-unselect layui-tab-close"
                                   data-id="{{$v['id']}}" ptadmin-event="tabClose"></i>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="layui-tab-content">
                    <div class="layui-tab-item layui-show">
                        <div class="layui-card">
                            <div class="layui-card-header ptadmin-card-header">
                                <div class="ptadmin-card-header-left">
                                    <div class="layui-btn-group">
                                        <button class="layui-btn layui-btn-sm" ptadmin-event="create">
                                            <i class="layui-icon layui-icon-addition"></i>
                                        </button>
                                        <button class="layui-btn layui-btn-sm" ptadmin-event="reload">
                                            <i class="layui-icon layui-icon-refresh"></i>
                                        </button>
                                    </div>
                                </div>

                            </div>
                            <div class="layui-card-body">
                                <!--搜索区域后面构建-->
                                {{-- @include('manage.common.search')--}}
                                <table id="dataTable" lay-filter="dataTable"></table>
                                <script type="text/html" id="options">
                                    <div class="layui-btn-group">
                                        @{{# if(d.lv !=2 ){ }}
                                        <a class="layui-btn layui-btn-xs" lay-event="add">
                                            <i class="layui-icon layui-icon-addition"></i></a>
                                        @{{# } }}
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
                </div>
            </div>
        </div>

    </div>
@endsection

@section("script")
    <script>
        layui.use(['layer', 'treeTable', 'PTPage', 'common', 'form'], function () {
            const {treeTable, layer, PTPage, common, form, element} = layui
            let group_id = {{$navigation_group_id}};

            $('#created').on('click', function () {
                common.formOpen("{{admin_route('cms/navigation-group')}}", "新增导航分组")
            })

            $('#edit').on('click', function () {
                common.formOpen("{{admin_route('cms/navigation-group')}}/" + group_id, "编辑导航分组")
            })

            // tab 切换事件
            element.on('tab(navigation_group)', function (data) {
                group_id = $(this).attr('lay-id');
                treeTable.reload('dataTable', {
                    where: {
                        navigation_group_id: group_id
                    }
                });
            });

            treeTable.render({
                id: 'dataTable',
                elem: '#dataTable',
                url: "{{admin_route('cms/navigation-lists')}}",
                maxHeight: '500px',
                parseData: function (res) {
                    return {
                        "code": res.code,
                        "msg": res.message,
                        "count": res.data['total'] || 0,
                        "data": res.data['results'] || []
                    };
                },
                tree: {
                    customName: {
                        name: 'title',
                        icon: 'icon_show',
                    },
                    view: {
                        showIcon: false
                    }
                },
                btn_left: ['add', 'refresh'],
                cols: [[
                    {field: 'id', title: 'ID', width: 100, fixed: 'left'},
                    {field: 'title', title: '导航名称', width: 300},
                    {field: 'subtitle', title: '副标题', width: 200},
                    {field: 'icon', title: 'ICON', width: 100, templet: PTPage.format.icon},
                    {field: 'group_title', title: '导航分组', width: 200},
                    {field: 'type_text', title: '导航类型', width: 100},
                    {
                        field: 'url', title: '链接', width: 150, templet: function (data) {
                            return '<a href="' + data.url + '" target="_blank" style="color: #4397fd">' + data.url + '</a>';
                        }
                    },
                    {field: 'category_title', title: '文章分类', width: 200,},
                    {field: 'status', title: '状态', width: 100, templet: PTPage.format.whether},
                    {fixed: "right", title: "操作", width: 150, align: "center", toolbar: "#options"}
                ]],
                page: false
            });

            const events = {
                create: function () {
                    const index = common.formOpen("{{admin_route('cms/navigation')}}?navigation_group_id=" + group_id, "新增导航", {
                        yes: () => {
                            let iframeWindow = window['layui-layer-iframe' + index];
                            let form = $(iframeWindow.document).find('form');
                            $.ajax({
                                url: "{{admin_route('cms/navigation')}}?navigation_group_id=" + group_id,
                                data: form.serialize(),
                                type: 'post',
                                dataType: 'json',
                                success: function (response) {
                                    if (response.code === 0) {
                                        layer.msg(response.message, {icon: 1, zIndex: 999999999999});
                                        setTimeout(function () {
                                            element.tabChange('navigation_group', response.data);
                                        }, 1000)
                                        layer.close(index)
                                    } else {
                                        layer.msg(response.message, {icon: 2, zIndex: 999999999999});
                                    }
                                }
                            });
                        }
                    })
                },
                add: function (data) {
                    const index = common.formOpen(`{{admin_route('cms/navigation')}}?parent_id=${data.id}&navigation_group_id=${group_id}`, "新增导航", {
                        yes: () => {
                            let iframeWindow = window['layui-layer-iframe' + index];
                            let form = $(iframeWindow.document).find('form');
                            $.ajax({
                                url: `{{admin_route('cms/navigation')}}?parent_id=${data.id}&navigation_group_id=${group_id}`,
                                data: form.serialize(),
                                type: 'post',
                                dataType: 'json',
                                success: function (response) {
                                    if (response.code === 0) {
                                        layer.msg(response.message, {icon: 1, zIndex: 999999999999});
                                        setTimeout(function () {
                                            element.tabChange('navigation_group', response.data);
                                        }, 1000)
                                        layer.close(index)
                                    } else {
                                        layer.msg(response.message, {icon: 2, zIndex: 999999999999});
                                    }
                                }
                            });

                        }
                    })
                },
                edit: function (data) {
                    const index = common.formOpen("{{admin_route('cms/navigation')}}/" + data.id, "编辑导航", {
                        yes: () => {
                            let iframeWindow = window['layui-layer-iframe' + index];
                            let form = $(iframeWindow.document).find('form');
                            $.ajax({
                                url: "{{admin_route('cms/navigation')}}/" + data.id,
                                data: form.serialize(),
                                type: 'put',
                                dataType: 'json',
                                success: function (response) {
                                    if (response.code === 0) {
                                        layer.msg(response.message, {icon: 1, zIndex: 999999999999});
                                        setTimeout(function () {
                                            element.tabChange('navigation_group', response.data);
                                        }, 1000)
                                        layer.close(index)
                                    } else {
                                        layer.msg(response.message, {icon: 2, zIndex: 999999999999});
                                    }
                                }
                            });

                        }
                    })
                },
                del: function (data) {
                    layer.confirm('确定删除吗？', {icon: 3, title: 'Warning'}, function (index) {
                        $.ajax({
                            url: "{{admin_route('cms/navigation')}}/" + data.id,
                            type: 'delete',
                            dataType: 'json',
                            success: function (response) {
                                if (response.code === 0) {
                                    layer.msg('删除成功', {icon: 1, zIndex: 999999999999});
                                    setTimeout(function () {
                                        treeTable.reload('dataTable')
                                    }, 1000)
                                    layer.close(index);
                                } else {
                                    layer.msg(response.message, {icon: 2, zIndex: 999999999999});
                                }
                            }
                        });
                    });
                },
                reload: function () {
                    treeTable.reload('dataTable')
                },
                tabClose: function () {
                    const obj = $(this)
                    const id = obj.attr("data-id")
                    layer.confirm('确认要删除此项目吗?', {icon: 3, title: 'Warning'}, function (index) {
                        let url = `{{admin_route("cms/navigation-group")}}/${id}`
                        common.del(url, {}, function (res) {
                            if (res.code === 0) {
                                layer.msg(res.message, {icon: 1, zIndex: 999999999999});
                                element.tabDelete("navigation_group", id);
                            } else {
                                layer.msg(res.message, {icon: 2, zIndex: 999999999999});
                            }
                        });
                        layer.close(index);
                    });
                }
            }

            treeTable.on("tool(dataTable)", function (obj) {
                const data = obj.data;
                const event = obj.event;
                if (events[event]) {
                    events[event].call(this, data)
                    return
                }
                console.error(`未定义事件: 【${event}】`)
            })

            form.on('switch(ptadmin-switch)', function (data) {
                const value = data.elem.value
                let param = {
                    field: $(data.elem).attr('data-name'),
                    value: data.elem.checked === true ? 1 : 0,
                    is_edit: 1
                }
                common.put(`{{admin_route('cms/navigation-status')}}/${value}`, param)
            })

            $('body').on('click', '*[ptadmin-event]', function (e) {
                const event = $(this).attr('ptadmin-event')
                if (event === 'tabClose') {
                    e.stopPropagation()
                }
                if (events[event]) {
                    events[event].call(this)
                    return
                }
                console.error(`未定义事件: 【${event}】`)
            })

        });

    </script>
@endsection
