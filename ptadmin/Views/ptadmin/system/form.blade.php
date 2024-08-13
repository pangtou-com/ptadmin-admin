@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('username')->required();
                $form->text('nickname')->required();
                $form->select('role_id')->setOptions(\PTAdmin\Admin\Models\Role::class, 'id', 'title')->required();
                $form->password('password');
                $form->text('mobile');
                $form->avatar('avatar');
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
