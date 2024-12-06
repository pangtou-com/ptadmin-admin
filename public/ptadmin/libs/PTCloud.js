/**
 * 云市场 PTCloud
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2022/04/09.
 */
layui.define(['laytpl', 'layer', 'common', "laypage", "rate", "dropdown"],function (exports) {
    "use strict";
    const { laytpl, layer, common, laypage, rate, dropdown, $ } = layui
    const MOD_NAME = 'PTCloud'
    const LIMIT = 30
    let options = {
        addon_login: '',
        addon_logout: ''
    }
    let current_type = ""
    /** 登录元素 */
    const PTADMIN_LOGIN_ELE = ".ptadmin_login";
    /** 插件列表元素 */
    const PTADMIN_ADDON_OBJECT = $("#addon_results")
    /** 用户信息展示 */
    const PTADMIN_USER_TEMPLATE = `
        <div style="padding: 20px">
            <blockquote class="layui-elem-quote">
                <strong>温馨提示</strong>
                <div>当前登录账户：【{{ d.nickname }}】</div>
            </blockquote>
        </div>`;

    /** 用户信息展示 */
    const showUserinfo = function () {
        let nickname = $(PTADMIN_LOGIN_ELE).data("addon-user")
        let index = layer.open({
            title: '用户信息',
            skin: 'layui-layer-lan',
            area: ['450px', '350px'],
            btn: ['退出登录', '关闭'],
            content: laytpl(PTADMIN_USER_TEMPLATE).render({nickname: nickname}),
            yes: function () {
                $.get(options.addon_logout, function (res) {
                    layer.close(index)
                    location.reload();
                })
            }
        })
    }

    const login = function () {
        common.formOpen(options.addon_login, '登录PTAdmin', {area: ['450px', '350px']})
    }

    /** 获取登录状态 */
    const getLoginStatus = function () {
        const nickname = $(PTADMIN_LOGIN_ELE).data("addon-user")
        return !(nickname === '')
    }

    const render_rate = () => {
        let lists = $(".addon-rate")
        lists.each(function () {
            rate.render({
                elem: this,
                value: $(this).data("addon-rate"),
                readonly: true,
            })
        })
    }

    const render_addon = (results) => {
        results.map(item => {
            item.version_results = ""
            let versions = item.versions;
            if (versions && versions.length > 0) {
                let str = '';
                for (let i = 0; i < versions.length; i++) {
                    str += `v${versions[i]['version']}_${versions[i]['id']}|`
                }
                item.version_results = str.substring(0, str.length - 1)
            }
        })

        PTADMIN_ADDON_OBJECT.html(results.length > 0
                ? laytpl($("#addonHtml").html()).render(results)
                : laytpl($("#emptyHtml").html()).render())

        render_rate()
    }

    const loadData = function (type, search = {}) {
        current_type = type
        const send = function (_page, _limit) {
            const data = {page: _page, limit: _limit, ...search}
            common.post(options[type], data, "post", function (res) {
                if (res.code !== 0) {
                    layer.msg(res.message, { icon: 3 })
                    return
                }
                render_addon(res.data.results)
                page(res.data.total, _page)
            })
        }

        const page = function (total, page = 1) {
            if (total <= LIMIT) {
                $("#addon-page").html("")
                return
            }
            laypage.render({
                elem: 'addon-page',
                count: total,
                theme: '#1E9FFF',
                limit: LIMIT,
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

        send(1, LIMIT)
    }

    const clickAddonEvent = function () {
        let type = $(this).attr('ptadmin-event')
        if ((type === 'addon_my') && getLoginStatus() === false) {
            login()
            return
        }
        $(this).addClass('layui-bg-purple')
            .removeClass('layui-btn-normal')
            .siblings()
            .removeClass('layui-bg-purple')
            .addClass('layui-btn-normal')

        loadData(type)
    }

    const search = function (){
        let type = $(this).data("type")
        $(this).addClass('active').siblings().removeClass('active')
        loadData(current_type, {type: type})
    }

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

    const getInstallUrl = function (data) {
        common.post(options.addon_download, data, "post", function (res){
            if (res.code !== 0) {
                layer.msg(res.message, { icon: 3 })
                return
            }

            console.log(res)
            let url = res.data.url
        })

    }

    const install = function () {
        if (!getLoginStatus()) {
            login()
            return
        }
        const data = $(this).data()
        const id = data.id
        const versions = buildVersion(data.versions, data.code)
        if (versions.length > 0) {
            let dropdownID = `addon_install_${data.code}`
            dropdown.render({
                elem: $(this),
                id: dropdownID,
                data: versions,
                click: function (data) {
                    data['addon_id'] = id
                    getInstallUrl(data)
                }
            });
            dropdown.open(dropdownID)
            return
        }
        getInstallUrl({code: data.code, addon_version_id: data.version, addon_id: data.id})
    }

    const events = {
        userinfo: () => getLoginStatus() ? showUserinfo() :login(),
        addon_cloud: clickAddonEvent,
        addon_local: clickAddonEvent,
        addon_my: clickAddonEvent,
        search: search,
        install: install
    }

    const PTCloud = function (ops = {}) {
        options = $.extend({}, options, ops)
        $('body').on("click", "*[ptadmin-event]", function () {
            const event = $(this).attr('ptadmin-event');
            if (events[event]) {
                events[event].call(this);
            }
        })

        return {
            getLoginStatus: getLoginStatus,
            login: login,
            showUserinfo: showUserinfo,
            loadAddons: loadData
        }
    }

    exports(MOD_NAME, PTCloud);
})
