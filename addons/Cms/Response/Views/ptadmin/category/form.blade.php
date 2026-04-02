@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-cms-category">
        @php
            $id = (int)request()->route('id', 0);
            $parentId = (int)request()->get('parent_id', 0);
        @endphp
        <form action="{{admin_route('cms/category')}}@if($id)/{{$id}}@endif" class="layui-form ptadmin-cms-category-form" id="form">
            @csrf
            @method($id ? 'put' : 'post')
            <div class="layui-row">
                <div class="layui-col-xs6 section border">
                    <div class="title">基础设置</div>
                    <div class="content">
                        {!! \PTAdmin\Build\Layui::select('parent_id', '父栏目', $parentId ?:$dao->parent_id )->options($parentCategories)->required() !!}
                        {!! \PTAdmin\Build\Layui::text('title', '标题', $dao->title)->required() !!}
                        {!! \PTAdmin\Build\Layui::select('mod_id', '扩展模型', $dao->mod_id)->options($models)->disabled(isset($dao->mod_id)) !!}
                        {!! \PTAdmin\Build\Layui::text('subtitle', '副标题', $dao->subtitle) !!}
                        {!! \PTAdmin\Build\Layui::text('dir_name', '目录名称', $dao->dir_name) !!}
                        {!! \PTAdmin\Build\Layui::number('weight', '权重', $dao->weight) !!}
{{--                        {!! \PTAdmin\Build\Layui::radio('is_related', '是否有关联', $dao->is_single)->default(0)->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps()); !!}--}}
                        {!! \PTAdmin\Build\Layui::radio('is_single', '是否为单页', $dao->is_single)->default(0)->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps()); !!}
                        {!! \PTAdmin\Build\Layui::radio('status', '状态', $dao->status)->default(1)->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps()); !!}
                        {!! \PTAdmin\Build\Layui::textarea('note', '备注说明', $dao->note) !!}
                    </div>
                </div>
                <div class="layui-col-xs6">
                    <div class="section">
                        <div class="title">封面图</div>
                        <div class="content">
                            {!! \PTAdmin\Build\Layui::avatar('cover', '封面图', $dao->cover) !!}

                        </div>
                    </div>
                    <div class="section">
                        <div class="title">栏目广告</div>
                        <div class="content">
                            {!! \PTAdmin\Build\Layui::avatar('banner', '栏目广告', $dao->banner) !!}

                        </div>
                    </div>
                    <div class="section">
                        <div class="title">SEO设置</div>
                        <div class="content">
                            {!! \PTAdmin\Build\Layui::text('seo_title', 'SEO标题', $dao->seo_title) !!}
                            {!! \PTAdmin\Build\Layui::text('seo_keyword', 'SEO关键词', $dao->seo_keyword) !!}
                            {!! \PTAdmin\Build\Layui::text('seo_doc', 'SEO描述', $dao->seo_doc) !!}
                        </div>
                    </div>
                    {{--<div class="section">
                        <div class="title">模板设置</div>
                        <div class="content">
                            {!! \PTAdmin\Build\Layui::select('template_list', '模板列表页', $dao->template_list)->setOptions(get_lists_template()) !!}
                            {!! \PTAdmin\Build\Layui::select('template_detail', '模板详情页', $dao->template_detail)->setOptions(get_detail_template()) !!}
                            {!! \PTAdmin\Build\Layui::select('template_channel', '模板频道页', $dao->template_channel)->setOptions(get_channel_template()) !!}
                        </div>
                    </div>--}}
                </div>
            </div>
        </form>
    </div>
@endsection

@section("script")
    <script>
        layui.use(["PTForm", "form"], function () {
            const {PTForm, form} = layui
            PTForm.init();
            // 监听是否为单页
            let initial_is_single = parseInt($("input[name='is_single']:checked").val());
            // 初始化隐藏
            let $elementsToHide = $("#template_list, #template_channel").parent().parent();
            $elementsToHide.attr("hidden", initial_is_single !== 0);
            form.on('radio(is_single)', function (data) {
                $elementsToHide.attr("hidden", parseInt(data.value) !== 0);
            });
        });
    </script>
@endsection
