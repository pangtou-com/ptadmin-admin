@extends("ptadmin-install::layouts.base")

@section('content')
    @foreach($results as $result)
        <ul class="requirement"><li class="li-title">{{$result['title']}}</li><li class="li-title">推荐配置</li><li class="li-title">当前状态</li></ul>
        @if(isset($result['results']) && $result['results'])
            @foreach($result['results'] as $item)
                <ul class="requirement lists @if(!$item['state']) error @endif">
                    <li class="body">{{$item['title']}}</li>
                    <li class="body">{{$item['config']}}</li>
                    <li class="body">
                        @if($item['state'])
                            <i class="layui-icon layui-icon-success"></i>
                        @else
                            <i class="layui-icon layui-icon-error" style="color: red"></i>
                        @endif
                    </li>
                </ul>
            @endforeach
        @endif
    @endforeach
@endsection

@section('button')
    <div style="text-align: center">
        <div class="layui-btn-group">
            <button type="button" id="pre" class="layui-btn layui-btn-sm">上一步</button>
            <button type="button" id="reload" class="layui-btn layui-bg-orange layui-btn-sm">重新检测</button>
            <button type="button" id="next" class="layui-btn layui-bg-blue layui-btn-sm">下一步</button>
        </div>
    </div>
@endsection

@section('script')
<script>
    layui.use('form', function() {
        const $ = layui.$
        $("#pre").on('click', () => {
            location.href = '/install'
        })

        $("#reload").on('click', () => {
            location.reload()
        })

        $("#next").on('click', () => {
            location.href = '/install/env'

        })
    })
</script>
@endsection
