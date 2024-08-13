@extends("ptadmin.layouts.base")

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <x-hint>
                <p>配置标识：建议标识名称设置为英文单词,且不允许重复，用于模版调用和开发调用。<span style="color: red">请谨慎删除或修改配置标识</span> </p>
                <p>配置项：如果是选项类型（多选，单选，下拉等）需要配置该项,配置项格式为：一行一条数据</p>
            </x-hint>
            @php
                $parentId = (int)request()->get('category_id', 0);
                if ($dao->configure_category_id == 0 && $parentId) {
                    $dao->configure_category_id = $parentId;
                }
                $form = \Zane\Build\Layui::make($dao);
                $form->hidden('configure_category_id')->default($parentId);
                $form->text('title')->required();
                $form->text('name')->required();
                $form->select('type')->required()->setOptions(\App\Enum\FormTypeEnum::getMaps());
                $form->text('default_val');
                $form->textarea('extra')->placeholder('选项值，一行一条数据，如需要指定值可 key=value');
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

