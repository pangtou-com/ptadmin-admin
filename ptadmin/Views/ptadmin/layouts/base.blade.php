<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PTAdmin</title>
    <meta name="renderer" content="webkit">
    <link rel="icon" href="/favicon.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{_asset('/ptadmin/bin/css/layui.css')}}" media="all">
    <link rel="stylesheet" href="{{_asset('/ptadmin/style/app.css')}}" media="all">
    <link rel="stylesheet" href="{{_asset('/ptadmin/iconfont/iconfont.css')}}" />
    <link rel="stylesheet" href="{{_asset('/ptadmin/bin/fontawesome/css/all.min.css')}}" media="all">
    <link rel="stylesheet" href="{{_asset('/ptadmin/style/multipleSelect.css')}}" media="all">
    @yield('head')
</head>

<body class="ptadmin-layout-body">
    @yield('content')
    <script src="{{_asset('/ptadmin/js/jquery-1.12.4.min.js')}}"></script>
    <script src="{{_asset('/ptadmin/bin/layui.js')}}"></script>
    @if(setting('editor', 'tiny') === 'tiny')
    <script src="{{_asset('ptadmin/bin/editor/tinymce/tinymce.min.js')}}"></script>
    @else
    <script src="{{_asset('ptadmin/bin/editor/kindeditor/kindeditor-all-min.js')}}"></script>
    @endif
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        layui.config({
            base: '{{config("app.url")}}/ptadmin/libs/',
            @if(config('app.debug')) version: true @endif
        }).use(['layer', 'table', 'common', 'PTIcon'], function() {
            const {table, common} = layui;
            common.set({
                base: '{{config("app.url")}}/{{admin_route_prefix()}}/',
            })
            table.set({
                limit: 20,
                parseData: function(res) {
                    return {
                        "code": res.code,
                        "msg": res.message,
                        "count": res.data['total'] || 0,
                        "data": res.data['results'] || []
                    };
                },
            })
        });
    </script>
    @yield('script')
</body>

</html>
