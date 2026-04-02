@extends('ptadmin.layouts.base')

@section("content")
    <x-hint>
        如广告链接为内部文章，请将 --https://pangtou.com/article-detail/-- 与需要跳转的文章 id 拼接
        <br>
        例：https://pangtou.com/article-detail/1
    </x-hint>

    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $navigation_group_id = $dao->navigation_group_id ?? (int)request()->get('ad_position_id', 0);
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('title')->required();
                $form->select('ad_position_id')->setOptions(\Addon\Cms\Models\AdSpace::class, 'id', 'title')->default($navigation_group_id)->required();
                $form->avatar('image')->required();
                $form->text('links');
                $form->textarea('intro');
                $form->number('weight')->default(99);
                $form->radio('status')->options(\PTAdmin\Admin\Enum\StatusEnum::getMaps());
            @endphp
            {!! $form !!}
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm', 'laydate'], function () {
            let {PTForm, laydate} = layui;
            PTForm.init();
            laydate.render({
                elem: '#start_at',
                type: 'datetime',
                fullPanel: true
            });
            laydate.render({
                elem: '#end_at',
                type: 'datetime',
                fullPanel: true
            });
        })
    </script>
@endsection
