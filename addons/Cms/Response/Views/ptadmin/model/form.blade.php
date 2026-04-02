@extends('ptadmin.layouts.base')

@section("content")
<div class="layui-fluid">
    <div class="layui-card-body">
        <x-hint>
            <p>1、模型名称：模型展示名称</p>
            <p>2、模型标识：实际为数据表名称，必须为英文字母，不支持中文，不支持特殊字符，新增后无法修改</p>
        </x-hint>
        @php
            $form = \PTAdmin\Build\Layui::make();
            $form->setMethod(isset($dao) ? 'put' : 'post');
            $form->setAction(isset($dao) ? admin_route("cms/model/{$dao->id}") : admin_route('cms/model'));
            $form->text('title', '模型名称', $dao->title ?? '')->required();
            $form->text('table_name', '模型标识', $dao->table_name ?? '')->required()->disabled(isset($dao));
            $form->number('weight', '排序', $dao->weight ?? '')->default(99);
//            $form->textarea('extra[prompt]', '温馨提示', data_get($dao ?? [], 'extra.prompt'))->required();
            $form->textarea('intro', '描述信息', $dao->intro ?? '');
        @endphp

        {!! $form !!}

    </div>
</div>
@endsection

@section("script")
<script>
    layui.use(['PTForm'], function () {
        let { PTForm } = layui;

        PTForm.init();
    })
</script>
@endsection
