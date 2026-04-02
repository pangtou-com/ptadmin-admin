@extends('ptadmin.layouts.base')

@section("content")
<div class="layui-fluid">
    <div class="layui-card-body">
        @php
            $form = \PTAdmin\Build\Layui::make($dao);
            $form->text('title')->required();
            $form->textarea('intro');
            $form->radio('status')->options(\PTAdmin\Admin\Enum\StatusEnum::getMaps());
        @endphp
        {!! $form !!}
    </div>
</div>
@endsection

@section("script")
<script>
    layui.use(["PTForm"], function () {
        let { PTForm } = layui;
        PTForm.init();
    });
</script>
@endsection
