@extends('ptadmin.layouts.base')


@section('content')
    <div class="ptadmin-login">
        <div class="ptadmin-login-main">
            <div class="ptadmin-login-header">
                <img src="{{_asset('/ptadmin/images/logo.png')}}" alt="">
                <h2>404</h2>
            </div>
            <div style="line-height: 200%">
                <p>哦豁！找不到了，它应该找朋友去了！</p>
                <p>您现在可以做下面的操作：</p>
                <p style="padding-top: 20px;text-align: center">
                    <a href="/" class="layui-btn layui-bg-blue layui-btn-sm">首页</a>
                    <a href="javascript:history.go(-1)" class="layui-btn layui-bg-red layui-btn-sm">返回上一页</a>
                </p>
            </div>
        </div>
        <div class="layui-trans ptadmin-footer">
            <p>©2022 - {!! date('Y') !!} <a href="https://www.pangtou.com" target="_blank">PTAdmin管理系统</a></p>
        </div>
    </div>
@endsection
