/**
 * 自定义穿梭框组件
 */
layui.define(function (exports) {
    const { $, layer, element } = layui
    const MOD_NAME = "PTTransfer"

    const isNode = function (obj) {
        return obj && typeof obj.nodeType === 'number' && typeof obj.nodeName === 'string';
    }

    // 默认配置参数
    const config = {
        id: undefined,              // 容器ID
        url: '',                    // URL请求地址
        elem: '',                   // 容器 string/DOM
        data: [],                   // 数据
        value: [],                  // 初始选中数据
        title: { left: '源数据', right: '目标数据' }, // 穿梭框标题
        empty: {
            none: '暂无数据',
            selected: '未选择数据'
        },
        where: undefined,           // 查询参数
        page: false,                // 是否开启翻页功能
        search: true,               // 是否开启搜索功能
        field: "transfer",           // 字段名称
        item: {
            type: 'default',        // 列表项类型， 默认为 default, collapse 折叠面板
            template: undefined,    // 自定义列表项模板
        },
        events: undefined
    }

    class Transfer {
        // 当前对象
        T = undefined
        /** 选中数据ID */
        selected = []
        /** 选中数据集合 */
        value = []
        /** 数据集合*/
        data = undefined
        // 事件集合
        events = {}
        /** 配置信息 */
        config = undefined
        /** 主要容器数据 */
        _element = undefined
        /** 左侧容器 */
        _leftBox = undefined
        /** 右侧容器 */
        _rightBox = undefined
        /** 表单提交input */
        _input = undefined
        /** 请求参数 */
        _requestParams = {
            keyword: '',
            page: 1,
            limit: 10
        }
        /** 总页码数 */
        _countPageNum = 0
        constructor(options) {
            this.value = options.value || []
            this.data = options.data || []
            this.config = $.extend({}, config, options)
            this._element = this._elementValid(this.config['elem'])
            this._initialize()
        }

        /**
         * 获取一个有效的DOM
         * @param elem
         * @returns {*}
         * @private
         */
        _elementValid(elem) {
            if (typeof elem === 'string') {
                elem = $(elem)
            }
            if (Object.hasOwn(elem, 'length')) {
                elem = elem[0] || undefined
            }
            if (!isNode(elem)) {
                throw new Error('elem 属性必须为 DOM 对象, 或者有效的 ID 元素')
            }

            return elem
        }

        /**
         * 初始化
         * @private
         */
        _initialize() {
            this._leftBox = new TransferBox(this, 'left')
            this._rightBox = new TransferBox(this, 'right')
            for (const key in this.value) {
                this.selected.push(this.value[key]['value'])
            }
            // 事件绑定
            this.events = {
                selected: this.__selected,
                delete: this.__delete,
                'page-next': this.__next,
                'page-prev': this.__prev,
                'search:left': this.__search
            }
        }

        /**
         * 将选中项目写入到表单input中
         * @private
         */
        _writeInput() {
            if (this._input === undefined) {
                this._input = $(this._element).find(`input[name='${this.config['field']}']`)
            }
            this._input.val(this.selected !== undefined && this.selected.length > 0 ? this.selected.join(",") : '')
        }

        __selected({ data }) {
            let temp = undefined
            if (this.selected.indexOf(data['id']) !== -1) {
                return
            }
            for (const key in this.data) {
                if (this.data[key]['value'] === data['id']) {
                    temp = this.data[key]
                    this.selected.push(data['id'])
                    this.value.push(temp)
                    break
                }
                if (this.config.item.type === 'collapse') {
                    for (const versionKey in this.data[key]['versions']) {
                        if (this.data[key]['versions'][versionKey]['value'] === data['id']) {
                            const resetData = {
                                label: this.data[key]['label'] + this.data[key]['versions'][versionKey]['label'],
                                value: this.data[key]['versions'][versionKey]['value']
                            }
                            temp = resetData
                            this.selected.push(data['id'])
                            this.value.push(temp)
                            break
                        }
                    }
                }
            }
            this._leftBox.selected(data['id'])
            this._rightBox.append(temp)
        }

        __delete(data) {
            data = $(data.elem).parent().data()
            this.selected.splice(this.selected.indexOf(data['id']), 1)
            for (let i = 0; i < this.value.length; i++) {
                if (this.value[i]['value'] === data['id']) {
                    this.value.splice(i, 1)
                    break
                }
            }
            this._rightBox.delete(data['id'])
            this._leftBox.unselected(data['id'])
        }
        __next() {
            this._page('next')
        }
        __prev() {
            this._page('prev')
        }
        __search(data) {
            const elem = $(data.elem)
            this._requestParams['keyword'] = elem.prev().val()
            this._requestData(this.config.url, this._requestParams)
        }
        _page(type) {
            let prevBox = this._leftBox._elem.next().find('.prev')
            let nextBox = this._leftBox._elem.next().find('.next')
            if (type === 'next') {
                this._requestParams.page += 1
                prevBox.removeAttr('disabled')
                if (this._countPageNum === this._requestParams.page) {
                    nextBox.attr('disabled', true)
                }
            } else {
                this._requestParams.page -= 1
                nextBox.removeAttr('disabled')
                if (this._requestParams.page === 1) {
                    prevBox.attr('disabled', true)
                }
            }
            this._requestData(this.config.url, this._requestParams)
            this._leftBox._elem.next().find('.number').text(this._requestParams.page)
        }
        /**
         * 挂载
         */
        mount() {
            if (this.T === true) {
                return
            }
            this.action('mount')
            this.T = true
            const elem = $(this._element)
            const input = `<input type="hidden" value="" name="${this.config['field']}" >`
            const html = `<div class="layui-row layui-col-space15">${input} ${this._leftBox.render()} ${this._rightBox.render()}</div>`
            elem.append(html)
            this.reloadData()
            this.action('mounted')
            const thiz = this
            elem.on('click', "*[ptadmin-event]", function () {
                const data = {
                    elem: this,
                    data: $(this).data(),
                    thiz: thiz
                }
                thiz.action($(this).attr('ptadmin-event'), data)
            })
        }

        /**
         * 卸载
         */
        unmount() {
            if (this.T === false) {
                return
            }
            this.action('unmount')
            this.T = false
            const elem = $(this._element)
            elem.html("")
        }

        /**
         * 重新加载
         */
        reload() {
            this.unmount()
            this.mount()
        }

        /**
         * 重新加载数据
         */
        reloadData(options = undefined) {
            const { data, url, value } = options || { data: this.data || undefined, url: this.config['url'] || '', value: this.value || [] }
            this.action('reloadData')
            this._rightBox.renderData(value)
            this._leftBox.renderData(data)
            if (data !== undefined && data.length > 0) {
                this.data = data
                return
            }
            if (url !== undefined) {
                this.config['url'] = url
                this._requestData(url, this._requestParams)
                return
            }
            throw new Error('未定义数据源')
        }

        /**
         * 请求接口
         * @param url
         * @param options
         * @private
         */
        _requestData(url, options = {}) {
            let load = layer.load(0, { shade: true });
            const thiz = this
            $.ajax({
                url: url,
                type: "get",
                data: options,
                success: function (res) {
                    if (res.code === 0) {
                        thiz.data = res.data.results
                        thiz._leftBox.renderData(thiz.data)
                        // 处理翻页问题
                        if (thiz._leftBox._elem.next().length === 0) {
                            new TransferPage().render(thiz, res.data)
                        }
                        layer.close(load); // 关闭 loading
                    }
                }
            })

        }

        static make(options) {
            const thiz = new Transfer(options)
            thiz.mount()
            return thiz
        }


        on(event, callback) {
            this.events[event] = callback

            return this
        }

        action(event, params) {
            if (this.events[event] !== undefined) {
                this.events[event].call(this, params)
            }
            if (['selected', 'delete', 'mounted', 'reloadData'].indexOf(event) !== -1) {
                this._writeInput()
            }
        }
    }

    class TransferBox {
        /** 当前盒子属于什么类型的，left 或 right */
        _type = 'left'
        _root = undefined
        _page = undefined
        _elem = undefined

        constructor(root, type) {
            this._root = root
            this._type = type
        }

        renderData(data) {
            if (this._elem === undefined) {
                this._elem = $(this._root._element).find(`[data-item-${this._type}]`)
            }
            this._elem.html("")
            const template = this._getActionTemplate()
            const lists = []
            for (let i = 0; i < data.length; i++) {
                lists.push(template(this._getTemplateType(), data[i], this._isSelected(data[i])))
            }
            this._elem.append(lists.join("") || this._empty())
            element.render('collapse');
        }


        render() {
            return `<div class="layui-col-md6">
                        <div class="layui-card">
                            <div class="layui-card-header">
                                <div class="placeholder left-title">${this._root.config['title'][this._type] || ""} </div>
                                ${this._type === 'left' ? this.renderSearch() : ''}
                            </div>
                            <div class="layui-card-body relatedTemplate">
                                <ul class="temp-categorize-content layui-collapse" ${this._type === 'left' ? 'data-item-left' : 'data-item-right'}></ul>
                            </div>
                        </div>
                    </div>`
        }

        renderSearch() {
            if (this._root.config['search'] !== true) {
                return ''
            }

            return `<div class="search">
                        <div class="layui-input-group">
                            <input type="text" placeholder="请输入关键词搜索" class="layui-input"/>
                            <div type="button" class="layui-input-split layui-input-suffix relatedTemplateSearchBtn" ptadmin-event="search:${this._type}" style="cursor: pointer">
                                <i class="layui-icon layui-icon-search"></i>
                            </div>
                        </div>
                    </div>`
        }

        _isSelected(data) {
            if (this._type === 'right') {
                return true
            }
            const selected = this._root.selected
            if (selected === undefined || selected.length === 0) {
                return false
            }
            if (this._root.config.item.type === 'collapse') {
                console.log(selected);
                for (const key in data['versions']) {
                    return selected.includes(data['versions'][key]['value'])
                }
            }
            return selected.includes(data.value)
        }

        _getActionTemplate() {
            const template = this._root.config['item']['template']
            if (template !== undefined && typeof template === 'function') {
                return template
            }
            return TransferItem.render
        }
        _getTemplateType() {
            let type = this._root.config['item']['type']
            if (this._type === 'right') {
                type = 'selected'
            }
            return type
        }
        _empty() {
            let str = ''
            if (this._type === 'left') {
                str = this._root.config['empty']['none']
            }
            if (this._type === 'right') {
                str = this._root.config['empty']['selected']
            }
            return `<div class="temp-empty-box">
                        <div class="layui-icon layui-icon-face-cry"></div>
                        <div class="tip">${str}</div>
                    </div>`
        }
        selected(id) {
            this._elem.find(`[data-id="${id}"]`).addClass('active')
            const empty = this._root._rightBox._elem.find('.temp-empty-box')
            if (empty.length > 0) {
                empty.remove()
            }
        }

        unselected(id) {
            this._elem.find(`[data-id="${id}"]`).removeClass('active')
        }

        delete(id) {
            this._elem.find(`[data-id="${id}"]`).remove()
            if (this._root.value.length === 0) {
                const empty = this._empty()
                this._elem.html(empty)
            }
        }
        append(data) {
            const template = this._getActionTemplate();
            const html = template(this._getTemplateType(), data, true)
            this._elem.append(html)
        }
    }

    /**
     * 列表项
     */
    class TransferItem {
        _active = ''

        constructor(active) {
            this._active = active === true ? 'active' : '';
        }

        renderSelected(data) {
            return `<li class="item" data-id="${data.value}">
                        <span class="text">${data.label}</span>
                        <div class="ptadmin-temp-del" ptadmin-event="delete">
                            <i class="layui-icon layui-icon-delete"></i>
                        </div>
                    </li>`
        }

        renderDefault(data) {
            return `<li class="item ${this._active}" data-id="${data.value}" ptadmin-event="selected">
                        <span class="text">${data.label}</span>
                        <i class="layui-icon layui-icon-success"></i>
                   </li>`
        }

        renderCollapse(data) {
            const children = data.versions || []
            const html = []
            if (children.length > 0) {
                for (let i = 0; i < children.length; i++) {
                    html.push(this.renderDefault(children[i]))
                }
            }
            return `<div class="layui-colla-item">
                        <div class="layui-colla-title">${data.label}</div>
                        <div class="layui-colla-content">${html.join(" ")}</div>
                    </div>`
        }

        static render(type, data, active = false) {
            if (type === 'default') {
                return new TransferItem(active).renderDefault(data)
            }
            if (type === 'collapse') {
                return new TransferItem(active).renderCollapse(data)
            }
            if (type === 'selected') {
                return new TransferItem(active).renderSelected(data)
            }
        }
    }

    /**
     * 翻页对象
     */
    class TransferPage {
        render(root, data) {
            // 计算总页数
            const countPage = Math.ceil(data.total / root._requestParams.limit)
            root._countPageNum = countPage
            const pageStr = `<div class="page">
                                <div class="prev" ptadmin-event="page-prev" ${root._requestParams.page === 1 ? 'disabled' : ''}>上一页</div>
                                <div class="current">
                                    <span class="number">${root._requestParams.page}</span>/<span class="total">${countPage}</span>
                                </div>
                                <div class="next" ptadmin-event="page-next" ${root._requestParams.page === countPage ? 'disabled' : ''}>下一页</div>
                            </div>`;
            root._leftBox._elem.after(pageStr)
        }
    }
    exports(MOD_NAME, Transfer);
})
