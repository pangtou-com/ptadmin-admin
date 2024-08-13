@extends('ptadmin.layouts.base')

@section("content")
    <div style="padding: 20px">
        <x-hint>
            <strong>温馨提示</strong>
            <div>请登录 <a href="https://www.pangtou.com" style="color: red" target="_blank">【PTAdmin】官网账户</a></div>
        </x-hint>
        <form action="" id="form" class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label"><color class="red_point">*</color>{{__("common.username")}}:</label>
                <div class="layui-input-block layui-input-wrap">
                    <input type="text" name="username" lay-verify="required" autocomplete="off" lay-affix="clear" class="layui-input" placeholder="{{__("common.username")}}">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label"><color class="red_point">*</color>{{__("common.password")}}:</label>
                <div class="layui-input-block layui-input-wrap">
                    <input type="password" name="password" lay-verify="required" autocomplete="off" lay-affix="clear" class="layui-input" placeholder="{{__("common.password")}}">
                </div>
            </div>
        </form>
    </div>
@endsection

@section("script")
<script>
    layui.use(["PTForm"], function () {
        const { PTForm } = layui

        PTForm.init()
    })
</script>
@endsection
