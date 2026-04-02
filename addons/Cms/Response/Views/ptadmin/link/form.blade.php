@extends('ptadmin.layouts.base')

@section("content")

    <div class="layui-fluid">
        <div class="layui-card-body">
        @php
            $form = \PTAdmin\Build\Layui::make($dao);
            $form->radio('type')->options(\Addon\Base\Enum\TypeEnum::getMaps());
//            $form->radio('category')->options(\Addon\Link\Enum\TypeEnum::getMaps());
            $form->text('url')->required()->placeholder("请输入链接地址（示例：http://www.baidu.com）");
            $form->text('title')->required();
            $form->avatar('image')->required();
            $form->number('weight')->default(99);
            $form->text('email');
            $form->textarea('intro');
            $form->radio('status')->options(\PTAdmin\Admin\Enum\StatusEnum::getMaps())->default(1);
            $form->checkbox('attribute')->options(\Addon\Base\Enum\AttributeEnum::getMaps());
        @endphp
        {!! $form !!}
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm','form'], function () {
            let { PTForm, form } = layui;
            PTForm.init();
            // 监听是否为单页
            let type = parseInt($("input[name='type']:checked").val());
            // 初始化隐藏
            let $elementsToHide = $("#img-image").closest('.layui-form-item');
            $elementsToHide.attr("hidden", type === 0);
            form.on('radio(type)', function (data) {
                $elementsToHide.attr("hidden", parseInt(data.value) === 0);
            });
        })
    </script>
@endsection
