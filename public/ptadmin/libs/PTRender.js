layui.define(function (exports) {
    const MOD_NAME = "PTRender"
    const { $ } = layui

    // 某些属性为了书写方便做了一下转换操作
    const AttributeTran = {
        event: 'ptadmin-event'
    }

    const getAttribute = function (config) {
        let attr = [];
        for (let i in config) {
            if (config[i] === '' || ['tagName', 'text', 'icon'].indexOf(i) !== -1) {
                continue;
            }
            let getAttribute = AttributeTran[i] || i;
            attr.push(`${getAttribute}="${config[i]}"`);
        }

        return attr.join(" ");
    }

    // 简易的标签渲染，这里只是为了内部渲染需求
    const render = function (option = {}) {
        let config = {
            tagName: 'a',
            class: '',
            event: '',
            icon: '',
            target: '',
            href: '',
            text: '',
        }

        $.extend(config, option)
        let icon = '';
        if (config.icon !== '') {
            icon = `<i class="${config.icon}"></i>`;
        }
        if (config['event'] !== "") {
            config['lay-event'] = config['event']
        }
        return `<${config.tagName} ${getAttribute(config)}>${icon}${config.text}</${config.tagName}>`;
    }

    const PTRender = {
        render: render
    };

    exports(MOD_NAME, PTRender)
});
