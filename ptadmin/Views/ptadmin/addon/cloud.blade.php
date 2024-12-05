@extends('ptadmin.layouts.base')

@section("content")
    <div class="content ptadmin-addon-content">
        <div class="layui-card">
            <div class="layui-card-header ptadmin-card-header">
                <div class="addon-header-left">
                    <div class="layui-btn-group">
                        <a href="javascript:void(0)" ptadmin-event="addon_cloud" class="layui-btn  layui-bg-purple"><i
                                    class="layui-icon layui-icon-component"></i>云市场</a>
                        <a href="javascript:void(0)" ptadmin-event="addon_local" class="layui-btn layui-btn-normal"><i
                                    class="layui-icon layui-icon-templeate-1"></i>本地插件</a>
                        <a href="javascript:void(0)" ptadmin-event="addon_my" class="layui-btn layui-btn-normal"><i
                                    class="layui-icon layui-icon-flag"></i>我的插件</a>
                    </div>
                    <a href="javascript:void(0)" ptadmin-event="local_install"
                       class="layui-btn layui-btn-normal layui-bg-purple">
                        <i class="layui-icon layui-icon-transfer"></i>本地安装
                    </a>
                </div>
                <button type="button" data-addon-user="{{ $ptadmin_addon_user['nickname'] ?? '' }}"
                        class="layui-btn layui-bg-purple ptadmin_login">
                    <i class="layui-icon layui-icon-user"></i> {{ $ptadmin_addon_user['nickname'] ?? '登录PTAdmin' }}
                </button>
            </div>
            <!-- <div class="layui-card-body"> -->
                <!-- <div class="layui-row layui-col-space20" id="addon_results"></div> -->
            <!-- </div> -->

            <!-- 样式调整 -->
                <div class="ptadmin-categorize-box">
                    <div class="ptadmin-categorize-container">
                        <aside class="ptadmin-categorize-aside">
                            <ul class="lists">
                                <li class="active">全部</li>
                                <li>独立应用</li>
                                <li>功能补充</li>
                                <li>开发辅助</li>
                                <li>聚合接口</li>
                                <li>信息通知</li>
                                <li>登录授权</li>
                                <li>在线支付</li>
                                <li>在线存储</li>
                                <li>编辑器</li>
                                <li>未归类</li>
                            </ul>
                        </aside>
                        <main class="ptadmin-categorize-main">
                            <div class="addon-header ptadmin-page-container">
                                <div class="ptadmin-page-box">
                                        <form class="layui-form layui-row layui-col-space16 layui-form-pane ptadmin-search-form">
                                            <!-- 默认 -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">默认</label>
                                                <div class="ptadmin-prefix">
                                                    <select>
                                                        <option value="1">不等于</option>
                                                        <option value="2">包含</option>
                                                        <option value="3">等于</option>
                                                    </select>
                                                </div>
                                                <input type="text" placeholder="带任意后置内容" class="layui-input" />
                                            </div>
                                            <!-- 无label -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <div class="ptadmin-prefix">
                                                    <select>
                                                        <option value="1">不等于</option>
                                                        <option value="2">包含</option>
                                                        <option value="3">等于</option>
                                                    </select>
                                                </div>
                                                <input type="text" placeholder="带任意后置内容" class="layui-input" />
                                            </div>
                                            <!-- 无select -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">无select</label>
                                                <input type="text" placeholder="带任意后置内容" class="layui-input" />
                                            </div>
                                            <!-- 数字区间 -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">数字区间</label>
                                                <div class="ptadmin-interval">
                                                    <input
                                                        type="number"
                                                        name="price_min"
                                                        placeholder=""
                                                        autocomplete="off"
                                                        class="layui-input"
                                                        min="0"
                                                        step="1"
                                                        lay-affix="number" />
                                                    <span class="icondivide">-</span>
                                                    <input
                                                        type="number"
                                                        name="price_max"
                                                        placeholder=""
                                                        autocomplete="off"
                                                        class="layui-input"
                                                        min="0"
                                                        step="1"
                                                        lay-affix="number" />
                                                </div>
                                            </div>
                                            <!-- 金额区间 -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">金额区间</label>
                                                <div class="ptadmin-interval">
                                                    <input
                                                        type="number"
                                                        name="price_min"
                                                        placeholder=""
                                                        autocomplete="off"
                                                        class="layui-input"
                                                        min="0"
                                                        step="1"
                                                        lay-affix="number" />
                                                    <span class="icondivide">-</span>
                                                    <input
                                                        type="number"
                                                        name="price_max"
                                                        placeholder=""
                                                        autocomplete="off"
                                                        class="layui-input"
                                                        min="0"
                                                        step="1"
                                                        lay-affix="number" />
                                                </div>
                                            </div>
                                            <!-- 时间选择 -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">时间选择</label>
                                                <input type="text" class="layui-input" id="ID-laydate-demo" placeholder="yyyy-MM-dd" />
                                            </div>
                                            <!-- 时间区间 -->
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3 ptadmin-input-group">
                                                <label class="layui-form-label ptadmin-label">时间区间</label>
                                                <div class="ptadmin-interval" id="ID-laydate-range">
                                                    <input
                                                        type="text"
                                                        autocomplete="off"
                                                        id="ID-laydate-start-date"
                                                        class="layui-input"
                                                        placeholder="开始日期" />
                                                    <span class="icondivide">-</span>
                                                    <input
                                                        type="text"
                                                        autocomplete="off"
                                                        id="ID-laydate-end-date"
                                                        class="layui-input"
                                                        placeholder="结束日期" />
                                                </div>
                                            </div>
                                            <div class="layui-col-xs12 layui-col-sm6 layui-col-md4 layui-col-lg3">
                                                <button class="layui-btn" lay-submit lay-filter="demo-search">Search</button>
                                                <button type="reset" class="layui-btn layui-btn-primary">Reset</button>
                                            </div>
                                        </form>
			                    </div>
                            </div>
                            <div class="addon-body">
                                <ul class="addon-lists">
                                    <li class="item">
                                        <div class="image">
                                            <a href="">
                                                <img src="https://www.pangtou.com/storage/default/20240906/jLHnxgYM3FDXLcuUaMpY7AUJoJD0wWIOTvzlotYk.jpg" alt="">
                                            </a>
                                        </div>
                                        <div class="addon-details">
                                            <div class="title">智慧小区物业管理小程序</div>
                                            <div class="rate-box">
                                                <div class="addon-rate"></div>
                                                <div class="price">￥1999.00</div>
                                            </div>
                                        </div>
                                        <div class="operate">
                                            <div class="badges">
                                                <span class="layui-badge layui-bg-green">官方</span>
                                            </div>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal">
                                                <i class="layui-icon layui-icon-download-circle"></i>
                                                安装
                                            </button>
                                        </div>
                                    </li>
                                    <li class="item">
                                        <div class="image">
                                            <a href="">
                                                <img src="https://www.pangtou.com/storage/default/20240906/vrQnOTkBiAJ02st3oCMPR5497nFdgbuPfQjwH71a.png" alt="">
                                            </a>
                                        </div>
                                        <div class="addon-details">
                                            <div class="title">沃德政务招商系统</div>
                                            <div class="rate-box">
                                                <div class="addon-rate"></div>
                                                <div class="price">￥699.00</div>
                                            </div>
                                        </div>
                                        <div class="operate">
                                            <div class="badges">
                                                <span class="layui-badge layui-bg-green">官方</span>
                                            </div>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal">
                                                <i class="layui-icon layui-icon-download-circle"></i>
                                                安装
                                            </button>
                                        </div>
                                    </li>
                                    <li class="item">
                                        <div class="image">
                                            <a href="">
                                                <img src="http://www.pangtouweb.com/storage/default/20240906/4B7sKpu62D7nMSVx5Inl8v29XnRx2PrhrXPZiJMJ.jpg" alt="">
                                            </a>
                                        </div>
                                        <div class="addon-details">
                                            <div class="title">西陆二手交易系统</div>
                                            <div class="rate-box">
                                                <div class="addon-rate"></div>
                                                <div class="price">￥399.00</div>
                                            </div>
                                        </div>
                                        <div class="operate">
                                            <div class="badges">
                                                <span class="layui-badge layui-bg-green">官方</span>
                                            </div>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal">
                                                <i class="layui-icon layui-icon-download-circle"></i>
                                                安装
                                            </button>
                                        </div>
                                    </li>
                                    <li class="item">
                                        <div class="image">
                                            <a href="">
                                                <img src="http://www.pangtouweb.com/storage/default/20240906/LTFMq4mCjDMYMsw8b9UYANBbj6sWQ9kZpYGZ1DBa.png" alt="">
                                            </a>
                                        </div>
                                        <div class="addon-details">
                                            <div class="title">XYkeep健身小程序</div>
                                            <div class="rate-box">
                                                <div class="addon-rate"></div>
                                                <div class="price">￥1480.00</div>
                                            </div>
                                        </div>
                                        <div class="operate">
                                            <div class="badges">
                                                <span class="layui-badge layui-bg-green">官方</span>
                                            </div>
                                            <button type="button" class="layui-btn layui-btn-sm layui-btn-normal">
                                                <i class="layui-icon layui-icon-download-circle"></i>
                                                安装
                                            </button>
                                        </div>
                                    </li>

                                </ul>
                                <!-- 预留分页 -->
                            </div>
                        </main>
                    </div>
                </div>
            <!-- 样式调整end -->

            <div style="text-align: center;padding: 0 20px">
                <div id="addon-page"></div>
            </div>
        </div>
    </div>

    <!--插件应用模版-->
    <script id="addonHtml" type="text/html">
        @{{#  layui.each(d, function(index, item){ }}
        <div class="layui-col-sm4 layui-col-md3 layui-col-xs12 layui-col-lg2">
            <div class="addon-item">
                <div class="addon-item-img">
                    @{{# if(item.cover_url === null){ }}
                    <div style="font-size: 100px;display: flex; justify-content: center">
                        <div style="top: 30%;position: absolute;">@{{ item.title.substr(0,1) }}</div>
                    </div>
                    @{{# }else{ }}
                    <img src="@{{ item.cover_url }}" alt="@{{ item.title }}">
                    @{{# } }}
                </div>
                <div class="addon-item-content">
                    <div class="addon-item-title">
                        <div class="title" title="@{{ item.title }}">@{{ item.title }}</div>
                        @{{# if(item.is_local != 1){ }}
                        <div class="addon-icon">
                            <div class="addon-price">
                                @{{# if(item.amount == 0){ }}
                                <strong>{{__("common.free")}}</strong>
                                @{{# } else { }}
                                @{{# if (item.old_amount) { }}
                                <del>￥@{{ item.old_amount }}</del>
                                @{{# } }}
                                <strong>￥@{{ item.amount }}</strong>
                                @{{# } }}
                            </div>
                            <div>
                                <i class="layui-icon layui-icon-download-circle"></i> @{{ item.download }}
                            </div>
                        </div>
                        @{{# } }}
                    </div>
                    <hr>
                    <div class="addon-btn">
                        <div class="addon-tag">
                            @{{# if(item.user_id == 0){ }}
                            <span class="layui-badge layui-bg-orange">{{__("common.official")}}</span>
                            @{{# } }}
                            <span class="layui-badge layui-bg-green">{{__("common.ensure")}}</span>
                            @{{# if(item.is_recommend > 0){ }}
                            <span class="layui-badge layui-bg-cyan">{{__('common.recommend')}}</span>
                            @{{# } }}
                        </div>
                        <div class="layui-btn-group">
                            @{{# if(item.is_local != 1){ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-install"
                                    data-code="@{{ item.code }}" data-id="@{{ item.id }}"
                                    data-versions="@{{item.version_results}}"
                                    data-versionId="@{{ item.addon_version_id }}">
                                {{__("common.install")}}
                                @{{# if (item.versions.length > 1) { }}
                                <i class="layui-icon layui-icon-down layui-font-12"></i>
                                @{{# } }}
                            </button>
                            @{{# }else{ }}
                            <button class="layui-btn layui-bg-red layui-btn-xs addon-uninstall"
                                    data-code="@{{ item.code }}">
                                {{__("common.uninstall")}}
                            </button>
                            @{{# if(item.setting === 1){ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-setting"
                                    data-code="@{{ item.code }}">
                                {{__("common.setting")}}
                            </button>
                            @{{# } }}
                            @{{# if(item.is_local != 1){ }}
                            <button class="layui-btn layui-bg-blue layui-btn-xs addon-update">
                                {{__("common.update")}}
                            </button>
                            @{{# } }}
                            @{{# } }}
                            @{{# if(item.platform != null){ }}
                            <a href="https://www.pangtou.com/addon/@{{item.id}}.html" target="_blank"
                               class="layui-btn layui-btn-xs layui-bg-purple" ptadmin-tips="{{__('common.view_demo')}}">
                                <i class="layui-icon layui-icon-website"></i>
                            </a>
                            @{{# } }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        layui.use(['dropdown', 'layer', 'common', 'form', 'laytpl', 'laypage', 'PTCloud','rate'], function () {
            const {dropdown, layer, common, form, laytpl, laypage, PTCloud,rate} = layui;

            const urls = {
                addon_cloud: "{{ admin_route("addon-cloud") }}",
                addon_my: "{{ admin_route("my-addon") }}",
                addon_local: "{{ admin_route("addon-local") }}",
                addon_create: "{{admin_route("local-addon")}}",
                local_install: "{{ admin_route("local-install") }}",
                addon_download: "{{ admin_route("addon-download") }}",
                addon_setting: "{{ admin_route("addon-setting") }}",
                addon_uninstall: "{{ admin_route("addon-uninstall") }}"
            }
            const cloud = PTCloud({
                addon_login: "{{ admin_route("cloud-login") }}",
                addon_logout: "{{ admin_route("cloud-logout") }}",
            })

            const loadAddon = function (type, search = {}) {
                let limit = 30
                const send = function (_page, _limit) {
                    const data = {page: _page, limit: _limit, ...search}
                    let index = layer.load();
                    const obj = $("#addon_results")
                    $.ajax({
                        url: urls[type],
                        type: 'post',
                        data: data,
                        dataType: 'json',
                        success: function (res) {
                            if (res.code === 0) {
                                res.data.results.map(item => {
                                    item.version_results = ""
                                    let versions = type === 'addon_my' ? item.addon ? item.addon.versions : [] : item.versions;
                                    if (versions && versions.length > 0) {
                                        let str = '';
                                        for (let i = 0; i < versions.length; i++) {
                                            str += `v${versions[i].version}_${versions[i].id}|`
                                        }
                                        item.version_results = str.substring(0, str.length - 1)
                                    }
                                })

                                let addonHtml = type !== 'addon_my' ? "#addonHtml" : "#addonMyHtml"

                                obj.html(
                                    res.data.results.length > 0
                                        ? laytpl($(addonHtml).html()).render(res.data.results)
                                        : laytpl($("#emptyHtml").html()).render())

                                addonInstall()
                                if (res.data.total > _limit) {
                                    page(res.data.total, _page)
                                } else {
                                    $("#addon-page").html("")
                                }
                            } else if (res.code === 401) {
                                userLogin();
                            } else {
                                obj.html(laytpl($("#emptyHtml").html()).render())
                                $("#addon-page").html("")
                            }
                        },
                        complete: () => {
                            layer.close(index);
                        }
                    })
                }

                const page = function (total, page = 1) {
                    laypage.render({
                        elem: 'addon-page',
                        count: total,
                        theme: '#1E9FFF',
                        limit: limit,
                        limits: [10, 20, 30, 40, 50],
                        curr: page,
                        layout: ['count', 'prev', 'page', 'next', 'limit'],
                        jump: function (obj, first) {
                            if (!first) {
                                send(obj.curr, obj.limit)
                            }
                        }
                    });
                }

                send(1, limit)
            }

            const clickAddonEvent = function () {
                let type = $(this).attr('ptadmin-event')
                if ((type === 'addon_my') && cloud.getLoginStatus() === false) {
                    cloud.login()
                    return
                }
                $(this).addClass('layui-bg-purple').removeClass('layui-btn-normal')
                    .siblings().removeClass('layui-bg-purple').addClass('layui-btn-normal')
                loadAddon(type)
            }

            /**
             * 下载插件
             * @param data
             */
            const getAddonInstallUrl = function (data) {
                layer.load()
                $.ajax({
                    url: urls['addon_download'],
                    type: 'post',
                    data: data,
                    dataType: 'json',
                    success: function (res) {
                        layer.closeAll()
                        if (res.code === 401) {
                            layer.msg("未登录云市场，请登录市场后操作", {icon: 2})
                            setTimeout(() => {
                                cloud.login()
                            }, 1500)
                            return
                        }
                        layer.msg(res.message)
                        if (res.code === 0) {
                            setTimeout(() => {
                                clickAddonEvent.call($('[ptadmin-event="addon_local"]'))
                            }, 1500)
                        }
                    }
                })
            }

            const addonInstall = function () {
                const buildVersion = function (versions, code) {
                    if (!versions) {
                        return []
                    }
                    let vs = versions.split('|')
                    versions = vs.map(item => {
                        let version = item.split('_')
                        return {title: version[0], addon_version_id: version[1], code}
                    })
                    return versions
                }
                $(".addon-install").click(function () {
                    if (cloud.getLoginStatus() === false) {
                        cloud.login()
                        return
                    }
                    let code = $(this).data('code')
                    let id = $(this).data('id')
                    let versionId = $(this).data('versionId')
                    let versions = buildVersion($(this).data('versions'), code)
                    if (versions.length > 1) {
                        let dropdownID = `addon_install_${code}`
                        dropdown.render({
                            elem: $(this),
                            id: dropdownID,
                            data: versions,
                            click: function (data) {
                                data['addon_id'] = id
                                getAddonInstallUrl(data)
                            }
                        });
                        dropdown.open(dropdownID)
                        return
                    }
                    getAddonInstallUrl({code: code, addon_id: id, addon_version_id: versionId})
                })
                /**
                 * 卸载
                 */
                $(".addon-uninstall").click(function () {
                    let code = $(this).data('code')
                    layer.confirm('是否确定卸载', {icon: 3}, function (index) {
                        $.ajax({
                            url: urls['addon_uninstall'] + '/' + code,
                            type: 'delete',
                            dataType: 'json',
                            success: function (res) {
                                if (res.code === 0) {
                                    layer.close(index)
                                    loadAddon('addon_local')
                                } else {
                                    layer.msg(res.message)
                                }
                                form.render();
                            }
                        })
                    });
                })

                $(".addon-setting").click(function () {
                    let code = $(this).data('code')
                    let addonHtml = "#addonSetting";
                    $.ajax({
                        url: urls['addon_setting'],
                        type: 'get',
                        data: {code: code},
                        dataType: 'json',
                        success: function (res) {
                            $('#extraSetting').html(res.data)
                            layer.open({
                                title: '配置信息',
                                skin: 'layui-layer-lan',
                                area: ['850px', '650px'],
                                btn: ['确定', '关闭'],
                                content: $(addonHtml).html(),
                                yes: function (index, layerObj) {
                                    form.on("submit(PT-submit)", function (data) {
                                        data.field.code = code
                                        common.post(urls['addon_setting'], data.field, "post", function (res) {
                                            if (res.code === 0) {
                                                layer.closeAll();
                                                layer.msg(res.message, {icon: 1});
                                            } else {
                                                layer.msg(res.message, {icon: 2});
                                            }
                                        });
                                        return false;
                                    });
                                    layerObj.find("button[lay-filter='PT-submit']").click()
                                }
                            })
                            form.render();
                        }
                    })
                })
            }

            const events = {
                addon_cloud: clickAddonEvent,
                addon_local: clickAddonEvent,
                addon_my: clickAddonEvent,
                addon_create: function () {
                    common.open(urls['addon_create'], '创建插件')
                },
                local_install: function () {
                    layer.open({
                        title: '本地安装',
                        type: 2,
                        area: ['1000px', '200px'],
                        content: urls['local_install'],
                        fixed: false, // 不固定
                        maxmin: true,
                        shadeClose: true,
                        btn: false,
                    });
                }
            }

            $("body").on('click', '*[ptadmin-event]', function () {
                const event = $(this).attr('ptadmin-event');
                if (events[event]) {
                    events[event].call(this);
                }
            })

            loadAddon('addon_cloud')

            // --皆为展示todo删除--
            rate.render({
                elem: '.addon-rate',
                value: 4,
                readonly: true
            });
        })
    </script>
@endsection
