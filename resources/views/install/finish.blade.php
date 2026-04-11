@extends("ptadmin-install::layouts.base")

@section('content')
    <div class="pre-content">
        <pre><code>安装完成请登录后台使用</code></pre>
    </div>
@endsection

@section('button')
    <div style="text-align: center">
        <div class="layui-btn-group">
            <a href="{{route('admin_login')}}" id="reload" class="layui-btn layui-bg-orange layui-btn-sm">登录后台</a>
            <a href="/" id="next" class="layui-btn layui-bg-blue layui-btn-sm">返回首页</a>
        </div>
    </div>
@endsection

@section('script')
<script>

</script>
@endsection
