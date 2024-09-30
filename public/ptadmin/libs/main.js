/** 管理后台主入口程序 ***/
layui.extend({
    layout: 'layout',
}).define(['layout', 'element', 'common'], function (exports) {
    const MOD_NAME = "main"
    const { layout, common, element, $ } = layui
    const APP_TAB_HEADER = "ptadmin_app_tab_header"
    const LAYOUT_TABS = "ptadmin-layout-tabs"

    const TAB_PAGE_MAP = new Map()

    /**
     * 打开新的标签页面
     * @param title
     * @param url
     * @param icon
     * @param id
     */
    const openTab = function (title, url, icon = '', id = '') {
        const tab_header = $(`#${APP_TAB_HEADER}>li`)
        let isExists = false
        for (let i = 0; i < tab_header.length; i++) {
            const $this = $(tab_header[i])
            const id = $this.attr('lay-id')
            layout.currentBodyIndex = i
            if (id === url) {
                isExists = true
                layout.closeShrinkNav()
                break
            }
        }

        if (!isExists) {
            let div = common.create('div', { className: 'ptadmin-iframe-item ptadmin-show' })
            let iframe = common.create('iframe', {
                src: url,
                frameborder: 0,
                className: 'ptadmin_iframe',
                onload: function () {
                    common.loadingClose()
                    layout.closeShrinkNav()
                }
            })

            common.loading(layout.shadeConfig)
            div.appendChild(iframe)
            $(`#iframe_body`)[0].appendChild(div)
            iframe = div = null
            element.tabAdd(LAYOUT_TABS, {
                id: url,
                url: url,
                title: title,
                change: true
            })
            layout.currentBodyIndex = tab_header.length
        }

        // 激活当前iframe
        layout.activeBody(layout.currentBodyIndex)
        // 切换tab
        element.tabChange(LAYOUT_TABS, url)
        // 选项卡滚动
        layout.tabScroll(layout.currentBodyIndex)
    }
    exports(MOD_NAME, {
        openTab
    })
})
