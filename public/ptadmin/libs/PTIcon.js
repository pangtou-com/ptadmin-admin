/**
 * ICON 图标实现
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2022/04/09.
 */
layui.define(function (exports) {
    "use strict";
    const { common, layer } = layui
    const MOD_NAME = "PTIcon"
    const dataBase = {
        iconsData: [
            {
                title: 'Layui-icon',
                baseClass: 'layui-icon',
                icons: [
                    {
                        categorizeTitle: 'Layui-icon列表',
                        result: [
                            { icon: 'layui-icon-github', iconName: 'Github' },
                            { icon: 'layui-icon-moon', iconName: '月亮' },
                            { icon: 'layui-icon-fail', iconName: '错误' },
                            { icon: 'layui-icon-success', iconName: '成功' },
                            { icon: 'layui-icon-question', iconName: '问号' },
                            { icon: 'layui-icon-lock', iconName: '锁定' },
                            { icon: 'layui-icon-eye', iconName: '显示' },
                            { icon: 'layui-icon-eye-invisible', iconName: '隐藏' },
                            { icon: 'layui-icon-clear', iconName: '删除' },
                            { icon: 'layui-icon-backspace', iconName: '退格' },
                            { icon: 'layui-icon-disabled', iconName: '禁用' },
                            { icon: 'layui-icon-tips-fill', iconName: '感叹号/提示' },
                            { icon: 'layui-icon-test', iconName: '测试/K线图' },
                            { icon: 'layui-icon-music', iconName: '音乐/音符' },
                            { icon: 'layui-icon-chrome', iconName: 'Chrome' },
                            { icon: 'layui-icon-firefox', iconName: 'Firefox' },
                            { icon: 'layui-icon-edge', iconName: 'Edge' },
                            { icon: 'layui-icon-ie', iconName: 'IE' },
                            { icon: 'layui-icon-heart-fill', iconName: '实心' },
                            { icon: 'layui-icon-heart', iconName: '空心' },
                            { icon: 'layui-icon-light', iconName: '太阳/明亮' },
                            { icon: 'layui-icon-time', iconName: '时间/历史' },
                            { icon: 'layui-icon-bluetooth', iconName: '蓝牙' },
                            { icon: 'layui-icon-at', iconName: '@艾特' },
                            { icon: 'layui-icon-mute', iconName: '静音' },
                            { icon: 'layui-icon-mike', iconName: '录音/麦克风' },
                            { icon: 'layui-icon-key', iconName: '密钥/钥匙' },
                            { icon: 'layui-icon-gift', iconName: '礼物/活动' },
                            { icon: 'layui-icon-email', iconName: '邮箱' },
                            { icon: 'layui-icon-rss', iconName: 'RSS' },
                            { icon: 'layui-icon-wifi', iconName: 'WIFI' },
                            { icon: 'layui-icon-logout', iconName: '退出/注销' },
                            { icon: 'layui-icon-android', iconName: 'Android 安卓' },
                            { icon: 'layui-icon-ios', iconName: 'Apple IOS 苹果' },
                            { icon: 'layui-icon-windows', iconName: 'Windows' },
                            { icon: 'layui-icon-transfer', iconName: '穿梭框' },
                            { icon: 'layui-icon-service', iconName: '客服' },
                            { icon: 'layui-icon-subtraction', iconName: '减' },
                            { icon: 'layui-icon-addition', iconName: '加' },
                            { icon: 'layui-icon-slider', iconName: '滑块' },
                            { icon: 'layui-icon-print', iconName: '打印' },
                            { icon: 'layui-icon-export', iconName: '导出' },
                            { icon: 'layui-icon-cols', iconName: '列' },
                            { icon: 'layui-icon-screen-restore', iconName: '退出全屏' },
                            { icon: 'layui-icon-screen-full', iconName: '全屏' },
                            { icon: 'layui-icon-rate-half', iconName: '星星-半心' },
                            { icon: 'layui-icon-rate', iconName: '星星-空心' },
                            { icon: 'layui-icon-rate-solid', iconName: '星星-实心' },
                            { icon: 'layui-icon-cellphone', iconName: '手机' },
                            { icon: 'layui-icon-vercode', iconName: '验证码' },
                            { icon: 'layui-icon-login-wechat', iconName: '微信' },
                            { icon: 'layui-icon-login-qq', iconName: 'QQ' },
                            { icon: 'layui-icon-login-weibo', iconName: '微博' },
                            { icon: 'layui-icon-password', iconName: '密码' },
                            { icon: 'layui-icon-username', iconName: '用户名' },
                            { icon: 'layui-icon-refresh-3', iconName: '刷新-粗' },
                            { icon: 'layui-icon-auz', iconName: '授权' },
                            { icon: 'layui-icon-spread-left', iconName: '左向右伸缩菜单' },
                            { icon: 'layui-icon-shrink-right', iconName: '右向左伸缩菜单' },
                            { icon: 'layui-icon-snowflake', iconName: '雪花' },
                            { icon: 'layui-icon-tips', iconName: '提示' },
                            { icon: 'layui-icon-note', iconName: '便签' },
                            { icon: 'layui-icon-home', iconName: '主页' },
                            { icon: 'layui-icon-senior', iconName: '高级' },
                            { icon: 'layui-icon-refresh', iconName: '刷新' },
                            { icon: 'layui-icon-refresh-1', iconName: '刷新' },
                            { icon: 'layui-icon-flag', iconName: '旗帜' },
                            { icon: 'layui-icon-theme', iconName: '主题' },
                            { icon: 'layui-icon-notice', iconName: '消息-通知' },
                            { icon: 'layui-icon-website', iconName: '网站' },
                            { icon: 'layui-icon-console', iconName: '控制台' },
                            { icon: 'layui-icon-face-surprised', iconName: '表情-惊讶' },
                            { icon: 'layui-icon-set', iconName: '设置-空心' },
                            { icon: 'layui-icon-template-1', iconName: '模板' },
                            { icon: 'layui-icon-app', iconName: '应用' },
                            { icon: 'layui-icon-template', iconName: '模板' },
                            { icon: 'layui-icon-praise', iconName: '赞' },
                            { icon: 'layui-icon-tread', iconName: '踩' },
                            { icon: 'layui-icon-male', iconName: '男' },
                            { icon: 'layui-icon-female', iconName: '女' },
                            { icon: 'layui-icon-camera', iconName: '相机-空心' },
                            { icon: 'layui-icon-camera-fill', iconName: '相机-实心' },
                            { icon: 'layui-icon-more', iconName: '菜单-水平' },
                            { icon: 'layui-icon-more-vertical', iconName: '菜单-垂直' },
                            { icon: 'layui-icon-rmb', iconName: '金额-人民币' },
                            { icon: 'layui-icon-dollar', iconName: '金额-美元' },
                            { icon: 'layui-icon-diamond', iconName: '砖石-等级' },
                            { icon: 'layui-icon-fire', iconName: '火' },
                            { icon: 'layui-icon-return', iconName: '返回' },
                            { icon: 'layui-icon-location', iconName: '位置-地图', },
                            { icon: 'layui-icon-read', iconName: '办公-阅读' },
                            { icon: 'layui-icon-survey', iconName: '调查' },
                            { icon: 'layui-icon-face-smile', iconName: '表情-微笑' },
                            { icon: 'layui-icon-face-cry', iconName: '表情-哭泣' },
                            { icon: 'layui-icon-cart-simple', iconName: '购物车' },
                            { icon: 'layui-icon-cart', iconName: '购物车' },
                            { icon: 'layui-icon-next', iconName: '下一页' },
                            { icon: 'layui-icon-prev', iconName: '上一页' },
                            { icon: 'layui-icon-upload-drag', iconName: '上传-空心-拖拽' },
                            { icon: 'layui-icon-upload', iconName: '上传-实心' },
                            { icon: 'layui-icon-download-circle', iconName: '下载-圆圈' },
                            { icon: 'layui-icon-component', iconName: '组件' },
                            { icon: 'layui-icon-file-b', iconName: '文件-粗' },
                            { icon: 'layui-icon-user', iconName: '用户' },
                            { icon: 'layui-icon-find-fill', iconName: '发现-实心' },
                            { icon: 'layui-icon-loading', iconName: 'loading' },
                            { icon: 'layui-icon-loading-1', iconName: 'loading' },
                            { icon: 'layui-icon-add-1', iconName: '添加' },
                            { icon: 'layui-icon-play', iconName: '播放' },
                            { icon: 'layui-icon-pause', iconName: '暂停' },
                            { icon: 'layui-icon-headset', iconName: '音频-耳机' },
                            { icon: 'layui-icon-video', iconName: '视频' },
                            { icon: 'layui-icon-voice', iconName: '语音-声音' },
                            { icon: 'layui-icon-speaker', iconName: '消息-通知-喇叭', },
                            { icon: 'layui-icon-fonts-del', iconName: '删除线', },
                            { icon: 'layui-icon-fonts-code', iconName: '代码' },
                            { icon: 'layui-icon-fonts-html', iconName: 'HTML' },
                            { icon: 'layui-icon-fonts-strong', iconName: '字体加粗' },
                            { icon: 'layui-icon-unlink', iconName: '删除链接' },
                            { icon: 'layui-icon-picture', iconName: '图片' },
                            { icon: 'layui-icon-link', iconName: '链接' },
                            { icon: 'layui-icon-face-smile-b', iconName: '表情-笑-粗' },
                            { icon: 'layui-icon-align-left', iconName: '左对齐' },
                            { icon: 'layui-icon-align-right', iconName: '右对齐' },
                            { icon: 'layui-icon-align-center', iconName: '居中对齐' },
                            { icon: 'layui-icon-fonts-u', iconName: '字体-下划线' },
                            { icon: 'layui-icon-fonts-i', iconName: '字体-斜体' },
                            { icon: 'layui-icon-tabs', iconName: 'Tabs 选项卡' },
                            { icon: 'layui-icon-radio', iconName: '单选框-选中' },
                            { icon: 'layui-icon-circle', iconName: '单选框-候选' },
                            { icon: 'layui-icon-edit', iconName: '编辑' },
                            { icon: 'layui-icon-share', iconName: '分享' },
                            { icon: 'layui-icon-delete', iconName: '删除' },
                            { icon: 'layui-icon-form', iconName: '表单' },
                            { icon: 'layui-icon-cellphone-fine', iconName: '手机-细体' },
                            { icon: 'layui-icon-dialogue', iconName: '聊天 对话 沟通' },
                            { icon: 'layui-icon-fonts-clear', iconName: '文字格式化' },
                            { icon: 'layui-icon-layer', iconName: '窗口' },
                            { icon: 'layui-icon-date', iconName: '日期' },
                            { icon: 'layui-icon-water', iconName: '水 下雨' },
                            { icon: 'layui-icon-code-circle', iconName: '代码-圆圈' },
                            { icon: 'layui-icon-carousel', iconName: '轮播组图' },
                            { icon: 'layui-icon-prev-circle', iconName: '翻页' },
                            { icon: 'layui-icon-layouts', iconName: '布局' },
                            { icon: 'layui-icon-util', iconName: '工具' },
                            { icon: 'layui-icon-templeate-1', iconName: '选择模板' },
                            { icon: 'layui-icon-upload-circle', iconName: '上传-圆圈' },
                            { icon: 'layui-icon-tree', iconName: '树' },
                            { icon: 'layui-icon-table', iconName: '表格' },
                            { icon: 'layui-icon-chart', iconName: '图表' },
                            { icon: 'layui-icon-chart-screen', iconName: '图表 报表 屏幕' },
                            { icon: 'layui-icon-engine', iconName: '引擎' },
                            { icon: 'layui-icon-triangle-d', iconName: '下三角' },
                            { icon: 'layui-icon-triangle-r', iconName: '右三角' },
                            { icon: 'layui-icon-file', iconName: '文件' },
                            { icon: 'layui-icon-set-sm', iconName: '设置-小型' },
                            { icon: 'layui-icon-reduce-circle', iconName: '减少-圆圈' },
                            { icon: 'layui-icon-add-circle', iconName: '添加-圆圈' },
                            { icon: 'layui-icon-404', iconName: '404' },
                            { icon: 'layui-icon-about', iconName: '关于' },
                            { icon: 'layui-icon-up', iconName: '箭头-向上' },
                            { icon: 'layui-icon-down', iconName: '箭头-向下' },
                            { icon: 'layui-icon-left', iconName: '箭头-向左' },
                            { icon: 'layui-icon-right', iconName: '箭头-向右' },
                            { icon: 'layui-icon-circle-dot', iconName: '圆点' },
                            { icon: 'layui-icon-search', iconName: '搜索' },
                            { icon: 'layui-icon-set-fill', iconName: '设置-实心' },
                            { icon: 'layui-icon-group', iconName: '群组' },
                            { icon: 'layui-icon-friends', iconName: '好友' },
                            { icon: 'layui-icon-reply-fill', iconName: '回复 评论 实心' },
                            { icon: 'layui-icon-menu-fill', iconName: '菜单 隐身 实心' },
                            { icon: 'layui-icon-log', iconName: '记录' },
                            { icon: 'layui-icon-picture-fine', iconName: '图片细体' },
                            { icon: 'layui-icon-face-smile-fine', iconName: '表情-笑-细体' },
                            { icon: 'layui-icon-list', iconName: '列表' },
                            { icon: 'layui-icon-release', iconName: '发布-纸飞机' },
                            { icon: 'layui-icon-ok', iconName: '对 OK' },
                            { icon: 'layui-icon-help', iconName: '帮助' },
                            { icon: 'layui-icon-chat', iconName: '客服' },
                            { icon: 'layui-icon-top', iconName: '置顶' },
                            { icon: 'layui-icon-star', iconName: '收藏-空心' },
                            { icon: 'layui-icon-star-fill', iconName: '收藏-实心' },
                            { icon: 'layui-icon-close-fill', iconName: '关闭-实心' },
                            { icon: 'layui-icon-close', iconName: '关闭-空心' },
                            { icon: 'layui-icon-ok-circle', iconName: '正确' },
                            { icon: 'layui-icon-add-circle-fine', iconName: '添加-圆圈-细体' },
                        ]
                    }
                ]
            }
        ],
        currentInput: undefined,
        iconClass: '',
    }
    /** 输入框class */
    const ELEM_CLASS = '.layui-input-icon'
    /** 内容展示 */
    const SHOW = 'ptadmin-show-icon'
    /** 导航高亮 */
    const ACTIVE_NAV = 'active'
    /** 包裹元素class */
    const CONTAINER_CLASS = 'ptadmin-icon'
    /** 包裹元素左侧class */
    const CONTAINER_LEFT_CLASS = 'ptadmin-icon-left'
    /** 内容区域class */
    const CONTAINER_CONTENT_CLASS = 'ptadmin-icon-content'
    /** 弹出层class */
    const PTADMIN_ICON_DIALOG = 'ptadmin-icon-dialog'
    /** 包裹容器 */
    const CONTAINER = `<div class="ptadmin-icon">
                            <ul class="${CONTAINER_LEFT_CLASS}"></ul>
                            <ul class="${CONTAINER_CONTENT_CLASS}"></ul>
                      </div>`

    /** 选中元素高亮 */
    const SELECT = 'icon-item-active'
    /** 初始化数据 */
    const initData = function (iconContainer) {
        const footerBox = iconContainer.parent().siblings('.layui-layer-btn')
        const footerIcon = $(`<div class="footer-icon"></div>`)
        let nav = ''
        dataBase.iconsData.forEach((item, idx) => {
            nav += `<li class="icon-nav-item ${!idx ? ACTIVE_NAV : ''}">${item.title}</li>`
            const $ul = $(`<ul class="content-item  ${!idx ? SHOW : ''}">`);
            item.icons.forEach((res) => {
                const $categorize = $(`<li class="categorize-item">
                                            <div class="categorize-title">${res.categorizeTitle}（${res.result.length}个）</div>
                                            <ul class="icons"></ul>
                                        </li>`)
                res.result.forEach(ele => {
                    const $li = $(`
                            <li class="icon-item">
                                <i class="${item.baseClass} ${ele.icon || ''}"></i>
                                <span class="text">${ele.iconName}</span>
                            </li>
                        `)

                    $categorize.children('.icons').append($li)
                })

                $ul.append($categorize)
            });
            iconContainer.find(`.${CONTAINER_CONTENT_CLASS}`).append($ul)
        });
        iconContainer.find(`.${CONTAINER_LEFT_CLASS}`).html(nav)

        // 增加icon显示
        footerBox.append(footerIcon)
        if (dataBase.iconClass) {
            footerIcon.html(`<div class="pt-icon ${dataBase.iconClass}"><div>`)
        }
        // 点击导航
        iconContainer.on('click', '.icon-nav-item', function () {
            $(this).addClass(ACTIVE_NAV).siblings().removeClass(ACTIVE_NAV)
            const idx = $(this).index()
            $(this).parent().siblings(`.${CONTAINER_CONTENT_CLASS}`).children('.content-item').eq(idx).addClass(SHOW).siblings().removeClass(SHOW)

        })

        // 选择ICON
        iconContainer.on('click', '.icon-item', function () {
            const allIcon = iconContainer.find('.icon-item')
            dataBase.iconClass = $(this).children('i').attr('class')
            const activeDom = allIcon.filter(`.${SELECT}`)
            if (activeDom.length > 0) {
                activeDom.removeClass(SELECT)
            }
            $(this).addClass(SELECT)
            footerIcon.html(`<div class="pt-icon ${dataBase.iconClass}"><div>`)
        })
    }


    const PTIcon = {
        render: function (title) {
            if ($(ELEM_CLASS).length === 0 ) {
                return
            }
            const area = common.getArea();
            const options = {
                title,
                type: 1,
                area,
                shadeClose: true,
                content: CONTAINER,
                scrollbar: false,
                skin: 'layui-layer-lan',
                maxmin: true,
                moveOut: true,
                btn: ['确认', '关闭'],
                success: function (layero) {
                    initData(layero.find(`.${CONTAINER_CLASS}`))
                    layero.addClass(PTADMIN_ICON_DIALOG)
                },
                yes: function (index) {
                    dataBase.currentInput.val(dataBase.iconClass)
                    const iconBox = dataBase.currentInput.prev()
                    const icon = `<i class="${dataBase.iconClass}"></i>`
                    iconBox.html(icon)
                    layer.close(index)
                },
                end: function () {
                    dataBase.iconClass = ''
                }
            }

            $(ELEM_CLASS).on('click', 'span', function () {
                const $this = $(this)
                dataBase.currentInput = $this.prev()
                if (dataBase.currentInput.prev().find('i').length > 0) {
                    dataBase.iconClass = dataBase.currentInput.prev().find('i').attr('class')
                }
                layer.open(options);
            });
        },
        insert: function (data) {
            if (data) {
                data.forEach(item => {
                    dataBase.iconsData.push(item)
                });
            }
        }
    }
    exports(MOD_NAME, PTIcon);
});
