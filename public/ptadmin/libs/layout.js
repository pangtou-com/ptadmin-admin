layui.define(['form', 'common', 'element'], function (exports) {
    "use strict";
    const { $, common, element } = layui
    const MOD_NAME = "layout"
    /**  icon样式  **/
    const ICON = {
        spread: 'layui-icon-spread-left', // 展开
        shrink: 'layui-icon-shrink-right' // 收起
    }
    /** 移动端展开样式 **/
    const SIDE_SPREAD_SM = 'ptadmin-spread-sm'
    /** 移动端收缩样式 **/
    const SIDE_SHRINK_SM = 'ptadmin-shrink-sm'
    /** 侧边栏收缩样式 **/
    const SIDE_SHRINK = 'ptadmin-shrink'
    /**  侧边栏ICON  **/
    const FLEXIBLE_ICON = 'flexible_icon'
    /** 事件前缀 */
    const PTADMIN_EVENT = 'ptadmin-event'
    /** layui激活样式 */
    const LAYUI_ACTIVE = 'layui-this'
    /** 导航 */
    const PTADMIN_SIDE_MENU = 'ptadmin-side-menu'
    /** 收缩弹出框 */
    const PTADMIN_SHRINK_NAV = 'ptadmin-shrink-nav'
    /**  Iframe ID  **/
    const IFRAME_BODY = 'iframe_body'
    const IFRAME_BODY_ITEM = 'ptadmin-iframe-item'
    const SHOW = 'ptadmin-show'

    /** 选项卡filter **/
    const LAYOUT_TABS = "ptadmin-layout-tabs"

    /** tab_header */
    const TAB_HEADER = 'ptadmin_app_tab_header'
    /**  左侧导航 */
    const PTADMIN_NAV = 'ptadmin-nav'
    /** 选项卡右键元素 */
    const action_ele = $('.ptadmin-layout-tabs-action')
    const app = $('#ptadmin_app')
    const $win = $(window)
    const $body = $('body')
    const layout = {
        v: '0.1',
        /** 当前激活body **/
        currentBodyIndex: 0,
        rightClickTab: undefined,
        allTabsDom: undefined,
        /** 刷新遮罩样式 */
        shadeConfig: {
            '--theme-expand-left': '',
            'width': ''
        },
        /**
         * 设置侧边栏伸缩功能
         * status 为 true 表示当前为展开状态，切换为收缩状态
         * status 为 false 表示当前为搜索状态，切换为展开状态
         * **/
        sideFlexible: (status) => {
            console.log('当前导航状态', status);
            const eleIcon = $(`#${FLEXIBLE_ICON}`)
            const windowWidth = $win.width()
            if (status) {
                // 切换为收缩
                eleIcon.removeClass(ICON.spread).addClass(ICON.shrink);
                if (common.screen() >= common.SIZE_NO.md) {
                    app.addClass(SIDE_SHRINK_SM);
                    layout.shadeConfig["--theme-expand-left"] = '0px'
                    layout.shadeConfig.width = `${windowWidth}px`
                    sideContract(false)

                } else {
                    app.addClass(SIDE_SHRINK)
                    $(`.${PTADMIN_NAV} .layui-side-scroll`).css('width', '60px')
                    layout.shadeConfig["--theme-expand-left"] = '60px'
                    layout.shadeConfig.width = `${windowWidth - 60}px`
                    sideContract(true)
                }
                app.removeClass(SIDE_SPREAD_SM);
            } else {
                // 切换为展开
                eleIcon.removeClass(ICON.shrink).addClass(ICON.spread);
                if (common.screen() >= common.SIZE_NO.md) {
                    app.addClass(SIDE_SPREAD_SM)
                }
                layout.shadeConfig["--theme-expand-left"] = '220px'
                layout.shadeConfig.width = `${windowWidth - 220}px`
                sideContract(false)
                $(`.${PTADMIN_NAV} .layui-side-scroll`).css('width', '200px')
                app.removeClass(SIDE_SHRINK_SM).removeClass(SIDE_SHRINK)
            }
        },

        /**  打开全屏  **/
        fullScreen: function () {
            const ele = document.documentElement
            const reqFullScreen = ele['requestFullScreen'] || ele['webkitRequestFullScreen'] || ele['mozRequestFullScreen'] || ele['msRequestFullscreen']
            if (typeof reqFullScreen !== 'undefined' && reqFullScreen) {
                reqFullScreen.call(ele);
            }
        },

        /**  退出全屏  **/
        exitScreen: function () {
            const ele = document.documentElement
            if (document.exitFullscreen) {
                document.exitFullscreen().then(() => { })
            } else if (ele['reqFullScreen']) {
                ele['mozCancelFullScreen']();
            } else if (ele['webkitCancelFullScreen']) {
                ele['webkitCancelFullScreen']();
            } else if (ele['msExitFullscreen']) {
                ele['msExitFullscreen']();
            }
        },
        /**  查找指定的iframe  **/
        findBody: function (id = 0) {
            return $(`#${IFRAME_BODY}`).find(`.${IFRAME_BODY_ITEM}`).eq(id || 0)
        },
        /**  激活当前iframe  **/
        activeBody: function (id, options = {}) {
            const obj = layout.findBody(id)
            obj.addClass(SHOW).siblings().removeClass(SHOW);
            // 执行模块事件通知
            layui.event.call(this, MOD_NAME, 'tabsPage({*})', {
                url: options.url,
                text: options.text
            });
        },
        /**  当前活动标签  **/
        refresh: function (id, url = undefined) {
            const length = $(`#${IFRAME_BODY}`).find('iframe').length;
            if (id >= length) {
                id = length - 1;
            }
            const iframe = layout.findBody(id).find(".ptadmin_iframe")[0];
            iframe.onload = function () {
                common.loadingClose()
                layout.closeShrinkNav()
            }
            common.loading(layout.shadeConfig)
            if (url) {
                iframe.contentWindow.location.href = url
            } else {
                iframe.contentWindow.location.reload()
            }
        },

        /** 选项卡滚动 */
        tabScroll: function (idx) {
            const obj = $(`#${TAB_HEADER}`).find('li').eq(idx)
            const tabBox = $(`#${TAB_HEADER}`)
            // 获取包裹盒子 tabBox.offsetWidth
            const tabBoxWidth = tabBox.outerWidth()
            const currentOffSetLeft = obj[0].offsetLeft
            const currentOffSetWidth = obj[0].offsetWidth
            // 获取到当前点击元素的 offsetLeft  - 包裹盒子 offsetWidth 的一半 + 当前点击元素 offsetWidth 的一半
            const scrollLeft = currentOffSetLeft - tabBoxWidth / 2 + currentOffSetWidth / 2
            tabBox.animate({
                scrollLeft,
            }, 100);
        },
        /** 关闭选项卡右键操作卡片 */
        closeTabAction: function () {
            action_ele.fadeOut(300)
        },

        /** 关闭弹窗 */
        closeShrinkNav: function () {
            if (app.hasClass(SIDE_SHRINK)) {
                $('.layui-nav-itemed').removeClass('layui-nav-itemed')
            }
        }
    }

    /** 收缩状态下左侧导航展示方式 */
    const sideContract = function (status) {
        const ptadminSideMenu = $(`.${SIDE_SHRINK}`).find(`#${PTADMIN_SIDE_MENU}`)
        const sideScroll = $(`.${SIDE_SHRINK}`).find('.layui-side-scroll')
        const externalNavChild = $(`#${PTADMIN_SIDE_MENU}`).children('.layui-nav-item ').children('.layui-nav-child')
        if (status) {
            // 关闭手风琴效果
            ptadminSideMenu.removeAttr('lay-shrink')
            // 超出设置可见
            sideScroll.css('overflow-x', 'visible')
            // 新增class
            externalNavChild.addClass(PTADMIN_SHRINK_NAV)
        } else {
            // 开启手风琴效果
            ptadminSideMenu.attr('lay-shrink', 'all')
            // 超出设置不可见
            sideScroll.css('overflow-x', 'hidden')
            // 移除新增class
            externalNavChild.removeClass(PTADMIN_SHRINK_NAV)
        }
    }

    /** 左侧导航跟随联动 */
    const setRouterNav = function (currentDom) {
        const $currentDom = $(currentDom)
        const router = $currentDom.attr('lay-id')
        if ($(currentDom).is(`.${LAYUI_ACTIVE}`)) {
            const navDoms = $(`#${PTADMIN_SIDE_MENU}`).find('[ptadmin-href]')
            $.each(navDoms, function (idx, item) {
                const $item = $(item)
                if ($item.attr('ptadmin-href') === router) {
                    $item.parent().addClass(LAYUI_ACTIVE)
                } else {
                    $item.parent().removeClass(LAYUI_ACTIVE)
                }
            })
        }
    }

    /** 循环关闭标签删除元素 */
    const deleteTabs = function (removes, dom) {
        const activeTab = dom || layout.rightClickTab
        // 确保元素从后删除
        const $tabs = layout.allTabsDom
        removes.reverse().forEach(function (index) {
            $tabs.eq(index).remove();
            layout.findBody(index).remove()
        });
        setRouterNav(activeTab)
    }

    /** 关闭左侧或者右侧标签 */
    const closeTabsOutsideRange = function (isRight) {
        const $tabs = layout.allTabsDom
        const idx = layout.rightClickTab.index()  // 当前tab下标
        const indicesToRemove = [];
        let activeTab = undefined
        if (!layout.rightClickTab.is(`.${LAYUI_ACTIVE}`)) {
            /** 当关闭的左侧元素存在激活项时，激活当前选择项，并关闭其它 */
            const prevAllDom = isRight ? layout.rightClickTab.nextAll() : layout.rightClickTab.prevAll()
            const isActive = prevAllDom.filter(`.${LAYUI_ACTIVE}`)
            if (isActive.length > 0) {
                activeTab = layout.rightClickTab
                activeTab.addClass(LAYUI_ACTIVE).siblings().removeClass(LAYUI_ACTIVE)
                layout.activeBody(idx)
            } else {
                // 当关闭非激活项左侧标签，并且左侧没有激活项，保留当前激活页面，关闭左侧其它tabs
                activeTab = layout.rightClickTab.siblings(`.${LAYUI_ACTIVE}`)
                // 当前需要激活的下标
                const i = activeTab.index()
                activeTab.addClass(LAYUI_ACTIVE).siblings().removeClass(LAYUI_ACTIVE)
                layout.activeBody(i)
            }
        }

        // 循环删除元素
        $tabs.each(function (i) {
            if (idx !== i && i > 0) {
                if (i > idx && isRight) {
                    indicesToRemove.push(i);
                } else if (i < idx && !isRight) {
                    indicesToRemove.push(i);
                }
            }
        });
        deleteTabs(indicesToRemove)
    }

    // 管理后台事件集合
    const events = {
        /** 侧边伸缩 */
        flexible: function (obj) {
            const isSpread = obj.find(`#${FLEXIBLE_ICON}`).hasClass(ICON.spread);
            layout.sideFlexible(isSpread);
        },
        /** 页面刷新 **/
        refresh: function () {
            const obj = $(`#${TAB_HEADER}`).find('li')
            let i = 0
            for (i; i < obj.length; i++) {
                if ($(obj[i]).hasClass(LAYUI_ACTIVE)) {
                    break
                }
            }
            layout.refresh(i)
        },

        /** 全屏 **/
        fullscreen: function (obj) {
            const SCREEN_FULL = 'layui-icon-screen-full'
            const SCREEN_REST = 'layui-icon-screen-restore'
            const iconElem = obj.children("i");
            if (iconElem.hasClass(SCREEN_FULL)) {
                layout.fullScreen();
                iconElem.addClass(SCREEN_REST).removeClass(SCREEN_FULL);
            } else {
                layout.exitScreen();
                iconElem.addClass(SCREEN_FULL).removeClass(SCREEN_REST);
            }
        },

        /** 点击遮罩层 **/
        shade: function () {
            layout.sideFlexible(true);
        },

        /** 退出登录 **/
        logout: function (obj) {
            let url = obj.attr('data-url')
            if (!url) {
                const { origin, pathname } = location
                url = origin + '/logout'
            }
            common.get(url, {}, function (res) {
                if (res.code === 0) {
                    location.href = res.data.url
                } else {
                    layer.msg(res.message, { icon: 2 });
                }
            })
        },
        password: function (obj) {
            const url = $(obj).attr('data-url')
            common.formOpen(url, '修改密码', { area: ['400px', '300px'] })
        },

        // 关闭当前标签
        closeThisTab: function () {
            const idx = layout.rightClickTab.index()
            if (!idx) return
            // 关闭的为激活项
            if (layout.rightClickTab.is(`.${LAYUI_ACTIVE}`)) {
                layout.rightClickTab.prev().addClass(LAYUI_ACTIVE)
                layout.findBody(idx).prev().addClass(SHOW)
                setRouterNav(layout.rightClickTab.prev())
            }
            layout.rightClickTab.remove()
            layout.findBody(idx).remove()
        },

        // 关闭其它标签
        closeOtherTab: function () {
            const idx = layout.rightClickTab.index()
            const indicesToRemove = [];
            const $tabs = layout.allTabsDom
            // 当不为激活标签时 设置当前元素为激活
            if (!layout.rightClickTab.is(`.${LAYUI_ACTIVE}`)) {
                layout.rightClickTab.addClass(LAYUI_ACTIVE).siblings().removeClass(LAYUI_ACTIVE)
                layout.activeBody(idx)
            }
            // 循环获取删除元素下标删除元素
            $tabs.each(function (i) {
                if (idx !== i && i > 0) {
                    indicesToRemove.push(i);
                }
            });
            deleteTabs(indicesToRemove)
        },

        /** 关闭全部标签 */
        closeAllTab: function () {
            const indicesToRemove = [];
            const $tabs = layout.allTabsDom
            let activeTab = undefined
            // 循环删除元素
            $tabs.each(function (i, item) {
                const $item = $(item)
                if (i > 0) {
                    indicesToRemove.push(i);
                } else {
                    activeTab = $item
                }
            });
            layout.activeBody(0)
            activeTab.addClass(LAYUI_ACTIVE)
            deleteTabs(indicesToRemove, activeTab)

        },

        /** 关闭右侧 */
        closeRightTab: function () {
            closeTabsOutsideRange(true)
        },

        /** 关闭左侧 */
        closeLeftTab: function () {
            closeTabsOutsideRange(false)
        },
    }



    /**
     * 页面事件监听
     */
    $body.on("click", `*[${PTADMIN_EVENT}]`, function () {
        const $this = this
        const event = $($this).attr(`${PTADMIN_EVENT}`)
        if (events[event] && typeof events[event] === 'function') {
            events[event].call($this, $($this))
            return
        }
        console.error(`未找到事件${event}`)
    })

    /***
     * 提示信息事件
     */
    $body.on("mouseenter", "*[ptadmin-tips]", function () {
        const $this = $(this);
        if ($this.parent().hasClass('layui-nav-item') && !app.hasClass(SIDE_SHRINK)) return;
        const tips = $this.attr('ptadmin-tips')
        const offset = $this.attr('ptadmin-offset') || 40
        const direction = $this.attr('ptadmin-direction') || 1
        const index = layer.tips(tips, this, {
            tips: direction,
            time: -1,
            success: function (obj, index) {
                if (offset) {
                    obj.css('margin-left', offset + 'px');
                }
            }
        });
        $this.data('index', index);
    }).on('mouseleave', "*[ptadmin-tips]", function () {
        layer.close($(this).data('index'));
    })

    /** 监听按键 **/
    document.addEventListener('keydown', (e) => {
        if (((e.metaKey && common.isMac()) || e.ctrlKey) && e.key === 'k') {
            e.preventDefault()
            // 弹出搜索框功能
            console.log('打开搜索框')
        }
    })

    /**
     * 窗口resize事件
     * @type {layui.data.resizeSystem}
     */
    layui.data.resizeSystem = function () {
        layer.closeAll('tips');
        if (!window['resize_lock']) {
            setTimeout(function () {
                layout.sideFlexible(common.screen() >= common.SIZE_NO.md);
                delete window['resize_lock'];
            }, 100);
        }
        window['resize_lock'] = true;
    }

    // 监听窗口变动事件
    $win.on("resize", layui.data.resizeSystem)

    // 更多操作项目事件
    element.on('nav(ptadmin-page-nav)', function (elem) {
        const dd = elem.parent();
        dd.removeClass(LAYUI_ACTIVE)
        dd.parent().removeClass("layui-show")
    })
    // 侧边栏导航点击事件
    element.on(`nav(${PTADMIN_SIDE_MENU})`, function (elem) {
        if (app.hasClass(SIDE_SHRINK)) {
            const itemedElem = elem.closest('li').siblings('.layui-nav-item ')
            itemedElem.removeClass('layui-nav-itemed')
            itemedElem.find('.layui-nav-itemed').removeClass('layui-nav-itemed')
        }
    })

    // 选项卡切换事件
    element.on(`tab(${LAYOUT_TABS})`, function (data) {
        layout.activeBody(data.index)
        layout.tabScroll(data.index)
        action_ele.fadeOut(300)
        setRouterNav(this)
    })

    // 选项卡右键事件
    $body.on("contextmenu", `#${TAB_HEADER}>li`, function (event) {
        // 阻止浏览器默认事件
        event.preventDefault();
        action_ele.fadeIn(300)
        action_ele.css('top', event.pageY + 25)
        action_ele.css('left', event.pageX)
        layout.allTabsDom = $(`#${TAB_HEADER} li`)
        layout.rightClickTab = $(this)
        const disabledRefresh = $(this).is(`.${LAYUI_ACTIVE}`)
        const rightAllDom = $(this).nextAll()
        // 刷新只存在当前打开标签
        if (!disabledRefresh) {
            action_ele.find('[type="refresh"]').addClass('disabled').removeAttr(`${PTADMIN_EVENT}`)
        } else {
            action_ele.find('[type="refresh"]').removeClass('disabled').attr(`${PTADMIN_EVENT}`, 'refresh')
        }
        // 当下标为0时为首页，首页不允许关闭
        if ($(this).index()) {
            action_ele.find('[type="close"],[type="left"]').removeClass('disabled')
            action_ele.find('[type="close"]').attr(`${PTADMIN_EVENT}`, 'closeThisTab')
            action_ele.find('[type="left"]').attr(`${PTADMIN_EVENT}`, 'closeLeftTab')
        } else {
            action_ele.find('[type="close"],[type="left"]').addClass('disabled').removeAttr(`${PTADMIN_EVENT}`)
        }
        // 当右侧不存在标签时不允许操作关闭右侧标签
        if (rightAllDom.length > 0) {
            action_ele.find('[type="right"]').removeClass('disabled').attr(`${PTADMIN_EVENT}`, 'closeRightTab')
        } else {
            action_ele.find('[type="right"]').addClass('disabled').removeAttr(`${PTADMIN_EVENT}`)
        }
    })

    // 关闭选项卡弹出卡片事件
    $body.on('click', function (event) {
        layout.closeTabAction()
    })

    // 选项卡删除事件
    element.on(`tabDelete(${LAYOUT_TABS})`, function (data) {
        layout.findBody(data.index).remove()
    })

    // 初始化事件
    !(function () {
        // 移动端默认收起侧边栏
        if (common.screen() >= common.SIZE_NO.md) {
            layout.sideFlexible(true)
        }
    })()

    exports(MOD_NAME, layout)
})
