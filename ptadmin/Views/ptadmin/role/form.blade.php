@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('name')->required();
                $form->text('title')->required();
                $form->textarea('note');
                $form->radio('status')->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps());
                echo $form->render();
            @endphp
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(["PTForm"], function () {
            const {PTForm} = layui

            PTForm.init();
        });

    </script>
@endsection
