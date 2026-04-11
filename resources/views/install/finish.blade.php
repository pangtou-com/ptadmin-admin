@extends("ptadmin-install::layouts.base")

@section('content')
    <div class="pre-content">
        <pre><code>{{ __('ptadmin::install.finish_message') }}</code></pre>
    </div>
@endsection

@section('button')
    <div style="text-align: center">
        <div class="layui-btn-group">
            <a href="{{route('admin_login')}}" id="reload" class="layui-btn layui-bg-orange layui-btn-sm">{{ __('ptadmin::install.login_admin') }}</a>
            <a href="/" id="next" class="layui-btn layui-bg-blue layui-btn-sm">{{ __('ptadmin::install.back_home') }}</a>
        </div>
    </div>
@endsection

@section('script')
<script>

</script>
@endsection
