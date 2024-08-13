layui.define(function (exports) {
    const MOD_NAME = "PTRender"
    const { $ } = layui
    /**
     * 预设显示方式：
     * class 设置class属性
     * event 设置响应事件 - 可设置为空
     * icon: 设置显示图标 - 可设置为空
     * @type {{edit: {icon: string, event: string, class: string}, show: {icon: string, event: string, class: string}, del: {icon: string, event: string, class: string}}}
     */
    const options = {
        edit: {
            class: "layui-btn layui-btn-sm btn-theme",
            event: 'edit',
            icon: 'layui-icon layui-icon-edit'
        },
        del: {
            class: "layui-btn layui-btn-sm layui-bg-red",
            event: 'del',
            icon: 'layui-icon layui-icon-delete'
        },
        show: {
            class: "layui-btn layui-btn-sm layui-bg-green",
            event: 'show',
            icon: 'layui-icon layui-icon-search'
        }
    }
    // 某些属性为了书写方便做了一下转换操作
    const AttributeTran = {
        event: 'lay-event'
    }

    const paramMerge = function (config, option, type) {
        if (type !== undefined && options[type] !== undefined) {
            $.extend(config, options[type]);
        }
        $.extend(config, option);

        return config;
    }

    const getAttribute = function (config) {
        let attr = [];
        let a = 0;
        for (let i in config) {
            if (config[i] === '' || ['name', 'content', 'icon'].indexOf(i) !== -1) {
                continue;
            }
            let getAttribute = AttributeTran[i] || i;
            attr[a] = ` ${getAttribute}="${config[i]}"`;
            a++;
        }

        return attr.join("");
    }

    // 简易的标签渲染，这里只是为了内部渲染需求
    const render = function (option = {}, type = undefined ) {
        let config = {
            name: 'a',
            class: '',
            event: '',
            icon: '',
            target: '',
            href: '',
            content: '',
        }

        config = paramMerge(config, option, type);
        let icon = '';
        if (config.icon !== '') {
            icon = `<i class="${config.icon}"></i>`;
        }

        return `<${config.name} ${getAttribute(config)} > ${icon} ${config.content} </${config.name}>`;
    }

    const PTRender = {
        render: render
    };

    exports(MOD_NAME, PTRender)
});
