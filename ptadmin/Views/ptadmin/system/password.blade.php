@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $form = \PTAdmin\Build\Layui::make();
                $form->password('old_password')->label('原密码')->required();
                $form->password('password')->label('新密码')->required();
                $form->password('password_confirmation')->label('确认密码')->required();
                echo $form->render();
            @endphp
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(["PTForm"], function () {
            const { PTForm } = layui

            PTForm.init();
        });
    </script>
@endsection
