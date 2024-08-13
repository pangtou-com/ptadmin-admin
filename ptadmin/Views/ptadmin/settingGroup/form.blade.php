@extends("ptadmin.layouts.base")

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <x-hint>
                <p>配置标识：建议标识名称设置为英文单词,且不允许重复，用于模版调用和开发调用。<span style="color: red">请谨慎删除或修改配置标识</span> </p>
            </x-hint>
            @php
                $parentId = (int)request()->get('parent_id', 0);
                if ($dao->parent_id == 0 && $parentId) {
                    $dao->parent_id = $parentId;
                }
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->hidden('parent_id')->default($parentId);
                $form->text('title')->required();
                $form->text('name')->required();
                $form->number('weight')->default(99);
                $form->textarea('intro');
            @endphp
            {!! $form !!}
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


