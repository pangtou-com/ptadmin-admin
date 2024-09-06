
<form action="" class="layui-form">
    <div class="box">
        @if($data)
            <div class="box-right">
                <div class="box-item">
                    <div class="box-content">
                        @foreach($data as $key => $item)
                            <div class="box-content-item {!! $key ? "":"active" !!}" data-id="{{$item['id']}}">
                                {{-- 分类ID --}}
                                <input type="hidden" name="ids[]" value="{{$item['id']}}">
                                {!! $item['view'] ?? "" !!}
                            </div>
                        @endforeach
                    </div>
                    <div class="box-hint">
                        <x-hint>
                            <p>配置说明：</p>
                            <div class="layui-text-em">空</div>
                            <p>模版标签调用：</p>
                            <div class="layui-text-em">空</div>
                            <p>系统方法调用：</p>
                            <div class="layui-text-em">空</div>
                        </x-hint>
                    </div>
                </div>
            </div>
        @endif
    </div>
    <div class="container-footer layui-btn-group" >
            <button class="layui-btn layui-bg-blue" lay-submit lay-filter="config">保存</button>
            <button class="layui-btn ">列表</button>
    </div>
</form>
