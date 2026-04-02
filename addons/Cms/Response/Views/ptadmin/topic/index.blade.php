@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-topic-container layui-card">
            <div class="layui-card-header ptadmin-card-header">
                <div class="ptadmin-card-header-left">
                    <div class="layui-btn-group">
                        <button class="layui-btn layui-btn-sm layui-bg-blue" lay-submit lay-filter="create">
                            <i class="layui-icon layui-icon-addition"></i>
                        </button>
                        <button class="layui-btn layui-btn-sm " lay-submit lay-filter="reload">
                            <i class="layui-icon layui-icon-refresh"></i>
                        </button>
{{--                        <button class="layui-btn layui-btn-sm " lay-submit lay-filter="details">--}}
{{--                            详情--}}
{{--                        </button>--}}
                    </div>
                </div>
            </div>
            <div class="layui-card-body ptadmin-temps-category-box">
                <table id="dataTable" lay-filter="dataTable"></table>
                <script type="text/html" id="options">
                    <div class="layui-btn-group">
                        <a class="layui-btn layui-btn-xs" lay-event="edit">
                            <i class="layui-icon layui-icon-edit"></i>
                        </a>
{{--                        <a class="layui-btn layui-btn-xs" lay-event="detail">--}}
{{--                            <i class="layui-icon layui-icon-eye"></i>--}}
{{--                        </a>--}}
                        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="del">
                            <i class="layui-icon layui-icon-delete"></i>
                        </a>
                        <a class="layui-btn layui-btn-xs layui-btn-normal" id="@{{ d.id }}" lay-event="preview">
                            <i class="layui-icon layui-icon-eye"></i>
                        </a>
                        <a class="layui-btn  layui-btn-xs layui-bg-orange" id="@{{ d.id }}" lay-event="topicSetting">
                            <i class="layui-icon layui-icon-down"></i>
                            <!-- 下拉框里面的内容 -->
                            <!-- 设置导航/编辑导航 -->
                            <!-- 设置分类/编辑分类 -->
                        </a>
                    </div>
                </script>
            </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm','form', 'PTPage', 'common', 'dropdown'], function () {
            const { PTForm, form, common, PTPage, dropdown } = layui
            PTForm.init();

            const page = PTPage.make({
                // event: events,
                urls: {
                    index_url: "{{admin_route('cms/topics')}}",
                    create_url: "{{admin_route('cms/topic')}}",
                    edit_url: "{{admin_route('cms/topic')}}/{id}",
                    del_url: "{{admin_route('cms/topic')}}/{id}",
                    status_url: "{{admin_route('cms/topic/status')}}/topic/{id}",
                },
                search: false,
                table: {
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '专题标题'},
                        {field: 'subtitle', title: '副标题'},
                        {field: 'url', title: '路径名称'},
                        {field: 'remark', title: '备注信息'},
                        {field: 'topic_template', title: '模板类型'},
                        {field: 'weight', title: '权重', width: 80},
                        {field: 'status', title: '有效状态', width: 90, templet: PTPage.format.switch},
                        {field: 'num', title: '数量', width: 80},
                        {fixed: 'right', width: 200, title: '操作', align: 'center', templet: '#options'},
                    ]],
                    done: function (res) { }
                }
            })

            const options = [
                {
                    title: '专题导航', event: 'navForm', callback: function (data) {

                    }
                },
                {
                    title: '专题分类', event: 'navAssociation', callback: function (data) {

                    }
                }
            ]

            const events = {
                currentDropdown: {},
                topicSetting: function (data) {
                    const id = data.elem === undefined  ? 'batch_op' : $(data.elem).attr('id');
                    const op = []
                    for (const temp of options) {
                        temp['ids'] = data.data.id
                        temp['id'] = data.data.id
                        temp['disabled'] = 0
                        op.push(JSON.parse(JSON.stringify(temp)))
                    }
                    if (events.currentDropdown[id] !== undefined) {
                        dropdown.close(id)
                    }
                    events.currentDropdown[id] = dropdown.render({
                        elem: data.elem || data.target,
                        id: id,
                        show: true,
                        data: op,
                        click: function (obj) {
                            events[obj.event].call(undefined, obj)
                        }
                    })
                },
                navForm: function (data) {
                    location.href = "{{admin_route('cms/topic/navigations')}}/" + data.id;
                },
                navAssociation: function (data){
                    location.href = "{{admin_route('cms/topic/associations')}}/" + data.id;
                },
                preview: function (data) {
                    location.href = `{{admin_route('cms/topic/detail')}}/` + data.data.id;
                }
            }

            page.on('preview', events.preview)

            page.on('topicSetting', events.topicSetting);

            form.on('submit(create)', function (data) {
                common.formOpen(`{{admin_route('cms/topic')}}`, '添加专题')
            });

            form.on('submit(reload)', function () {
                location.reload();
            });

            form.on('submit(details)', function (data) {
                location.href = '{{admin_route('cms/topics/details')}}'
            });
            form.on('submit(navigations)', function (data) {
                location.href = '{{admin_route('cms/topics/navigations')}}'
            });

            form.on('submit(column)', function (data) {
                location.href = '{{admin_route('cms/topics/column')}}'
            });

        })
    </script>
@endsection
