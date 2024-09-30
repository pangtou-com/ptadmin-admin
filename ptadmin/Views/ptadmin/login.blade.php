@extends('ptadmin.layouts.base')


@section('content')
    <div class="ptadmin-login">
        <div class="ptadmin-login-main">
            <div class="ptadmin-login-header">
                <img src="{{_asset('/ptadmin/images/logo.png')}}" alt="">
                <h2>PTAdmin</h2>
            </div>
            <form class="layui-form">
                {{csrf_field()}}
                <div class="layui-form-item">
                    <div class="layui-input-wrap">
                        <div class="layui-input-prefix">
                            <i class="layui-icon layui-icon-username"></i>
                        </div>
                        <input type="text" name="username" id="login-username" lay-verify="required" placeholder="{{__("common.username")}}" class="layui-input" lay-affix="clear">
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-wrap">
                        <div class="layui-input-prefix">
                            <i class="layui-icon layui-icon-password"></i>
                        </div>
                        <input type="password" name="password" value="" lay-verify="required" placeholder="{{__("common.password")}}" lay-reqtext="{{__("common.password")}}" autocomplete="off" class="layui-input" lay-affix="eye">
                    </div>
                </div>
                <div class="layui-form-item">
                    <button type="button" class="layui-btn layui-btn-fluid" lay-submit lay-filter="login-submit">{{__("common.login_submit")}}</button>
                </div>
            </form>
        </div>
        <div class="layui-trans ptadmin-footer">
            <p>©2022 - {!! date('Y') !!} <a href="https://www.pangtou.com" target="_blank">PTAdmin管理系统</a></p>
        </div>
    </div>
@endsection

@section('script')
<script>
    layui.use(['form', 'layer', 'common'], function () {
        const {form, layer, common} = layui
        document.onkeydown = function (e) {
            if (e.keyCode === 13) {
                $('[lay-submit]').click()
            }
        }
        form.on('submit(login-submit)', function (obj) {
            common.loading({
                '--theme-expand-left': "0px",
                '--theme-header-top': "0px",
            })
            $.ajax({
                url: "{{admin_route('/login')}}",
                data: obj.field,
                type: 'post',
                dataType: 'json',
                success: function (response) {
                    if (response.code === 0) {
                        layer.msg(response.message, {icon: 1});
                        location.href = '{{admin_route('/layout')}}';
                    } else {
                        layer.msg(response.message, {icon: 2});
                    }
                },
                complete: function () {
                    common.loadingClose()
                }

            });
            return false;
        });
    })
</script>
@endsection
