@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <x-hint>
            <div><strong>内容模型</strong></div>
            <p>1、模型发布后才可使用</p>
            <p>2、发布后的模型无法删除、设置字段内容</p>
        </x-hint>
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-body">
                <script type="text/html" id="options">
                    <div class="layui-btn-group">
                        @{{# if(d.deleted_at != null){ }}
                            <button class="layui-btn layui-btn-sm" lay-event="restore">{{__("system.btn_restore")}}</button>
                            <button class="layui-btn layui-btn-sm layui-bg-danger" lay-event="thorough_del">
                                {{__("system.btn_thorough_del")}}
                            </button>
                        @{{# }else{ }}
                            <button class="layui-btn layui-btn-xs layui-bg-blue" lay-event="edit">
                                {{__("system.btn_edit")}}
                            </button>
                            <button class="layui-btn layui-btn-xs layui-bg-purple" lay-event="field">
                                {{__("system.btn_set")}}
                            </button>
                            <button class="layui-btn layui-btn-xs more" data-id="@{{d.id}}">
                                <span>更多</span>
                                <i class="layui-icon layui-icon-down layui-font-12"></i>
                            </button>
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
        layui.use(['common', 'layer', 'dropdown', 'PTPage'], function () {
            const {common, layer, dropdown, PTPage, form, element} = layui;
            const events = {
                field: function ({data}) {
                    location.href = `{{admin_route('cms/models/field')}}/${data.id}`
                },
                restore: function ({data}) {
                    handle('确认要恢复此模型吗?', `{{admin_route('cms/model-restore')}}/${data.id}`)
                },
                thorough_del: function ({data}) {
                    handle('确认要彻底删除此模型吗?', `{{admin_route('cms/model-thorough')}}/${data.id}`, 'delete')
                },
                del: function ({data}) {
                    handle('确认要删除此模型吗?', `{{admin_route('cms/model')}}/${data.id}`, 'delete')
                },
                preview: function ({data}) {
                    const index = common.formOpen(
                        `{{admin_route('cms/model-preview')}}/${data.id}`,
                        '{{__("system.btn_preview")}}',
                        {yes: () => layer.close(index)})
                },
                publish: function ({data}) {
                    handle('请确认是否发布此模型?', `{{admin_route('cms/model-publish')}}/${data.id}`)
                },
                cancel: function ({data}){
                    handle('请确认是否撤销此模型?', `{{admin_route('cms/model-cancel')}}/${data.id}`)
                }
            }

            const page = PTPage.make({
                urls: {
                    index_url: "{{admin_route('cms/models')}}",
                    create_url: "{{admin_route('cms/model')}}",
                    edit_url: "{{admin_route('cms/model')}}/{id}",
                    del_url: "{{admin_route('cms/model')}}/{id}",
                    status_url: "{{admin_route('cms/model-status')}}/{id}",
                    title: {create: '添加模型', edit: '编辑模型'}
                },
                btn_left:[{event: 'create', theme: 'info', text: '添加'},{event: 'recycle',theme: 'danger', text: '回收站'}],
                search: false,
                table: {
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '模型名称'},
                        {field: 'table_name', title: '表单标识'},
                        {field: 'weight', title: '排序', width: 80},
                        {field: 'status', title: '有效状态', width: 90, templet: PTPage.format.switch},
                        {field: 'is_publish', title: '是否发布', width: 90, templet: PTPage.format.whether},
                        {fixed: 'right', width: 200, title: '{{__("system.btn_handle")}}', align: 'center', templet: '#options'},
                    ]],
                    done: function (res) {
                        const data = res.data
                        $('.more').on('click', function () {
                            const id = $(this).data('id')
                            let cur = data.find((item) => item.id === parseInt(id))
                            const events = [
                                {title: '预览', event: 'preview', cur},
                                {title: '发布', event: 'publish', cur, disabled: cur['is_publish'] !== 0},
                                {type: '-'},
                                {title: '撤销发布', event: 'cancel', cur, disabled: cur['is_publish'] === 0},
                                {title: '删除', event: 'del', cur, disabled: cur['is_publish'] !== 0},
                            ]
                            moreEvents(id, events, this)
                        })
                    }
                }
            })

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

            // 设置模型字段
            page.on('field',function (obj){
                events.field(obj);
            });

            // 恢复
            page.on('restore',function (obj){
                events.restore(obj);
            });

            page.on('thorough_del',function (obj){
                events.thorough_del(obj);
            });

            page.on('del',function (obj){
                events.del(obj);
            });

            page.on('preview',function (obj){
                events.preview(obj);
            });

            page.on('publish',function (obj){
                events.publish(obj);
            });

            page.on('cancel',function (obj){
                events.cancel(obj);
            });


            /**
             * 更多事件处理
             * @param id
             * @param options
             * @param obj
             */
            const moreEvents = (id, options, obj) => {
                let dropdownID = `more_${id}`
                dropdown.render({
                    elem: $(obj),
                    id: dropdownID,
                    data: options,
                    click: function (data) {
                        events[data.event]({data: data.cur})
                    }
                });
                dropdown.open(dropdownID)
            }

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
        });
    </script>
@endsection
