@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $id = (int)request()->route('id', 0);
                $parentId = (int)request()->get('parent_id', 0);
            @endphp

            <form action="{{admin_route('cms/tag')}}@if($id)/{{$id}}@endif"
                  class="layui-form ptadmin-cms-category-form" id="form">
                @csrf
                @method($id ? 'put' : 'post')
                {!! \PTAdmin\Build\Layui::text('title', '标题', $dao->title)->required() !!}
                {!! \PTAdmin\Build\Layui::number('weight', '权重', $dao->weight) !!}
                {!! \PTAdmin\Build\Layui::radio('status', '状态', $dao->status)->default(1)->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps()); !!}
                {!! \PTAdmin\Build\Layui::avatar('cover', '封面图', $dao->cover) !!}
                {!! \PTAdmin\Build\Layui::text('seo_title', 'SEO标题', $dao->seo_title) !!}
                {!! \PTAdmin\Build\Layui::text('seo_keyword', 'SEO关键词', $dao->seo_keyword) !!}
                {!! \PTAdmin\Build\Layui::text('seo_doc', 'SEO描述', $dao->seo_doc) !!}
            </form>
        </div>
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
