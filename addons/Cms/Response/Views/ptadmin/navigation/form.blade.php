@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $navigation_group_id = $dao->navigation_group_id ?? (int)request()->get('navigation_group_id', 0);
                $parent_id = $dao->parent_id ?? (int)request()->get('parent_id', 0);
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->hidden('navigation_group_id',$navigation_group_id);
                $form->text('title')->required();
                $form->text('subtitle');
                $form->icon('icon');
                $form->avatar('cover');
                $form->select('parent_id')->options((\Addon\Cms\Service\MenuItemService::getOption($navigation_group_id)))->default($parent_id)->required();
                $form->radio('target')->options(\Addon\Cms\Enum\NavigationTargetEnum::getMaps())->required();
                $form->radio('type')->options(\Addon\Cms\Enum\MenuTypeEnum::getMaps())->required();
                $form->text('url');
                $form->select('category_id')->options($category);
                $form->radio('is_sync')->options(\Addon\Cms\Enum\NavigationSyncEnum::getMaps())->default(1);
                $form->radio('status')->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps())->default(1);
                $form->number('weight')->default(99);
            @endphp
            {!! $form !!}
        </div>
    </div>
@endsection


@section("script")
    <script>
        layui.use(["PTForm", "form", "common"], function () {
            const {PTForm, form, common} = layui

            PTForm.init();

            form.on('select(category_id)', function (data) {
                let is_sync = $("input[name='is_sync']:checked").val();
                let value = data.value;
                if (is_sync == 0) {
                    let url = "{{admin_route('nav/navigation-category')}}"
                    common.post(url, {parent_id: value}, 'post', function (res) {
                        if (res.data.category.length > 0) {
                            let str = '';
                            str += '<div class="layui-panel custom" style="margin-bottom: 15px">'
                            str += '<div>'
                            str += '<div class="layui-card">'
                            str += '<div class="layui-card-header">下级导航</div>'
                            str += '<div class="layui-card-body">'
                            str += '<table class="layui-table">'
                            str += '<tr>'
                            str += '<th width="43%">导航名称</th>'
                            str += '<th width="43%">副标题</th>'
                            str += '<th width="14%"></th>'
                            str += '</tr>'
                            for (let i = 0; i < res.data.category.length; i++) {
                                str += '<tr>'
                                str += '<input type="hidden" name="son[]" value=' + res.data.category[i].id + '>'
                                str += '<td><input type="text" name="son_title[]" class="layui-input" value=' + res.data.category[i].title + ' ></td>'
                                str += '<td><input type="text" name="son_subtitle[]" placeholder="请输入副标题" class="layui-input"></td>'
                                str += '<td>'
                                str += '<button type="button" class="layui-btn layui-btn-sm layui-btn-danger del-tr">'
                                str += '<i class="layui-icon layui-icon-delete"></i> 删除'
                                str += '</button>'
                                str += '</td>'
                                str += '</tr>'
                            }
                            str += '</table>'
                            str += '</div>'
                            str += '</div>'
                            str += '</div>'
                            str += '</div>'
                            $("input[name='is_sync']").parents('.layui-form-item').after(str)
                        } else {
                            $('.custom').remove();
                        }
                    });
                } else {
                    $('.custom').remove();
                }
            });

            form.on('radio(is_sync)', function (data) {
                let category_id = $('#category_id').val();
                let value = data.value;
                if (value == 0) {
                    let url = "{{admin_route('nav/navigation-category')}}"
                    common.post(url, {parent_id: category_id}, 'post', function (res) {
                        if (res.data.category.length > 0) {
                            let str = '';
                            str += '<div class="layui-panel custom" style="margin-bottom: 15px">'
                            str += '<div>'
                            str += '<div class="layui-card">'
                            str += '<div class="layui-card-header">下级导航</div>'
                            str += '<div class="layui-card-body">'
                            str += '<table class="layui-table">'
                            str += '<tr>'
                            str += '<th width="43%">导航名称</th>'
                            str += '<th width="43%">副标题</th>'
                            str += '<th width="14%"></th>'
                            str += '</tr>'
                            for (let i = 0; i < res.data.category.length; i++) {
                                str += '<tr>'
                                str += '<input type="hidden" name="son[]" value=' + res.data.category[i].id + '>'
                                str += '<td><input type="text" name="son_title[]" class="layui-input" value=' + res.data.category[i].title + ' ></td>'
                                str += '<td><input type="text" name="son_subtitle[]" placeholder="请输入副标题" class="layui-input"></td>'
                                str += '<td>'
                                str += '<button type="button" class="layui-btn layui-btn-sm layui-btn-danger del-tr">'
                                str += '<i class="layui-icon layui-icon-delete"></i> 删除'
                                str += '</button>'
                                str += '</td>'
                                str += '</tr>'
                            }
                            str += '</table>'
                            str += '</div>'
                            str += '</div>'
                            str += '</div>'
                            str += '</div>'
                            $("input[name='is_sync']").parents('.layui-form-item').after(str)
                        } else {
                            $('.custom').remove();
                        }
                    });
                } else {
                    $('.custom').remove();
                }
            });

            form.on('radio(type)', function (data) {
                let value = data.value;
                if (value == 2) {
                    $('#url').parents('.layui-form-item').hide();
                    $('#category_id').parents('.layui-form-item').show();
                    $("input[name='is_sync']").parents('.layui-form-item').show();

                    let is_sync = $("input[name='is_sync']:checked").val();
                    if (is_sync == 0) {
                        $('.custom').show();
                    }
                } else if (value == 0) {
                    $('#url').parents('.layui-form-item').show();
                    $('#category_id').parents('.layui-form-item').hide();
                    $("input[name='is_sync']").parents('.layui-form-item').hide();
                    $('.custom').hide();
                } else {
                    $('#url').parents('.layui-form-item').hide();
                    $('#category_id').parents('.layui-form-item').hide();
                    $("input[name='is_sync']").parents('.layui-form-item').hide();
                    $('.custom').hide();
                }
            });

            let type = $("input[name='type']:checked").val();
            let son = @json($son);
            if (type == 2) {
                $('#url').parents('.layui-form-item').hide();
                $('#category_id').parents('.layui-form-item').show();
                $("input[name='is_sync']").parents('.layui-form-item').show();
                if (son.length > 0) {
                    let str = '';
                    str += '<div class="layui-panel custom" style="margin-bottom: 15px">'
                    str += '<div>'
                    str += '<div class="layui-card">'
                    str += '<div class="layui-card-header">下级导航</div>'
                    str += '<div class="layui-card-body">'
                    str += '<table class="layui-table">'
                    str += '<tr>'
                    str += '<th width="43%">导航名称</th>'
                    str += '<th width="43%">副标题</th>'
                    str += '<th width="14%"></th>'
                    str += '</tr>'
                    for (let i = 0; i < son.length; i++) {
                        str += '<tr>'
                        str += '<input type="hidden" name="son[]" value=' + son[i].id + '>'
                        str += '<td><input type="text" name="son_title[]" class="layui-input" value=' + son[i].title + ' ></td>'
                        str += '<td><input type="text" name="son_subtitle[]" class="layui-input" value=' + son[i].subtitle + ' ></td>'
                        str += '<td>'
                        str += '<button type="button" class="layui-btn layui-btn-sm layui-btn-danger del-tr">'
                        str += '<i class="layui-icon layui-icon-delete"></i> 删除'
                        str += '</button>'
                        str += '</td>'
                        str += '</tr>'
                    }
                    str += '</table>'
                    str += '</div>'
                    str += '</div>'
                    str += '</div>'
                    str += '</div>'
                    $("input[name='is_sync']").parents('.layui-form-item').after(str)
                }
            } else if (type == 0) {
                $('#url').parents('.layui-form-item').show();
                $('#category_id').parents('.layui-form-item').hide();
                $("input[name='is_sync']").parents('.layui-form-item').hide();
                $('.custom').hide();
            } else {
                $('#url').parents('.layui-form-item').hide();
                $('#category_id').parents('.layui-form-item').hide();
                $("input[name='is_sync']").parents('.layui-form-item').hide();
                $('.custom').hide();
            }

            $('.layui-card-body').on('click', '.del-tr', function () {
                $(this).parents('tr').remove();
                let length = $('tr').length
                if (length == 1) {
                    $('.custom').remove();
                }
            })

        });
    </script>
@endsection
