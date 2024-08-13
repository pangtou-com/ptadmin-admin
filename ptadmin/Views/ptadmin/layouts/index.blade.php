@extends("ptadmin.layouts.base")

@section("content")
<div class="ptadmin_app" id="ptadmin_app">
    <div class="ptadmin-layout">
        <!-- 左侧导航 -->
        <div class="ptadmin-layout-left">
            <div class="ptadmin-logo" lay-href="{{admin_route('console')}}">
                <img class="expand" src="{{_asset('ptadmin/images/logo_all_white.png')}}" alt="">
                <img class="shrink" src="{{_asset('ptadmin/images/logo_white.png')}}" alt="">
            </div>
            <div class="ptadmin-nav">
                <div class="layui-side-scroll">
                    <ul class="layui-nav layui-nav-tree" lay-shrink="all" id="ptadmin-side-menu" lay-filter="ptadmin-side-menu">
                        {!! $nav !!}
                    </ul>
                </div>
            </div>
        </div>

        <div class="ptadmin-layout-right">
            <!-- 头部导航 -->
            <div class="ptadmin-layout-header">
                <ul class="layui-nav">
                    <li class="layui-nav-item ptadmin-flexible">
                        <a href="javascript:void(0);" ptadmin-event="flexible" title="侧边伸缩">
                            <i class="layui-icon layui-icon-spread-left" id="flexible_icon"></i>
                        </a>
                    </li>
                    <li class="layui-nav-item layui-hide-xs">
                        <a href="/" target="_blank" title="前台">
                            <i class="layui-icon layui-icon-website"></i>
                        </a>
                    </li>
                    <li class="layui-nav-item">
                        <a href="javascript:void(0);" ptadmin-event="refresh" title="刷新">
                            <i class="layui-icon layui-icon-refresh-3"></i>
                        </a>
                    </li>
                    <li class="layui-nav-item layui-hide-xs">
                        <input type="text" placeholder="搜索..." autocomplete="off" class="layui-input layui-input-search" layadmin-event="search" lay-action="template/search.html?keywords=">
                    </li>
                </ul>
                <ul class="layui-nav layui-nav-right">
                    <li class="layui-nav-item layui-hide-xs">
                        <a lay-href="app/message/index.html" ptadmin-event="message" lay-text="消息中心">
                            <i class="layui-icon layui-icon-notice"></i>
                            <!-- 如果有新消息，则显示小圆点 -->
                            <span class="layui-badge-dot"></span>
                        </a>
                    </li>

                    <li class="layui-nav-item layui-hide-xs">
                        <a href="javascript:void(0);" ptadmin-event="fullscreen">
                            <i class="layui-icon layui-icon-screen-full"></i>
                        </a>
                    </li>

                    <li class="layui-nav-item" lay-unselect>
                        <a href="javascript:void(0);">
                            <cite>管理员</cite>
                        </a>
                        <dl class="layui-nav-child">
                            <dd><a href="javascript:void(0)" ptadmin-href="{{admin_route('system/info')}}">基本资料</a>
                            </dd>
                            <dd><a href="javascript:void(0)" ptadmin-event="password" data-url="{{admin_route('system/password')}}">修改密码</a></dd>
                            <hr>
                            <dd ptadmin-event="logout" data-url="{{admin_route('logout')}}" style="text-align: center;"><a href="javascript:void(0)">退出</a></dd>
                        </dl>
                    </li>
                </ul>
            </div>
            <!-- 标签区域 -->
            <div class="ptadmin-layout-tabs" id="ptadmin_app_tabs">
                <div class="layui-tab" lay-unauto lay-allowClose="true" lay-filter="ptadmin-layout-tabs">
                    <ul id="ptadmin_app_tab_header" class="layui-tab-title">
                        <li lay-id="{{admin_route('console')}}" lay-attr="{{admin_route('console')}}" class="layui-this"><i class="layui-icon layui-icon-home"></i></li>
                    </ul>
                </div>
            </div>

            <!-- 主体内容 -->
            <div class="ptadmin-layout-content " id="iframe_body">
                <div class="ptadmin-iframe-item ptadmin-show">
                    <iframe src="{{admin_route('console')}}" frameborder="0" class="ptadmin_iframe"></iframe>
                </div>
            </div>
            <!-- 底部区域 -->
            <div class="ptadmin-layout-footer"></div>
            <!-- 遮罩 -->
            <div class="ptadmin-shade" ptadmin-event="shade"></div>
            <!-- 标签右键区域 -->
            <div class="ptadmin-layout-tabs-action">
                <ul class="content">
                    <!-- disabled -->
                    <li class="item" type="refresh">
                        <i class="layui-icon layui-icon-refresh"></i>
                        重新加载
                    </li>
                    <li class="item" type="close">
                        <i class="layui-icon layui-icon-close"></i>
                        关闭标签
                    </li>
                    <li class="item" type="other" ptadmin-event="closeOtherTab">
                        <i class="layui-icon layui-icon-subtraction"></i>
                        关闭其它标签
                    </li>
                    <li class="item" type="all" ptadmin-event="closeAllTab">
                        <i class="layui-icon layui-icon-tips"></i>
                        关闭全部标签
                    </li>
                    <li class="item" type="left">
                        <i class="layui-icon layui-icon-prev"></i>
                        关闭左侧标签
                    </li>
                    <li class="item" type="right" ptadmin-event="closeRightTab">
                        <i class="layui-icon layui-icon-next"></i>
                        关闭右侧标签
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<script id="password-html" type="text/html">

</script>
@endsection

@section("script")
<script>
    layui.extend({
        main: 'main',
    }).use(["form", 'main', 'layout'], function() {
        const {
            layout
        } = layui
        // 仪表盘iframe事件
        const iframe = document.querySelector('.ptadmin_iframe')
        iframe.onload = function() {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            iframeDoc.body.addEventListener('click', function(event) {
                event.preventDefault();
                layout.closeTabAction()
            });
        };
    })
</script>
@endsection