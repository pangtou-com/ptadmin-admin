@extends('ptadmin.layouts.base')
@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <form action="" class="layui-form">
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">请求地址：</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input layui-disabled" value="{{$dao['url']}}">
                    </div>
                    <label for="" class="layui-form-label">请求方式：</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input layui-disabled" value="{{$dao['method']}}">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">控制器：</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input layui-disabled" value="{{$dao['controller']}}">
                    </div>
                    <label for="" class="layui-form-label">执行方法：</label>
                    <div class="layui-input-inline">
                        <input type="text" class="layui-input layui-disabled" value="{{$dao['action']}}">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">请求参数：</label>
                    <div class="layui-input-block">
<pre class="layui-code" lay-skin="notepad" lay-encode="true">
{!! var_export($dao['request']) !!}
</pre>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">响应状态：</label>
                    <div class="layui-input-block">
                        @php
                        $status = [200 => 'layui-bg-blue', 500 => 'layui-bg-red'];
                        @endphp
                        <span class="layui-badge {{$status[$dao['response_code']] ?? 'layui-bg-red'}}">{{$dao['response_code']}}</span>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">响应参数：</label>
                    <div class="layui-input-block">
<pre class="layui-code" lay-skin="notepad" lay-encode="true">
{!! var_export($dao['response']) !!}
</pre>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label for="" class="layui-form-label">sql信息：</label>
                    <div class="layui-input-block">
                        @include('ptadmin.operationRecord.sql', ['data' => $dao['sql_param']])
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

