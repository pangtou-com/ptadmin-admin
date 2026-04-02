@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <x-hint>
                <p>分组标识为前端调用时的标志信息，若不填写标识则默认为名称首字母，前端调用方式为：XXXXXX</p>
            </x-hint>
            @php
                $form = \PTAdmin\Build\Layui::make($dao);
                $form->text('title')->required();
                if(isset($dao->code) && (string)$dao->code==='default'){
                   $form->text('code')->disabled();
                }else{
                   $form->text('code');
                }
                $form->radio('status')->options(\PTAdmin\Admin\Enum\StatusEnum::getMaps())->default(1);
            @endphp
            {!! $form !!}
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTForm'], function () {
            const { PTForm } = layui
            PTForm.init();
        });
    </script>
@endsection







