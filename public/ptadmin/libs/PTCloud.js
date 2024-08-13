/**
 * 云市场 PTCloud
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2022/04/09.
 */
layui.define(['laytpl', 'layer', 'common'],function (exports) {
    "use strict";
    const {laytpl, layer, common} = layui

    const MOD_NAME = 'PTCloud'
    let options = {
        addon_login: '',
        addon_logout: ''
    }
    /** 登录元素 */
    const PTADMIN_LOGIN_ELE = ".ptadmin_login";

    /** 用户信息展示 */
    const PTADMIN_USER_TEMPLATE = `
        <div style="padding: 20px">
            <blockquote class="layui-elem-quote">
                <strong>温馨提示</strong>
                <div>当前登录账户：【{{ d.nickname }}】</div>
            </blockquote>
        </div>`;

    /** 用户信息展示 */
    const showUserinfo = function (nickname) {
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

    const PTCloud = function (ops = {}) {
        options = $.extend({}, options, ops)

        $(PTADMIN_LOGIN_ELE).click(function () {
            const nickname = $(this).data("addon-user")
            if (nickname !== "") {
                showUserinfo(nickname)
                return
            }

            login()
        })

        return {
            getLoginStatus: getLoginStatus,
            login: login,
            showUserinfo: showUserinfo
        }
    }

    exports(MOD_NAME, PTCloud);
})
