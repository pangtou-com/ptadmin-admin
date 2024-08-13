@extends('ptadmin.layouts.base')
@section('content')
    <div class="configure">
        <div class="left">
            <div class="category">
                <div>配置分组</div>
                <div class="category-created" id="created"><i class="layui-icon layui-icon-addition"></i></div>
            </div>
            <ul class="lists">
                @foreach($data['cate'] as $item)
                    <li class="@if($item['id'] == $data['cateId'])active @endif" date-id="{{$item['id']}}">
                        {{$item['title']}}
                        <div class="layui-btn-group" data-id="{{$item['id']}}">
                            <button class="layui-btn layui-bg-orange" ptadmin-event="edit" ptadmin-tips="编辑内容"><i
                                        class="layui-icon layui-icon-edit"></i></button>
                            <button class="layui-btn layui-bg-red" ptadmin-event="delete" ptadmin-tips="删除内容"><i
                                        class="layui-icon layui-icon-delete"></i></button>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="right">
            <div class="header-title">
                <div class="title">{{$data['title']}}</div>
                <div class="layui-btn-group">
                    <input type="hidden" id="cate_id" value="{{$data['cateId']}}">
                    <a href="{{admin_route("settings")}}?cate_id={{$data['cateId']}}&type=form" ptadmin-tips="编辑配置内容"
                       class="layui-btn layui-btn-sm @if($data['type'] == 'form')layui-bg-blue @endif"><i
                                class="layui-icon layui-icon-form"></i></a>
                    <a href="{{admin_route("settings")}}?cate_id={{$data['cateId']}}&type=lists"
                       ptadmin-tips="查看当前分组下的字段信息，可编辑设置子集分类字段"
                       class="layui-btn layui-btn-sm @if($data['type'] == 'lists')layui-bg-blue @endif"><i
                                class="layui-icon layui-icon-cols"></i></a>
                    <button class="layui-btn layui-btn-sm layui-bg-purple" ptadmin-tips="在当前分组下新增下级分类"
                            id="create-parent"><i class="layui-icon layui-icon-addition"></i></button>
                </div>
            </div>
            {{-- 当前选中的分组根ID --}}
            <input type="hidden" id="cate_id" value="{{$data['cateId']}}">
            @if($data['type'] === 'form')
                @include('ptadmin.setting._form', ['data' => $data['results']])
            @else
                @include('ptadmin.setting._table', ['data' => $data['results']])
            @endif
        </div>
    </div>
@endsection

@section('script')
    <script>
        const mark = @json($data['mark'])

        layui.use(['PTForm', 'form', 'common'], function () {
            const {PTForm, common, form} = layui;
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

            // 分类事件处理
            $(".lists").on('click', 'li', function (e) {
                if (!$(e.target).is('i') && !$(e.target).is('button')) {
                    location.href = `{{admin_route("settings")}}?cate_id=${$(this).attr('date-id')}`
                }
            }).on('click', '*[ptadmin-event]', function (e) {
                const event = $(this).attr('ptadmin-event')
                const id = $($(this).parent()[0]).attr('data-id')
                events[event](id)
            })

            // 表单获取点击事件 mouseover
            $(".layui-form-item").on('click', function () {
                const field = $(this).find('label').attr('for')
                const con = mark[field] || {}
                const obj = $(".layui-elem-quote")
                obj.html('')
                if (con['intro']) {
                    obj.append(`<p>配置说明：</p>`)
                    obj.append(`<div class="layui-text-em">${con['intro']}</div>`)
                }
                if (con['template_tag']) {
                    obj.append(`<p>模版标签调用：</p>`)
                    obj.append(`<div class="layui-text-em">${con['template_tag']}</div>`)
                }
                if (con['system']) {
                    obj.append(`<p>系统方法调用：</p>`)
                    obj.append(`<div class="layui-text-em">${con['system']}</div>`)
                }
            });

            // 表单左侧分类点击事件
            $(".left-title").on('click', ".title", function () {
                let id = $(this).attr('data-id')
                let item = $('.box-content-item')
                $(this).parent().find('.title').removeClass("active")
                $(this).addClass('active')
                item.removeClass('active')
                for (let i = 0; i < item.length; i++) {
                    if ($(item[i]).attr('data-id') === id) {
                        $(item[i]).addClass("active")
                        break
                    }
                }
            })


            // 新增分组
            $("#created").on('click', function () {
                common.formOpen('{{admin_route("setting-group")}}', '新增分组')
            })

            // 新增下级分组
            $('#create-parent').on('click', function () {
                const id = $("#cate_id").val()
                common.formOpen(`{{admin_route("setting-group")}}?parent_id=${id}`, '新增下级分组')
            })

            // 列表事件监听
            @if($data['type'] === 'lists')
            $("#tableData").on('click', '*[ptadmin-event]', function () {
                const event = $(this).attr('ptadmin-event')
                const id = $($(this).parent()[0]).attr('data-id')
                if (!events[event]) {
                    console.error(`【${event}】事件未定义`)
                    return
                }
                events[event](id)
            })
            @else
            PTForm.init();
            form.on("submit(config)", function (data) {
                common.post("{{admin_route("setting-val")}}", data.field, 'post', function (res) {
                    if (res.code !== 0) {
                        layer.msg(res.message, {icon: 2});
                        return
                    }
                    layer.msg(res.message, {icon: 1});
                });
                return false;
            });

            @endif
        });
    </script>
@endsection
