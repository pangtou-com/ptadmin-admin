<form action="" class="layui-form">
    <div class="container-item">
        @if($data)
            @foreach($data as $key => $item)
                <div class="box">
                    <h4 class="title">{{$item['title']}}11</h4>
                    <div class="layui-row layui-col-space10">
                        <div class="layui-col-sm6">
                            {{-- 分类ID --}}
                            <input type="hidden" name="ids[]" value="{{$item['id']}}">
                            {!! $item['view'] ?? "" !!}
                        </div>
                        <div class="layui-col-sm6">
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
            @endforeach
        @endif
    </div>
    <div class="container-footer" >
        <button class="layui-btn layui-btn-normal" lay-submit lay-filter="config">保存</button>
    </div>
</form>
