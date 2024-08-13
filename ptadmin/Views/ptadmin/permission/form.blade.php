@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            @php
                $parentId = (int)request()->get('parent_id', 0);
                if ($dao->parent_id == 0) {
                    $dao->parent_id = $parentId;
                }
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('name')->required();
                $form->text('title')->required();
                $form->radio('type')->setOptions(\PTAdmin\Admin\Enum\MenuTypeEnum::getMaps())->default(\PTAdmin\Admin\Enum\MenuTypeEnum::NAV)->required();
                $form->text('route');
                $form->select('parent_id')->default($parentId)->options((new \PTAdmin\Admin\Service\PermissionService()));
                $form->icon('icon');
                $form->number('sort')->default(99);
                $form->textarea('note');
                $form->radio('is_nav')->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps());
                $form->radio('status')->setOptions(\PTAdmin\Admin\Enum\StatusEnum::getMaps());
                $form->radio('is_inner')->setOptions(\PTAdmin\Admin\Enum\WhetherEnum::getMaps());
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
                const is_inner = document.querySelector('input[name="is_inner"]').parentNode.parentNode;
                is_inner.style.display = val === 'btn' || val === 'dir' || val === 'link' ? 'none' : 'block';
            }

            handle("{{$dao->type}}")

        });
    </script>
@endsection
