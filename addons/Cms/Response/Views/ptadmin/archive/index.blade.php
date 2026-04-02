@extends('ptadmin.layouts.base')

@section("head")
    <link rel="stylesheet" href="{{addon_asset('cms', 'index.css')}}">
@endsection

@section("content")
    <div class="ptadmin-cms-box">
        <aside class="ptadmin-cms-aside">
            <header class="aside-header">
                <div class="title">内容管理</div>
            </header>
            <div class="layui-form checkbox-box">
                <input type="checkbox" class="expand-select" lay-filter="aside-checkbox" name="expand" title="展开">
                <input type="checkbox" class="all-select" lay-filter="aside-checkbox" name="all" title="全选">
            </div>
            <div id="zTree"  class="ztree"></div>
            <i class="layui-icon layui-icon-left expand-icon close-navigation" ptadmin-event="close-navigation"></i>
        </aside>
        <main class="ptadmin-cms-main">
            <i class="layui-icon layui-icon-right expand-icon open-navigation" ptadmin-event="open-navigation" hidden></i>
            <header class="main-header">
                <div class="title">内容管理</div>
                <div class="layui-btn-group">
                    <button type="button" class="layui-btn layui-btn-sm" ptadmin-event="create">
                        <i class="layui-icon layui-icon-addition"></i>
                    </button>
                    <button type="button" class="layui-btn layui-btn-sm layui-bg-blue" ptadmin-event="refresh">
                        <i class="layui-icon layui-icon-refresh-1"></i>
                    </button>
                    <input type="hidden" id="category_id" value="0">
                </div>
            </header>
            <div class="content">
                <div class="layui-card ptadmin-page-container">
                    <div class="layui-card-body">
                        <table id="dataTable" lay-filter="dataTable"></table>
                        <script type="text/html" id="expand">
                            <div class="ptadmin-page-expand-image-box">
                                @{{# if(d.cover !== ""){ }}
                                    <i class="layui-icon-picture layui-icon image" data-url="@{{d.cover}}"></i>
                                @{{# } }}
                                @{{# if(d.attribute_text  && d.attribute_text.length > 0){ }}
                                    <div class="tags">【@{{d.attribute_text[0]}}】</div>
                                @{{# } }}
                                <div class="content">@{{= d.title }}  <a href="@{{= d.a_url }}" target="_blank">【查看】</a></div>
                            </div>
                        </script>
                        {{--<script type="text/html" id="batch-operation">
                            <div class="ptadmin-page-batch-operation">
                                <a class="layui-btn layui-btn-sm" lay-event="batch">
                                    批量操作 <i class="layui-icon layui-icon-down"></i>
                                </a>
                            </div>
                        </script>--}}
                    </div>
                </div>
            </div>
        </main>
    </div>
@endsection

@section("script")
    <script>
        const dataTree = @json($tree);

    </script>
    @include('cms::ptadmin.archive._js', ['category' => $category])
@endsection
