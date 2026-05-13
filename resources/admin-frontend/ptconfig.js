/** @type {Window['ptconfig']} */
window.ptconfig = {
    // 应用基础信息
    title: 'PTAdmin Console',
    shortTitle: 'PTAdmin',
    description: 'PTAdmin 后台管理控制台',
    locale: 'zh-CN',
    logo: '',
    favicon: '',

    // 顶部区域配置
    header: {
        // 是否显示顶部品牌区
        showBrand: true,
        // 是否显示侧边栏折叠按钮
        showCollapse: true,
        // 混合布局下是否在头部显示一级导航
        showPrimaryNav: true,
        // 是否显示消息通知
        showMessage: true,
        // 是否显示语言切换
        showLanguage: false,
        // 是否显示明暗模式切换
        showThemeSwitch: true,
        // 是否显示全屏切换
        showFullScreen: true,
        // 是否显示用户区
        showUser: true,
    },

    // 登录页配置
    login: {
        // 是否显示左侧品牌介绍区
        showSidePanel: true,
        // 登录页标题，留空时回退到全局 title
        title: '',
        // 欢迎文案
        welcomeText: '欢迎登录',
        // 英文标题
        englishTitle: 'User Login',
        // 描述文案，留空时回退到全局 description
        description: '',
        // 登录页 Logo，留空时回退到全局 logo；若全局也为空，则使用内置品牌 Logo
        logo: '',
        // 登录页背景图，支持相对路径或完整地址；留空时使用内置背景
        backgroundImage: '',
        // 左侧面板背景图，留空时使用内置面板背景
        panelImage: '',
        // 登录帮助文案，留空时开发环境会显示默认演示提示
        helpText: '',
        // 帮助链接，配置后会在登录页展示“登录帮助”入口
        helpLink: '',
        // 是否默认记住账号
        rememberAccount: true,
    },

    // 面包屑配置
    breadcrumb: {
        visible: true,
        showHome: true,
        showCurrent: true,
        // 最大显示层级数，0 表示不限制
        maxCount: 0,
    },

    // 标签栏配置
    tab: {
        visible: true,
        showDropdown: true,
        closable: true,
        allowCloseCurrent: true,
        allowCloseOther: true,
        allowCloseLeftRight: true,
    },

    // 消息中心配置
    message: {
        defaultTab: 'notice',
        popoverWidth: 360,
        emptyText: '暂无消息',
        showReadAll: true,
        showViewAll: false,
        viewAllPath: '',
        labels: {
            notice: '通知',
            message: '消息',
            todo: '待办',
        },
    },

    // 页脚配置
    footer: {
        visible: false,
        text: 'Pangtou Admin',
        copyright: '',
        links: [],
    },

    // 当前登录用户区域配置
    user: {
        nickname: '管理员',
        avatar: '',
        profilePath: '/_account/profile',
        themePath: '',
        showProfile: true,
        showThemeEntry: true,
        showAvatar: false,
        showName: true,
        profileLabel: '个人资料',
        themeLabel: '主题设置',
        logoutLabel: '退出登录',
    },

    // 接口与路由
    // 为空时，运行时会按当前访问地址推导接口前缀
    baseURL: undefined,
    // 为空时，运行时会按 {baseURL}/upload 自动推导上传地址
    uploadURL: undefined,
    // 仅请求真实后端
    requestMode: 'http',
    // 云平台入口开关。显式配置为 true 时禁用；未配置或为 false 时，创始人可见。
    // cloud_disabled: true,
    bootstrap: {
        loginEndpoint: '/login',
        profileEndpoint: '/auth/profile',
        frontendsEndpoint: '/auth/frontends',
        resourcesEndpoint: '/auth/resources',
    },
    // 留空时按当前访问目录自动推导，例如 /39PLhHjO/
    basePath: '',
    routerHistory: 'hash',
    // 使用后端权限树
    routeMode: 'backend',
    timeout: 10000,

    // 布局与交互
    layout: 'left',
    animation: 'el-fade-in',
    dark: false,
    isAccordion: false,
    asideWidth: 220,

    // 主题配置
    // 颜色数组统一按 [浅色模式, 深色模式] 组织；为空字符串时表示沿用主题预设
    theme: {
        preset: 'classic-blue',
        primaryColor: '#2274ff',
        asideBgColor: ['', ''],
        asideTextColor: ['', ''],
        asideActiveColor: ['', ''],
        asideHoverColor: ['', ''],
        headerBgColor: ['', ''],
        headerTextColor: ['', ''],
        tabsBgColor: ['', ''],
        tabsActiveColor: ['', ''],
    },

    // 全局能力开关
    capabilities: {
        tenant: false,
        organization: false,
        data_scope: false,
    },
}
