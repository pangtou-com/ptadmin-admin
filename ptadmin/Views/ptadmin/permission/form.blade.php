@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $parentId = (string)request()->get('parent_name', "");
                if ($dao->parent_name == "") {
                    $dao->parent_name = $parentId;
                }
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('name')->required();
                $form->text('title')->required();
                $form->radio('type')->setOptions(\PTAdmin\Admin\Enum\MenuTypeEnum::getMaps())->default(\PTAdmin\Admin\Enum\MenuTypeEnum::NAV)->required();
                $form->text('route');
                $form->select('parent_name')->default($parentId)->options((new \PTAdmin\Admin\Service\PermissionService()));
                $form->icon('icon');
                $form->number('sort')->default(99);
                $form->textarea('note');
                $form->radio('is_nav')->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps())->default(1);
                $form->radio('status')->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps())->default(1);
                echo $form->render();

            @endphp
        </
        >
    </div>
@endsection

@section("script")
    <script>
        layui.use(["PTForm", "jquery"], function () {
            const {PTForm, form} = layui
            PTForm.init();

            form.on('radio(type)', function (data) {
                handle(data.value)
            });

            const handle = (val) => {
                const route_input = document.querySelector('input[name="route"]').parentNode.parentNode;
                route_input.style.display = val === 'btn' || val === 'dir' ? 'none' : 'block';
            }

            handle("{{$dao->type}}")

        });
    </script>
@endsection
