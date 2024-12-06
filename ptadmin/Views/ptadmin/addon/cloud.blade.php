@extends('ptadmin.layouts.base')

@section("content")
    <div class="content ptadmin-addon-content">
        <div class="layui-card">
            <div class="layui-card-header ptadmin-card-header">
                <div class="addon-header-left">
                    <div class="layui-btn-group">
                        <a href="javascript:void(0)"
                           ptadmin-event="addon_cloud"
                           class="layui-btn  layui-bg-purple">
                            <i class="layui-icon layui-icon-component"></i>云市场
                        </a>
                        <a href="javascript:void(0)"
                           ptadmin-event="addon_local"
                           class="layui-btn layui-btn-normal">
                            <i class="layui-icon layui-icon-templeate-1"></i>本地插件
                        </a>
                        <a href="javascript:void(0)"
                           ptadmin-event="addon_my"
                           class="layui-btn layui-btn-normal">
                            <i class="layui-icon layui-icon-flag"></i>我的插件
                        </a>
                    </div>
                    <a href="javascript:void(0)" ptadmin-event="local_install"
                       class="layui-btn layui-btn-normal layui-bg-purple">
                        <i class="layui-icon layui-icon-transfer"></i>本地安装
                    </a>
                </div>
                <button
                    type="button"
                    data-addon-user="{{ $ptadmin_addon_user['nickname'] ?? '' }}"
                    class="layui-btn layui-bg-purple ptadmin_login"
                    ptadmin-event="userinfo"
                >
                    <i class="layui-icon layui-icon-user"></i> {{ $ptadmin_addon_user['nickname'] ?? '登录PTAdmin' }}
                </button>
            </div>
            <!-- 样式调整 -->
            <div class="ptadmin-categorize-box">
                <div class="ptadmin-categorize-container">
                    <aside class="ptadmin-categorize-aside">
                        <ul class="lists">
                            <li ptadmin-event="search" class="active">全部</li>
                            <li ptadmin-event="search" data-type="1">独立应用</li>
                            <li ptadmin-event="search" data-type="2">功能补充</li>
                            <li ptadmin-event="search" data-type="3">开发辅助</li>
                            <li ptadmin-event="search" data-type="4">聚合接口</li>
                            <li ptadmin-event="search" data-type="5">信息通知</li>
                            <li ptadmin-event="search" data-type="6">登录授权</li>
                            <li ptadmin-event="search" data-type="7">在线支付</li>
                            <li ptadmin-event="search" data-type="8">OSS存储</li>
                            <li ptadmin-event="search" data-type="9">编辑器</li>
                            <li ptadmin-event="search" data-type="0">未归类</li>
                        </ul>
                    </aside>
                    <main class="ptadmin-categorize-main">
                        <div class="addon-body">
                            <ul class="addon-lists" id="addon_results"></ul>
                            <!-- 预留分页 -->
                            <div style="text-align: center;padding: 0 20px">
                                <div id="addon-page"></div>
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!--插件应用模版-->
    <script id="addonHtml" type="text/html">
        @{{#  layui.each(d, function(index, item){ }}
            <li class="item">
                <div class="image">
                    <a href="https://www.pangtou.com/addon/@{{  item.id }}.html" target="_blank">
                        <img src="@{{ item.cover_url }}" alt="">
                    </a>
                </div>
                <div class="addon-details">
                    <div class="title">
                        <a href="https://www.pangtou.com/addon/@{{  item.id }}.html" target="_blank">@{{ item.title }}</a>
                    </div>
                    <div class="rate-box">
                        <div class="addon-rate" data-addon-rate="@{{ item.rate }}"></div>
                        <div class="price">
                            @{{# if(item.amount == 0){ }}
                                <strong>{{__("common.free")}}</strong>
                            @{{# } else { }}
                                <strong>￥@{{ item.amount }}</strong>
                            @{{# } }}
                        </div>
                    </div>
                </div>
                <div class="operate">
                    <div class="badges">
                        @{{# if(item.user_id == 0){ }}
                            <span class="layui-badge layui-bg-orange">{{__("common.official")}}</span>
                        @{{# } }}
                            <span class="layui-badge layui-bg-green">{{__("common.ensure")}}</span>
                        @{{# if(item.is_recommend > 0){ }}
                            <span class="layui-badge layui-bg-cyan">{{__('common.recommend')}}</span>
                        @{{# } }}
                    </div>
                    <button
                        type="button"
                        class="layui-btn layui-btn-sm layui-btn-normal"
                        ptadmin-event="install"
                        data-code="@{{ item.code }}"
                        data-id="@{{ item.id }}"
                        data-version="@{{ item.addon_version_id }}"
                        data-versions="@{{item.version_results}}"
                    >
                        <i class="layui-icon layui-icon-download-circle"></i>
                        安装
                    </button>
                </div>
            </li>
        @{{# }) }}
    </script>

    <script id="addonMyHtml" type="text/html">
        @{{#  layui.each(d, function(index, item){ }}
        <div class="layui-col-sm4 layui-col-md3 layui-col-xs12 layui-col-lg2">
            <div class="addon-item">
                <div class="addon-item-img">
                    <img src="@{{ item.addon.cover_url }}" alt="@{{ item.addon.title }}">
                </div>
                <div class="addon-item-content">
                    <div class="addon-btn">
                        <div class="addon-tag">
                            @{{ item.addon.title }}
                        </div>
                        <div class="layui-btn-group">
                            @{{# if(item.is_install === 1 ){ }}
                            @{{# if(item.setting === 1){ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-setting"
                                    data-code="@{{ item.addon.code }}">
                                {{__("common.setting")}}
                            </button>
                            @{{# } }}
                            @{{# if(item.version !== item.addon.version){ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-update">
                                {{__("common.update")}}
                            </button>
                            @{{# } }}
                            @{{# }else{ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-install"
                                    data-code="@{{ item.addon.code }}" data-id="@{{ item.addon.id }}"
                                    data-versions="@{{item.version_results}}"
                                    data-versionId="@{{ item.addon.addon_version_id }}">
                                {{__("common.install")}}
                                @{{# if (item.addon.versions.length > 1) { }}
                                <i class="layui-icon layui-icon-down layui-font-12"></i>
                                @{{# } }}
                            </button>
                            @{{# } }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @{{# }) }}

    </script>

    <div id="addonSetting" style="display: none">
        <div style="padding: 20px">
            <x-hint>
                <div>如遇问题请点击： <a href="https://www.pangtou.com" style="color: red" target="_blank">【PTAdmin】官网账户</a>
                </div>
            </x-hint>
            <form action="" id="form" class="layui-form">
                <div id="extraSetting">

                </div>
                {!! pt_submit() !!}
            </form>
        </div>
    </div>

    <script id="emptyHtml" type="text/html">
        <div class="empty">
            <div><i class="layui-icon layui-icon-form"></i></div>
            <p><span>暂无数据</span></p>
        </div>
    </script>
@endsection

@section("script")
<script>
    layui.use(['PTCloud'], function () {
        const { PTCloud } = layui;
        const cloud = PTCloud({
            addon_login: "{{ admin_route("cloud-login") }}",
            addon_logout: "{{ admin_route("cloud-logout") }}",
            addon_cloud: "{{ admin_route("addon-cloud") }}",
            addon_my: "{{ admin_route("my-addon") }}",
            addon_local: "{{ admin_route("addon-local") }}",
            addon_create: "{{admin_route("local-addon")}}",
            local_install: "{{ admin_route("local-install") }}",
            addon_download: "{{ admin_route("addon-download") }}",
            addon_setting: "{{ admin_route("addon-setting") }}",
            addon_uninstall: "{{ admin_route("addon-uninstall") }}"
        })

        cloud.loadAddons("addon_cloud")
    })
</script>
@endsection
