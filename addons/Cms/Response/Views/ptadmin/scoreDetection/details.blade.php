@extends('ptadmin.layouts.base')

@section("content")
    <div class="content">
        <x-hint>
            <div><strong>扫描详情</strong></div>
            @foreach($desc['desc'] as $k => $de)
                <p>{{$k + 1}}、{{ $de }}</p>
            @endforeach
        </x-hint>
        <div class="layui-card ptadmin-page-container">
            <div class="layui-card-header">
                <a href="{{admin_route('cms/score-detection')}}" class="layui-btn layui-btn-sm layui-bg-blue" lay-submit lay-filter="back">返回列表</a>
            </div>
            <div class="layui-card-body">
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
        </div>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['PTPage'], function () {
            const { PTPage } = layui
            const page = PTPage.make({
                urls: {
                },
                search: false,
                btn_left: null,
                btn_right: null,
                table: {
                    cols: [[
                        {field: 'id', title: 'ID', width: 60},
                        {field: 'title', title: '文件名称'},
                        {field: 'desc', title: '描述'},
                        {field: 'score', title: '分数', width: 100},
                    ]],
                    done: function (res) {
                        const data = res.data
                    }
                }
            })
        })
    </script>
@endsection
