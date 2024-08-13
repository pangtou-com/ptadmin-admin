layui.define(['element'], function (exports) {
    const MOD_NAME = "common"
    const { $, element } = layui
    const win = $(window)

    const SIZE = { xs: 768, sm: 992, md: 1200, lg: 1600 }
    const SIZE_NO = { xs: 0, sm: 1, md: 2, lg: 3 }

    const LOADING = (config = {}) => {
        let style = ''
        if (config) {
            for (const key in config) {
                style += `${key}:${config[key]};`
            }
        }
        return [
            '<div>',
            '<div class="sk-cube-grid">',
            '<div class="sk-cube sk-cube-1"></div>',
            '<div class="sk-cube sk-cube-2"></div>',
            '<div class="sk-cube sk-cube-3"></div>',
            '<div class="sk-cube sk-cube-4"></div>',
            '<div class="sk-cube sk-cube-5"></div>',
            '<div class="sk-cube sk-cube-6"></div>',
            '<div class="sk-cube sk-cube-7"></div>',
            '<div class="sk-cube sk-cube-8"></div>',
            '<div class="sk-cube sk-cube-9"></div>',
            '</div>',
            `<div style="${style}" class="loading-shade"></div>`,
            '</div>'
        ]
    }

    const common = {
        loadingIndex: undefined,
        config: {
            base: "/" // 定义基础路径
        },
        set: function (options) {
            let that = this;
            that.config = $.extend({}, that.config, options);
            return that;
        },
        url: function (url) {
            let base = '/'
            if (this.config.base) {
                const s = this.config.base[this.config.base.length - 1];
                if (s === "/") {
                    base = this.config.base;
                } else {
                    base = this.config.base + "/";
                }
            }

            return `${base}${url}`;
        },
        // 是否为mac系统
        isMac: function () {
            return /macintosh|mac os x/i.test(navigator.userAgent)
        },

        /**  获取屏幕类型  **/
        screen: () => {
            const width = win.width()
            for (const k in SIZE) {
                const w = SIZE[k]
                if (w <= width) return SIZE_NO[k]
            }

            return SIZE_NO.lg
        },
        /** 创建 Element **/
        create: function (tag, attrs = []) {
            const el = document.createElement(tag)
            for (const k in attrs) {
                el[k] = attrs[k]
            }
            return el
        },
        getArea: function () {
            return [$(window).width() > 900 ? '900px' : '95%', $(window).height() > 700 ? '700px' : '95%'];
        },
        loading: function (config = {}) {
            if (common.loadingIndex === undefined) {
                common.loadingIndex = $(LOADING(config).join(''))
            }
            $('body').append(common.loadingIndex)
        },
        loadingClose: function () {
            common.loadingIndex && common.loadingIndex.remove()
            common.loadingIndex = undefined
        },
        SIZE_NO
    }

    /**
     * 这里希望通过组件的data属性获取信息
     * 会将【data-id】类型的属性信息取出并将前缀【data-】去除，组合成新的对象信息
     * 作为组件的配置信息
     */
    common.getDataOptions = function (obj) {
        let attrs = obj.getAttributeNames();
        let data = {}
        if (attrs.length === 0) {
            return data
        }
        attrs.forEach(function (item) {
            let name = item.substr(5);
            if (name !== "" && item.indexOf("data-") !== -1) {
                data[name] = obj.getAttribute(item);
            }
        });

        return data;
    }

    common.post = function (url, data, method = 'post', callback = undefined) {
        let index = layer.load();
        const config = {
            url: url,
            data: data || {},
            type: method,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function (response) {
                if (typeof callback === 'function') {
                    callback(response);
                    return;
                }
                if (parseInt(response.code) === 0) {
                    layer.msg(response.message, { icon: 1 });
                } else {
                    layer.msg(response.message, { icon: 2 });
                }
            },
            complete: () => {
                layer.close(index);
            }
        }

        $.ajax(config);
    }
    common.put = function (url, data, callback) {
        common.post(url, data, 'put', callback);
    }
    common.del = function (url, data, callback) {
        common.post(url, data, 'delete', callback);
    }
    common.get = function (url, data, callback) {
        common.post(url, data, 'get', callback);
    }

    /**
     * 自定义弹出层
     * @param url
     * @param title
     * @param options
     * @returns {*}
     */
    common.open = function (url, title, options) {
        const area = common.getArea();
        const config = {
            type: 2,
            title: title,
            shadeClose: true,
            maxmin: true,
            moveOut: true,
            area: area,
            content: url,
            skin: 'layui-layer-lan',
            zIndex: layer.zIndex,
            success: function (layerDom, index) {
                const that = this;
                $(layerDom).data("callback", that.callback);
                layer.setTop(layerDom);
                if ($(layerDom).height() > $(window).height()) {
                    layer.style(index, {
                        top: 0,
                        height: $(window).height()
                    });
                }
            },
        };
        if (options !== undefined) {
            $.extend(config, options);
        }
        return layer.open(config);
    }

    /**
     * 弹出表单层，包含一些表单地处理事件
     * @param url
     * @param title
     * @param options
     */
    common.formOpen = function (url, title, options = {}) {
        let option = {
            btn: ['确认', '关闭'],
            yes: function (index, layerObj) {
                let obj = window[layerObj.find('iframe')[0]['name']];
                if (obj.$("button[lay-filter='PT-submit']").length > 0) {
                    obj.$("button[lay-filter='PT-submit']").click();
                    return
                }
                if (obj.layui['PTForm'] === undefined) {
                    layer.msg("未引入PTForm")
                    return
                }
                obj.layui['PTForm'].activeSubmit();
            },
        }
        $.extend(true, option, options);

        return common.open(url, title, option);
    }

    /**
     * url地址的搜索替换。
     * 可以将 /manage/manage/{id} 替换为/manage/manage/1 这样的地址
     * @param url
     * @param param
     */
    common.urlReplace = function (url, param) {
        if (url === "") {
            console.error('[url]未设置');
            return url;
        }
        let exp = new RegExp('{([^{}])*?}', 'g');
        let compile = function (str) {
            return function (it) {
                return str.replace(exp, function (variable) {
                    let param = variable.match(/{([\s\S]*?)}/)[1];
                    return it[param];
                });
            }
        };

        return compile(url)(param);
    }

    /**
     * 通过字段信息在行数据中获取对应的值
     * @param data
     * @param field
     */
    common.getTableColValue = function (data, field = undefined) {
        if (field === undefined) {
            field = data.LAY_COL.field;
        }
        if (field.includes('.')) {
            let fields = field.split('.');
            for (let i = 0; i < fields.length; i++) {
                data = data[fields[i]] || "";
            }
            return data;
        }
        return data[field];
    }

    /**
     * 本地数据存储
     * @param key
     * @param value
     * @param storage
     */
    common.setStorageData = function (key, value, storage = null) {
        storage = storage || window.localStorage;
        if (value === null) {
            storage.removeItem(key)
            return
        }
        value = typeof value === 'object' ? JSON.stringify(value) : JSON.stringify({ key: value })
        storage.setItem(key, value)
    }

    common.setSessionStorageData = function (key, value) {
        common.setStorageData(key, value, window.sessionStorage)
    }

    /**
     * 获取本地数据
     * @param key
     * @param storage
     * @param def
     */
    common.getStorageData = function (key, storage, def = null) {
        storage = storage || window.localStorage;
        let value = storage.getItem(key)
        if (value === null) {
            return def
        }
        let data = JSON.parse(value)
        if ("key" in data) {
            return data.key
        }
        return data
    }

    common.getSessionStorageData = function (key, def = null) {
        return common.getStorageData(key, window.sessionStorage, def)
    }

    /**
     * 表单数据过滤，将空的数据过滤掉
     * @param data
     * @returns {*}
     */
    common.formFilter = function (data) {
        if (data) {
            for (const key in data) {
                if (data[key] === '' || data[key] === null || data[key] === undefined) {
                    delete data[key]
                }
            }
        }
        return data
    }

    /**
     * 提示信息
     */
    let tips_lock = false
    $('body').on('mouseover', '*[ptadmin-tips]', function () {
        if (tips_lock) {
            return
        }
        tips_lock = true
        const that = $(this)
        const tips = that.attr('ptadmin-tips')
        let direction = that.attr('ptadmin-tips-direction') || 2 // 定义提示框样式，1-4
        let color = that.attr('ptadmin-tips-color') || "#000"
        direction > 4 && (direction = 4)
        layer.tips(tips, that, {
            tips: [direction, color],
            time: 2000
        })
    }).on("mouseleave", "*[ptadmin-tips]", function () {
        layer.closeAll('tips')
        tips_lock = false
    }).on('click', '*[ptadmin-href]', function () {
        const $this = $(this)
        const href = $this.attr('ptadmin-href')
        const text = $this.attr('ptadmin-text') || $this.text()
        if (href === '') {
            return
        }
        //执行跳转
        const root = parent === self ? layui : top.layui;
        root.main.openTab(text, href)
    });


    exports(MOD_NAME, common)
})
