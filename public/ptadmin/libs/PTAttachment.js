
layui.define(['upload', 'layer', 'form', 'laypage', 'common'], function (exports) {
    "use strict";
    // 测试数据
    const resultData = {
        data: {
            categorize: [
                {
                    id: 0,
                    name: '图片文件',
                    children: [
                        {
                            id: 1,
                            name: '图片文件一'
                        },
                        {
                            id: 2,
                            name: '图片文件二'
                        }
                    ]
                },
                {
                    id: 3,
                    name: '文档文件',
                    children: [
                        {
                            id: 4,
                            name: '文档文件一'
                        }
                    ]
                },
                {
                    id: 5,
                    name: '视频文件',
                }
            ],
            results: [
                {
                    created_at: "2023-06-28 18:29:27",
                    driver: "local",
                    groups: "default",
                    id: 999999,
                    md5: "d41d8cd98f00b204e9800998ecf8427e",
                    mime: "image/png",
                    path: "public/default/20230628/VLQrF7aHO9LIrX5VdNM7u2jREHjA2dGQ3HBsU8Cn.png",
                    quote: 0,
                    size: "1.57KB",
                    suffix: "png",
                    title: "测试356.png",
                    updated_at: "2023-06-28 18:29:27",
                    url: "https://tse1-mm.cn.bing.net/th/id/OIP-C.7lURCaoa-V1fPFkaYrhOdwHaHZ?w=226&h=180&c=7&r=0&o=5&pid=1.7"
                },
                {
                    created_at: "2023-06-28 18:29:27",
                    driver: "local",
                    groups: "default",
                    id: 888888888,
                    md5: "d41d8cd98f00b204e9800998ecf8427e",
                    mime: "image/png",
                    path: "public/default/20230628/VLQrF7aHO9LIrX5VdNM7u2jREHjA2dGQ3HBsU8Cn.png",
                    quote: 99,
                    size: "57KB",
                    suffix: "jpg",
                    title: "测试123.jpg",
                    updated_at: "2023-06-28 18:29:27",
                    url: "https://tse1-mm.cn.bing.net/th/id/OIP-C.StrDRqennoZNbzSPZapKZwAAAA?w=326&h=182&c=7&r=0&o=5&pid=1.7"
                },
            ],
            header: {
                dropdown: [
                    {
                        label: 'JPG',
                        value: 'jpg'
                    },
                    {
                        label: 'PNG',
                        value: 'png'
                    }
                ]
            },
            total: 50
        }
    }

    const imageType = ['png', 'jpg']
    const { $, upload, form, layer, laypage, common } = layui;
    const MOD_NAME = "PTAttachment";
    const extendObj = {
        id: '',
        title: ''
    }
    // 横向布局Class
    const TRANSVERSE = "ptadmin-attachment-transverse"
    // 纵向布局Class
    const DIRECTION = 'ptadmin-attachment-direction'
    // 盒子布局默认的盒子
    const DEFAULT = `ptadmin-attachment-upload`
    // 上传按钮
    const UPLOADBTN = 'ptadmin-attachment-upload-btn'
    // success Class
    const SUCCESSCLASS = 'upload-success'
    // 元素
    const ITEMCLASS = 'section'
    // 弹出层元素
    const ATTACHMENT_DIALOG = 'ptadmin-attachment-dialog-box'
    // 弹层上传URL
    const uploadUrl = common.url('upload')
    const attachmentUrl = common.url('attachments')
    // 弹层视图
    const attachmentView = {
        container: null,  //  弹出层容器
        area: null,  // 弹出层尺寸
        layerIndex: null,  // 弹层下标
        results: [],  //返回的所有数据
        data: undefined,// 文件数据
        currentBtn: null, // 当前打开弹层按钮
        selected: [], // 已选择的数据
        contentAttr: "content-list",  // 内容标签数据
        currPage: 1, // 当前页
        filterFile: '',  // 过滤的文件
        root: null
    }

    const config = {
        id: '', // 上传文件id
        suffix: '', // 支持的文件后缀 不填写默认为所有
        theme: 'default', // 默认主题 default  头像主题 avatar'
        elem: '', // 元素 字符串 DOM
        remote: true, // 是否为远程文件
        selector: true,  // 是否开启资源选择器
        direct: true, // 是否为直接上传
        saveRemote: true, // 是否保存远程文件
        edit: true, // 允许修改名称
        attribute: { // layui的原始属性与扩充属性
            multiple: false,
            accept: 'file',  // 允许上传的类型  和筛选的文件格式  默认所有
            allowFiles: 'file',  // 允许弹层的文件类型  多个用,隔开 file video audio image
        }, // layui原始属性
        data: undefined, // 数据
        area: '150', // 盒子等比尺寸
        required: true,// 是否必填
        field: '',
        done: function () { },
        confirm: function () { }
    }

    /**
     * 判断元素类型并返回对应的 jQuery 对象
     * @param {any} value - 输入值，可以是字符串、DOM 元素或 jQuery 对象
     * @returns {jQuery|null} 返回对应的 jQuery 对象或 null
     */
    const getElement = function identify(value) {
        // 处理 null 和 undefined
        if (!value) {
            console.error('未正确传入元素参数');
            return null;
        }
        // 检查字符串类型
        if (typeof value === 'string') { return $(value); }
        // 检查 DOM 元素
        if (value instanceof HTMLElement || value.nodeType === 1) { return $(value); }
        // 检查 jQuery 对象
        if (value instanceof $) { return value; }
        // 未知类型
        console.error('未知类型');
        return null;
    }

    // 判断是否需要展示上传按钮
    const isShowUpload = function (root, elem, uploadData) {
        let isShow = true
        if (!root.attribute['multiple'] && uploadData.length > 0) {
            isShow = false
        }
        if (root.attribute['multiple']) {
            isShow = uploadData.length < Number(root['attribute']['number'])
        }
        const element = elem.find(`.${UPLOADBTN}`)
        isShow ? element.attr('show', '') : element.removeAttr('show')
    }

    // 根据主题渲染 dom
    const themeAvatar = function (root, elem, uploadData) {
        elem.addClass(DEFAULT + " " + TRANSVERSE)
        const extend = `
                <div class="attachment-operate layui-btn-group">
                    <button type="button" class="layui-btn layui-btn-xs" ptadmin-event="upload">上传</button>
                    <button type="button" class="layui-btn layui-btn-xs layui-bg-blue" ptadmin-event="selector">选择</button>
                </div>`
        // 设置事件
        const setPtadminEvent = function () {
            if (root.direct) {
                return root.selector ? '' : 'ptadmin-event="upload"';
            } else {
                return 'ptadmin-event="selector"';
            }
        };
        const avatar = `<div class="${UPLOADBTN}" ${setPtadminEvent()}>
                            <i class="layui-icon layui-icon-upload-drag"></i>
                            ${root.direct && root.selector ? extend : ''}
                        </div>`
        elem.append(initItems(root), avatar)
        isShowUpload(root, elem, uploadData)
    }

    const themeDefault = function (root, elem, uploadData) {
        elem.addClass(DEFAULT + " " + DIRECTION)
        const input = `
                        <input type="text" placeholder="请输入URL地址" class="layui-input" ptadmin-input-event="input-remote"/>
                    `
        // TODO 远程保存需考虑
        // <input type="hidden" name="${root['field']}_remote" value="${root['saveRemote']}" class="layui-input"/>
        const operates = `<div class="ptadmin-remote layui-form">
                                ${root.remote ? input : ''}
                            <div class="input-suffix layui-btn-group">
                                ${root.direct ? '<button type="button" class="layui-btn layui-bg-blue" ptadmin-event="selector">选择</button>' : ''}
                                ${root.selector ? '<button type="button" class="layui-btn layui-bg-orange" ptadmin-event="upload">上传</button>' : ''}
                            </div>
                        </div>`
        const successBox = $(`<div class="${SUCCESSCLASS}"></div>`)
        initItems(root).forEach(item => {
            successBox.append(item)
        });
        elem.append(operates, successBox)
        isShowUpload(root, elem, uploadData)
    }

    // 初始化回显数据
    const initItems = function (root, a) {
        const data = verificationData(root.data)
        let items = []
        if (data) {
            data.forEach(item => {
                items.push(resourcesItem(item, root))
            });
        }
        return items
    }
    // 验证数据 转换数据
    const verificationData = function (data) {
        if (!Array.isArray(data)) {
            if (typeof data === 'object' && data !== null) {
                data = [data];
            } else {
                data = []
            }
        }
        return data
    }
    // 生成唯一Name值
    const produceName = function (root, time, val = '') {
        const extend = val ? '__extend__' : ''
        const random = root['attribute']['multiple'] ? `[${time}]` : ''
        return `${root['field']}${extend}${random}${val}`
    }

    const resourcesItem = function (data, root, _index = '') {
        // _index 为layui上传文件的下标
        const time = Math.random().toString(16).substring(2, 8);
        const value = {}
        if (extendObj) {
            Object.keys(extendObj).forEach(key => {
                if (data.hasOwnProperty(key)) {
                    value[key] = data[key];
                }
            });
        }
        const titleInput = `<input name="${produceName(root, time, `[title]`)}" type="text" class="layui-input" lay-affix="clear" value="${data['title']}"/>`
        const fieldInput = `<input name="${produceName(root, time)}" urlInput type="hidden" value='${data.url}'/>`
        //  扩展input字段
        let input = []
        for (const key in value) {
            if (key !== 'title') {
                input.push(`<input name="${produceName(root, time, `[${key}]`)}" type="hidden" value='${value[key]}'/>`)
            }
        }
        const items = $(`
                        <div class="${ITEMCLASS} layui-form"  remote-id="${data.remote_id ?? ''}">
                            ${fieldInput} ${input.join('')}
                            <div class="file"  ptadmin-event="file">
                                <div class="delete-btn" ptadmin-event="delete" ${_index ? `data-index=${_index}` : ''} >
                                    <i class="layui-icon layui-icon-delete"></i>
                                </div>
                            </div>
                            ${root['edit'] ? titleInput : ''}
                        </div>`)
        let img = new Image;
        img.src = data['thumb'] ? data['thumb'] : data['url']
        img.onload = function () {
            items.find('.file').append(img)
            img.onload = null;
            img.onerror = null;
            img = '';
        }
        const fileType = data['mime'] ?? ''
        let findIndex = fileType.indexOf("/");
        let type = fileType.substring(0, findIndex);
        img.onerror = function () {
            switch (type) {
                case 'video':
                    img.src = '/ptadmin/images/videoImage.png'
                    break;
                case 'audio':
                    img.src = '/ptadmin/images/audioImage.png'
                    break;
                case 'image':
                    img.src = '/ptadmin/images/errorImage.png'
                    break
                default:
                    img.src = '/ptadmin/images/unknown.png'
                    break;
            }
        }
        return items
    };
    class attachmentUpload {
        config = {}
        events = {}
        upload = [] // 上传成功存储的数据
        elem = undefined
        waitFiles = []  // 等待上传的文件
        filesIndex = [] // 文件index集合
        constructor(config) {
            this._setConfig(config)
            this._gainElem()
            this._render()
        }

        _setConfig(options) {
            if (typeof options !== 'object' || !options) {
                throw new Error('参数必须为对象')
            }
            this.config = $.extend({}, config, options);
            if (this.config['attribute'] && typeof this.config['attribute'] === 'object') {
                this.config['attribute'] = { ...config['attribute'], ...options['attribute'] }
            }
        }

        _gainElem() {
            this.elem = getElement(this.config.elem)
            this.elem.attr('style', `--ptadmin-size:${this.config.area}px`)
        }

        _render() {
            this.upload = this.config.data ? verificationData(this.config.data) : []
            switch (this.config.theme) {
                // 头像上传主题
                case "avatar":
                    themeAvatar(this.config, this.elem, this.upload)
                    break;
                default:
                    themeDefault(this.config, this.elem, this.upload)
                    break;
            }
            this._event()
            this.events = {
                delete: this._delete,
                file: this._clickfile,
                selector: this._selector,
                'input-remote': this._inputRemote
            }
            // 上传开启时渲染上传
            if (this.config['direct']) {
                this._initializeUpload()
            }
            form.render()
        }

        _event() {
            const thiz = this
            this.elem.on('click', "*[ptadmin-event]", function (target) {
                const data = { elem: this, thiz: thiz }
                thiz.action($(this).attr('ptadmin-event'), data, target)
            });
            this.elem.on('input', '*[ptadmin-input-event]', this._debounce(function (target) {
                const data = { elem: this, thiz: thiz };
                thiz.action($(this).attr('ptadmin-input-event'), data, target)
            }, 800))
        }
        _delete(data, target) {
            target.stopPropagation()
            const delDom = $(data.elem);
            // 获取当前数据的下标
            const uploadIdx = delDom.closest('.section').index()
            // 获取文件下标
            const indexFile = delDom.attr('data-index');
            // 移除 DOM 元素并处理异常
            try {
                this.upload.splice(uploadIdx, 1)
                delDom.closest(`.${ITEMCLASS}`).remove();
            } catch (error) {
                console.error('删除失败');
            }
            isShowUpload(this.config, this.elem, this.upload)
            // 删除文件
            if (indexFile) {
                this.filesIndex = this.filesIndex.filter(item => item !== indexFile);
                delete this.waitFiles[indexFile];
            }
        }
        // 点击文件
        _clickfile(data) {
            const curIdx = $(data.elem).closest('.section').index()
            const curUploadData = this.upload[curIdx]
            const isImage = imageType.includes(curUploadData['suffix'])
            const url = curUploadData.url
            this.previewFile(url, isImage)
        }

        // 弹出附件管理
        _selector(data) {
            const elem = attachmentView.currentBtn ? attachmentView.currentBtn.elem : ''
            // 元素不相同则重置
            if (data.elem !== elem) {
                resettingLayer()
            }
            attachmentView.currentBtn = data
            attachmentView.root = this
            openLayer()
        }
        // 初始化上传按钮
        _initializeUpload() {
            const thiz = this
            const elem = this.elem.find('[ptadmin-event="upload"]')
            upload.render({
                elem: elem,
                ...this.config.attribute,
                before(obj) {
                    // 将文件追加到队列
                    thiz.waitFiles = obj.pushFile();
                    // 将文件队列中的所有下标归集
                    for (const key in thiz.waitFiles) {
                        thiz.filesIndex.push(key)
                    }
                    // 找到重复下标
                    thiz.filesIndex = thiz.filesIndex.filter((item, index) => {
                        if (thiz.filesIndex.indexOf(item) !== index) {
                            delete thiz.waitFiles[item];
                            return
                        }
                        return item
                    })
                    // 获取上传数据的长度
                    const waitFilesLength = Object.keys(thiz.waitFiles).length
                    // 获取已存在的数据长度
                    const uploadLength = thiz.upload.length
                    if (uploadLength + waitFilesLength > +thiz.config.attribute['number']) {
                        thiz.filesIndex.forEach(item => {
                            delete thiz.waitFiles[item];
                        });
                        thiz.filesIndex = []
                        layer.msg(`最多上传${thiz.config.attribute['number']}张图片`, { icon: 0 });
                    }
                },
                done: function (res, index) {
                    if (res.code !== 0) {
                        layer.msg(res.message, { icon: 0 });
                        return
                    }
                    // 单图上传
                    if (!thiz.config.attribute['multiple']) {
                        thiz.upload = [res.data]
                    } else {
                        const param = $.extend(res.data, { uploadIndex: index });
                        // 多图上传
                        thiz.upload.push(param)
                    }
                    thiz.imageRender(res, elem, index)
                    const params = {
                        data: res,
                        config: thiz.config,
                        uploadValues: thiz.upload
                    }
                    thiz.config.done(params)
                    form.render()
                },
            });
        }
        // 远程输入事件
        _inputRemote(data) {
            const val = $(data.elem).val();
            const arr = val ? val.split(',') : [];
            const filterArr = arr.filter(item => item.trim());  // 过滤为空字符串的数据
            const isMultiple = this.config['attribute']['multiple']
            const maxNum = this.config.attribute['number']  // 最大可存在的数据个数
            const parent = $(data.elem).closest(`.${DEFAULT}`)
            // 所有子元素
            const children = parent.find(`.${SUCCESSCLASS}`).children()
            // 单
            if (!isMultiple && filterArr.length > 1) {
                layer.msg('最多只能存在一个资源')
                return
            }
            // 多
            if (isMultiple && maxNum && this.upload.length >= maxNum) {
                layer.msg('最多只能存在' + maxNum + '个资源')
                return
            }

            // 重设数据
            let rebuild = []
            filterArr.forEach((item, idx) => {
                const data = {
                    url: item,
                    title: item,
                    remote_id: 'remote_' + idx
                }
                rebuild.push(data)
            });

            // 当数据存在的时候
            if (!isMultiple) {
                this.upload = rebuild
            } else {
                const arr = rebuild;
                let filterArr = arr.filter((item, index, self) => {
                    return index === self.findIndex(it => it.id !== item.id);
                });
                console.log(filterArr);
                // this.upload = filterArr
            }

            // 获取最后一个输入的文件地址
            if (this.upload.length > 0) {
                const last = this.upload[this.upload.length - 1]
                if (!this.config['edit']) {
                    delete last.title
                }
                const html = resourcesItem(last, this.config)
                if (isMultiple) {
                    // 多选
                    $.each(children, function (i, item) {
                        console.log(item);
                    })
                    console.log('已经上传存在的数据：：：', this.upload);
                }
                // 单选
                if (!isMultiple) {
                    parent.find(`.${SUCCESSCLASS}`).html(html)
                }
            }

            // 当单选时，且没有上传文件，则移除
            if (!isMultiple && this.upload.length === 0) {
                children.remove()
            }

            if (this.config['remoteInput'] && typeof this.config['remoteInput'] === "function") {
                this.config['remoteInput'](this.upload, data)
            }
        }
        action(event, params, target) {
            if (this.events[event] !== undefined) {
                this.events[event].call(this, params, target)
            }
        }
        // 图片渲染
        imageRender(data, elem, _index) {
            let box = ''
            const itemDom = $(resourcesItem(data.data, this.config, _index))
            switch (this.config.theme) {
                case 'avatar':
                    box = elem.closest(`.${UPLOADBTN}`).length > 0 ? elem.closest(`.${UPLOADBTN}`) : this.elem
                    box.before(itemDom)
                    break;
                default:
                    box = elem.closest(`.${DEFAULT}`).find(`.${SUCCESSCLASS}`)
                    const isMultiple = this.config['attribute']['multiple']
                    isMultiple ? box.append(itemDom) : box.html(itemDom)
                    break;
            }
            isShowUpload(this.config, this.elem, this.upload)
            return itemDom
        }
        // 预览文件
        previewFile(url, fileType) {
            if (fileType) {
                layer.photos({
                    photos: {
                        "data": [{ "src": url }]
                    },
                    footer: false
                });
                return
            }
            window.parent.open(url)
        }
        // 防抖
        _debounce(fn, time) {
            let timeout;
            return function () {
                const context = this;
                const args = arguments;
                if (timeout) {
                    clearTimeout(timeout);
                    timeout = null;  // 确保 timeout 被释放
                }
                timeout = setTimeout(() => fn.apply(context, args), time);
            };
        }
    }


    // 创建弹层
    const buildLayer = function () {
        // 根据屏幕宽度 生成不同的弹窗尺寸
        $(window.parent.document).ready(function () {
            const windowWidth = $(window.parent.document).width()
            if (windowWidth > 1300) {
                attachmentView.area = ['1200px', '800px']
            }
            if (windowWidth < 1299.99) {
                attachmentView.area = ['700px', '800px']
            }
            let box = $(`
                        <div class="ptadmin-attachment-box ${ATTACHMENT_DIALOG}">
                            <div class="ptadmin-attachment-container">
                                <aside class="ptadmin-attachment-aside layer-aside"></aside>
                                <main class="ptadmin-attachment-main"></main>
                            </div>
                        </div>`);
            attachmentView.container = box
            box = ''
            initializeLayer()
        })
    }

    // 创建头部
    const buildHeader = function () {
        const header = attachmentView.results['data']['header']
        const dropdown = header['dropdown']
        if (header && dropdown) {
            let options = dropdown.map(item => {
                return `<option value="${item.value}">${item.label}</option>`
            }).join('')
            const html = `
            <div class="attachment-top">
                <div class="attachment-top-l layui-form">
                    <div class="layui-btn-group">
                        <button type="button" class="layui-btn layui-btn-sm" ptadmin-event="confirm">
                            <i class="layui-icon layui-icon-ok"></i> 确认
                            <span class="layui-badge" count>0</span>
                        </button>
                        <button type="button" class="layui-btn layui-btn-sm layui-bg-blue" attachment-layer="upload">
                            <i class="layui-icon layui-icon-upload"></i> 上传
                        </button>
                        <button class="layui-btn layui-bg-orange layui-btn-sm" ptadmin-event="cancel">
                            <i class="layui-icon layui-icon-error"></i>取消
                        </button>
                        <button class="layui-btn layui-bg-red layui-btn-sm" ptadmin-event="deleteSelected">
                            <i class="layui-icon layui-icon-delete"></i>删除
                        </button>
                    </div>
                    <input type="checkbox" lay-skin="tag" lay-filter="ptadminSelectAll" ptadmin-select-all/>
                    <div lay-checkbox>
                        <i
                            class="layui-icon layui-icon-success"
                            style="position: relative; top: 1px; line-height: normal"></i>
                        全选
                    </div>
                </div>
                <form class="layui-form attachment-top-r layui-form" action="" id="attachmentSearchForm">
                    <div class="select search-item">
                        <select name="file_suffix">
                            <option value="">请选择文件类型</option>
                            ${options}
                        </select>
                    </div>
                    <div class="layui-input-group search-item">
                        <input type="text" name="keywords" placeholder="请输入关键字进行搜索" class="layui-input" />
                        <div class="layui-input-split layui-input-suffix" lay-submit style="cursor: pointer" lay-filter="attachmentSearch">
                            <i class="layui-icon layui-icon-search"></i>
                        </div>
                    </div>
                </form>
            </div>`
            attachmentView.container.find('.ptadmin-attachment-main').append(html)
        }
    }

    // 创建左侧分类列表
    const buildCategory = function () {
        const data = attachmentView.results.data.categorize
        const change = `ptadmin-event="change"`
        let html = '<ul class="layui-menu ptadmin-attachment-nav">';
        html += `<li class="layui-menu-item-checked" ${change} data-id="-1"><div class="layui-menu-body-title">全部文件</div></li>`;
        data.forEach(item => {
            if (item.children && item.children.length > 0) {
                html += `<li class="layui-menu-item-group layui-menu-item-up">
                            <div class="layui-menu-body-title">${item.name}
                                <i class="layui-icon layui-icon-up"></i>
                            </div>
                            <ul class="ptadmin-children">`;
                item.children.forEach(child => {
                    html += `<li ${change} data-id="${child.id}">
                                <div class="layui-menu-body-title">${child.name}</div>
                            </li>`;
                });
                html += '</ul></li>';
            } else {
                html += `<li ${change} data-id="${item.id}"><div class="layui-menu-body-title">${item.name}</div></li>`;
            }
        });
        html += '</ul>';
        attachmentView.container.find('.ptadmin-attachment-aside').html(html)
    }

    // 创建内容容器
    const buildContent = function () {
        const data = attachmentView.results
        const isExist = data.data.results && data.data.results.length > 0
        const empty = `
                            <div class="empty-box ${isExist ? '' : 'empty-active'}">
                                <div class="layui-icon layui-icon-face-cry"></div>
                                <div class="text">暂无更多数据</div>
                            </div>
                        `
        const content = `<div div class="content">
                                ${empty}
                                <ul class="lists" ${attachmentView.contentAttr}></ul>
                            </div > `
        attachmentView.container.find('.ptadmin-attachment-main').append(content)
    }
    // 创建分页
    const buildPage = function () {
        const html = `<div div class="attachment-footer" > <div id="laypage-dialog"></div> </div > `
        attachmentView.container.find('.ptadmin-attachment-main').append(html)
    }
    // 循环数据渲染
    const layerFileRender = function (data) {
        if (!Array.isArray(data)) {
            console.error('数据类型错误，应为数组');
            return
        }
        const isExist = data && data.length > 0
        let contentItem = ``
        if (isExist) {
            data.forEach((item) => {
                // 是否高亮
                const isActive = attachmentView.selected.some(it => item.id === it.id)
                contentItem += layerFileItem(item, isActive)
            })
        }
        attachmentView.container.find(`[${attachmentView.contentAttr}]`).html(contentItem)
    }


    // 文件元素模板
    const layerFileItem = function (data, active = false) {
        const html = `
                        <li class="item ${active ? 'active' : ''}" data-id="${data['id']}" ptadmin-event="selectFile">
                            <div class="image">
                                <img src="${data['thumb'] ? data['thumb'] : data['url']}" alt="" />
                                <div class="mask-operate">
                                    <div class="layui-btn-group">
                                        <button type="button" class="layui-btn layui-btn-xs" ptadmin-event="operate" data-event="preview">
                                            <i class="layui-icon layui-icon-eye"></i>
                                        </button>
                                        <button type="button" class="layui-btn layui-btn-xs layui-bg-blue" ptadmin-event="operate" data-event="edit">
                                            <i class="layui-icon layui-icon-edit"></i>
                                        </button>
                                        <button type="button" class="layui-btn layui-btn-xs layui-bg-red" ptadmin-event="operate" data-event="delete">
                                            <i class="layui-icon layui-icon-delete"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="section">
                                <span class="count"><i class="iconfont icon-tap"></i>${data['quote']}</span>
                                <span class="layui-badge layui-bg-blue">${data['suffix']}</span>
                            </div>
                            <div class="title">${data['title']}</div>
                            <i class="layui-icon layui-icon-ok"></i>
                        </li>
                    `
        return html
    }
    // 初始化弹窗
    const initializeLayer = function () {
        const loadIndex = layer.load(0);
        $.ajax({
            url: attachmentUrl,
            type: "GET",
            data: {},
            dataType: "json",
            success: function (data) {
                if (data.code === 0) {
                    // TODO 修改正式数据
                    attachmentView.results = resultData
                    // attachmentView.data = resultData.data.results ?? []
                    // <-- TODO需删除 仅测试 -->
                    attachmentView.data = data.data.results ?? []
                    attachmentView.results.data.total = data.data.total
                    // <-- 需删除 end -->
                    $('body').append(attachmentView.container)
                    buildCategory() // 创建左侧分类
                    buildHeader() // 创建头部
                    buildContent()  // 内容容器渲染
                    buildPage()  // 创建分页器
                    layerFileRender(attachmentView.data)  // 生成内容
                    initializeLayerUpload() // 初始化上传按钮
                    bindEvents(attachmentView.container)
                    form.render()
                    // 全选
                    form.on('checkbox(ptadminSelectAll)', function (data) {
                        const elem = data.elem;
                        const checked = elem.checked;
                        selectAll(checked)
                    });
                    // 搜索查询
                    form.on('submit(attachmentSearch)', function (data) {
                        const field = data.field;
                        layerSearch(field)
                        return false;
                    });
                }
            },
            error: function (err) {
                layer.msg(err.message, { icon: 0 });
            },
            complete: function () {
                layer.close(loadIndex);
            }
        });
    }
    const layerEvents = {
        // 预览/编辑/删除
        operate: function (elem, e) {
            e.stopPropagation()
            const parent = elem.closest('.item')
            const id = parent.attr('data-id')
            const eventStr = elem.attr('data-event')
            let found = undefined
            if (id) {
                found = attachmentView.data.find(item => item.id === +id)
            }
            switch (eventStr) {
                case 'preview':
                    layerEvents._preview(found)
                    break;
                case 'edit':
                    layerEvents._edit(found, parent)
                    break
                case 'delete':
                    layerEvents._delete(id, parent)
                    break
                default:
                    console.error('未知操作')
                    break;
            }
        },
        // 文件预览
        _preview: function (data) {
            const isImage = imageType.includes(data['suffix'])
            attachmentView.root.previewFile(data.url, isImage)
        },
        // 文件title名称修改
        _edit: function (data, elem) {
            const val = elem.find('.title').text()
            layer.prompt({ title: '请输入名称', value: val }, function (value, index, el) {
                if (value === '') {
                    el.focus()
                    layer.msg('请输入名称')
                    return
                };
                elem.find('.title').text(value)
                data.title = value
                layer.close(index);
            });
        },
        // 文件删除  TODO完善
        _delete: function (id, elem) {
            layer.confirm('确认删除？', { icon: 3, title: '提示' }, function (index) {
                // attachmentView.selected = thiz.results.filter(item => item.id !== +id)
                // elem.remove()
                console.log('完善删除');
                layer.close(index);
            });
        },
        // 文件选择
        selectFile: function (elem) {
            const id = elem.attr('data-id') ? +elem.attr('data-id') : ''
            const findData = attachmentView.data.find((item) => item.id === id)
            const multiple = attachmentView.root['config']['attribute']['multiple']
            // 单选操作
            if (!multiple) {
                attachmentView.selected = [findData]
                elem.addClass('active').siblings().removeClass('active')
            }
            // 多选操作
            if (multiple) {
                if (elem.hasClass('active')) {
                    elem.removeClass('active')
                    attachmentView.selected = attachmentView.selected.filter((item) => item.id !== id)
                } else {
                    elem.addClass('active')
                    attachmentView.selected.push(findData)
                }
            }

            layerFileCount()
            controlAllCheckbox()
        },
        // 左侧分类切换 TODO
        change: function (elem) {
            const id = $(elem).attr('data-id')
            console.log('切换事件元素ID=======>', id);
        },

        // 确认选择文件
        confirm: function () {
            if (attachmentView.selected.length === 0) {
                layer.msg('请选择附件')
                return
            }
            const supportedFileTypes = attachmentView.root['config']['attribute']['allowFiles']   // 支持选择的文件格式
            const supportedFileTypesArray = supportedFileTypes ? supportedFileTypes.split(',') : ''  // 支持选择文件格式集合
            const selectedNumber = attachmentView.selected.length // 选择的个数
            const uploadNum = attachmentView.root['upload'].length  // 已存在的个数
            const multiple = attachmentView.root['config']['attribute']['multiple']  // 是否多选
            const maxNum = attachmentView.root['config']['attribute']['number'] // 最大支持选择个数
            // 单选判断
            if (!multiple && selectedNumber > 1) {
                layer.msg('只能选择一个')
                return
            }
            // 最多选择多少个
            if (multiple && maxNum && uploadNum + selectedNumber > maxNum) {
                layer.msg('最多选择' + maxNum + '个')
                return
            }
            // 判断类型文件
            let isContinue = true
            if (supportedFileTypes && supportedFileTypes !== 'file' && attachmentView.selected.length > 0) {
                for (let i = 0; i < attachmentView.selected.length; i++) {
                    let findIndex = attachmentView.selected[i]['mime'].indexOf("/");
                    let type = attachmentView.selected[i]['mime'].substring(0, findIndex);
                    if (!supportedFileTypesArray.includes(type)) {
                        isContinue = false
                        break
                    }
                }
            }
            if (!isContinue) {
                layer.msg(`请选择${supportedFileTypes}文件`);
                return;
            }
            attachmentView.root['config'].confirm(attachmentView.selected)

            attachmentView.root['upload'] = multiple ? [...attachmentView.root['upload'], ...attachmentView.selected] : attachmentView.selected
            attachmentView.selected.forEach(item => {
                const data = { data: item }
                const elem = $(attachmentView.currentBtn.elem)
                attachmentView.root.imageRender(data, elem)
            });
            layer.close(attachmentView.layerIndex);
            resettingLayer()
        },
        // 多选删除 TODO完善
        deleteSelected: function () {
            if (attachmentView.selected && attachmentView.selected.length === 0) {
                layer.msg('请选择将要删除的文件')
                return
            }
            const ids = []
            attachmentView.selected.forEach(item => {
                ids.push(item.id)
            });
            layer.confirm('确认删除？', { icon: 3, title: '提示' }, function (index) {
                console.log('删除成功 需将selected清空', ids);
                layer.close(index);
            });
        },
        // 取消勾选
        cancel: function () {
            if (attachmentView.selected.length === 0) {
                layer.msg('请选择文件')
                return
            }
            attachmentView.selected = []
            layerFileCount()
            controlAllCheckbox()
            const children = attachmentView.container.find(`[${attachmentView.contentAttr}]`).children()
            children.removeClass('active')
        }
    }

    // 绑定弹层页面事件
    const bindEvents = function (container) {
        container.on("click", `*[ptadmin-event]`, function (e) {
            const $this = $(this)
            const event = $this.attr(`ptadmin-event`)
            if (layerEvents[event] && typeof layerEvents[event] === 'function') {
                layerEvents[event].call($this, $this, e)
                return
            }
            console.error(`未找到事件${event}`)
        })
    }

    // 初始化弹层上传按钮
    const initializeLayerUpload = function () {
        const elem = attachmentView.container.find('[attachment-layer="upload"]')
        upload.render({
            elem: elem,
            url: uploadUrl,
            multiple: true,
            accept: 'file',
            done: function (res, index) {
                if (res.code === 0) {
                    const html = layerFileItem(res.data)
                    attachmentView.container.find(`[${attachmentView.contentAttr}]`).children().first().before(html)
                    const results = attachmentView.results.data.results
                    results.unshift(res.data)
                    console.log('上传成功数据查看', results);
                    return
                }
                layer.msg(res.message, { icon: 0 });
            },
        });
    }

    // 控制全选状态
    const controlAllCheckbox = function () {
        const children = attachmentView.container.find(`[${attachmentView.contentAttr}]`).children()
        const activeElements = children.filter('.active');
        const checkbox = attachmentView.container.find('[ptadmin-select-all]')
        if (children.length === activeElements.length) {
            checkbox.prop('checked', true)
        } else {
            checkbox.prop('checked', false)
        }
        form.render()
    }
    // 重置弹层状态
    const resettingLayer = function () {
        const children = attachmentView.container.find(`[${attachmentView.contentAttr}]`).children()
        const checkbox = attachmentView.container.find('[ptadmin-select-all]')
        attachmentView.selected = []
        children.removeClass('active')
        checkbox.prop('checked', false)
        $("#attachmentSearchForm")[0].reset();  // 重置layui表单
    }
    // 开启弹层
    const openLayer = function () {
        attachmentView.layerIndex = layer.open({
            type: 1,
            area: attachmentView.area,
            title: '文件管理器',
            resize: false,
            shadeClose: true,
            content: attachmentView.container,
            success: function () {
                layerPage()
                layerFileCount()
                console.log('【根据文件类型筛选文件，file不进行筛选】', attachmentView.root['config']['attribute']['allowFiles']);
                form.render()
            }
        })
    }
    // 全选
    const selectAll = function (isChecked) {
        const children = attachmentView.container.find(`[${attachmentView.contentAttr}]`).children()
        if (isChecked) {
            const mergeArr = [...attachmentView.selected, ...attachmentView.data]
            attachmentView.selected = mergeArr.filter((item, index, self) => {
                return index === self.findIndex(it => it.id === item.id);
            });
            children.addClass('active')
        } else {
            attachmentView.selected = []
            children.removeClass('active')
        }
        layerFileCount()
    }

    // 搜索功能
    const layerSearch = function (params) {
        console.log('【TODO】完善搜索功能', params);
        const loadIndex = layer.load(0);
        $.ajax({
            url: attachmentUrl,
            type: "GET",
            data: params,
            dataType: "json",
            success: function (res) {
                console.log(res);
                if (res.code === 0) {
                    // TODO测试数据 需删除
                    attachmentView.results = res
                    attachmentView.data = res.data.results
                    layerFileRender(res.data.results)
                    // 测试数据 需删除 end
                    attachmentView.currPage = 1  // 重置页码
                    layerPage()  // 重新渲染翻页
                    controlAllCheckbox() // 全选判定
                    return
                }
                layer.msg(data.message, { icon: 0 });
            },
            error: function (err) {
                layer.msg(err.message, { icon: 0 });
            },
            complete: function () {
                layer.close(loadIndex);
            }
        });
    }

    // 弹层分页渲染
    const layerPage = function () {
        laypage.render({
            elem: 'laypage-dialog',
            count: attachmentView.results.data.total, // 数据总数
            curr: attachmentView.currPage,
            theme: '#1E9FFF',
            limit: 20,
            jump: function (obj, first) {
                if (!first) {
                    const curr = obj.curr
                    attachmentView.currPage = curr
                    console.log('【TODO 完善切页】当前页', curr);
                    const loadIndex = layer.load(0);
                    $.ajax({
                        url: attachmentUrl,
                        type: "GET",
                        data: {
                            page: curr
                        },
                        dataType: "json",
                        success: function (res) {
                            console.log(res);
                            if (res.code === 0) {
                                // TODO测试数据 需删除
                                attachmentView.results = res
                                attachmentView.data = res.data.results
                                layerFileRender(res.data.results)
                                // 测试数据 需删除 end
                                controlAllCheckbox()
                                return
                            }
                            layer.msg(data.message, { icon: 0 });
                        },
                        error: function (err) {
                            layer.msg(err.message, { icon: 0 });
                        },
                        complete: function () {
                            layer.close(loadIndex);
                        }
                    });
                }
            }
        })
    }

    // 计数
    const layerFileCount = function () {
        const number = attachmentView.selected.length
        attachmentView.container.find('[count]').text(number)
        if (number === 0) {
            attachmentView.container.find('[count]').hide()
            return
        }
        attachmentView.container.find('[count]').show()
    }
    buildLayer()
    const PTAttachment = {
        make: function (config) {
            const ptadminAttachment = new attachmentUpload(config);
        }
    }
    exports(MOD_NAME, PTAttachment);
});
