/**
 * 附件管理
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2021/12/7.
 */
layui.define(['common'],function(exports){
    "use strict";
    const { common, $ } = layui;
    const MOD_NAME = "PTAttachment";
    const ATTACHMENT_ELEM = ".ptadmin-image-list";  // 附件列表元素
    const ATTACHMENT_URL = "attachment"; // 附件列表请求接口

    const PTAttachment = function () {
        // 渲染页面
        const render = (images, name, options = {}) => {
            const html = (data) => {
                let {url, id, name} = data;
                return `<div class="ptadmin-image image-html">\n` +
                    `                <input type="hidden" value="${url}" name="${name}">\n` +
                    `                <img src="${url}" class="layui-img-content" alt="">\n` +
                    `                <div class="layui-img-delete">\n` +
                    `                    <i class="layui-icon layui-icon-delete"></i>\n` +
                    `                </div>\n` +
                    `                <div class="layui-img-bg"></div>\n` +
                    `                <div class="layui-img-btn">\n` +
                    `                    <a href="javascript:void(0);" class="layui-btn layui-btn-xs btn-theme layui-img-open">\n` +
                    `                        <i class="layui-icon layui-icon-eye"></i></a>\n` +
                    `                </div>\n` +
                    `            </div>`;
            }

            let content = "";
            for (let i = 0; i < images.length; i++) {
                let data = { url: images[i].url, id: images[i].id, name: name }
                // 可以设置使用回调函数的方式自己渲染页面， 但是需要包裹到class image-html中
                if (options.template !== undefined && typeof options.template === "function") {
                    content += options.template(data);
                } else {
                    content += html(data);
                }
            }
            $(options.id).prepend(content);
        }

        const handle = function (obj, config) {
            const func = {
                options: {
                    id: undefined,
                    limit: 1,
                    openBtn: ".upload",             // 点击弹窗按钮,
                    delBtn: ".layui-img-delete",    // 删除按钮
                    bigPicBtn: ".layui-img-open",   // 点击查看大图按钮
                    imageHtml: ".image-html",       // 文件列表展示div class，
                    template: undefined,            // 待渲染模版 自定义渲染函数
                },
                init: function (obj, config = {}) {
                    this.options.id = "#" + obj.getAttribute("id");

                    $.extend(true, this.options, config);
                    this.uploadBtnShow();
                },
                uploadBtnShow: function () {
                    if (!this.isLimit()) {
                        $(this.options.id).find(this.options.openBtn).hide();
                        this.del();
                        this.bigPhoto();
                        return;
                    }
                    // 页面上不存在上传按钮
                    if ($(this.options.id).find(this.options.openBtn).length === 0) {
                        let html = '<div class="ptadmin-image upload" >' +
                                        '<i class="layui-icon layui-icon-add-1 layui-img-icon"></i>' +
                                    '</div>';
                        $(this.options.id).append(html);

                        // 监听上传事件
                        let thiz = this;

                        $(this.options.id).find(this.options.openBtn).on("click", function () {
                            thiz.open();
                        });
                    } else {
                        $(this.options.id).find(this.options.openBtn).show();
                    }
                },
                open: function () {
                    let url = common.url(ATTACHMENT_URL) + "?limit=" + this.options.limit + "&currentLen="+ this.getLength() ;
                    let obj = this;
                    common.open(url, "请选择", {
                        yes: function (i, layerObj) {
                            let { getActiveData } = window[layerObj.find('iframe')[0]['name']];
                            let img = getActiveData();
                            if (img.length === 0) {
                                return false;
                            }
                            layer.closeAll();
                            render(img, obj.options.name, {
                                id: obj.options.id,
                                template: obj.options.template,
                            });
                            // 刷新
                            obj.uploadBtnShow();
                            obj.del();
                            obj.bigPhoto();
                        },
                        cancel: function (){
                            layer.closeAll();
                        },
                        btn: ['确认']
                    });
                },
                getLength: function () {
                    return $(this.options.id).find(this.options.imageHtml).length;
                },
                isLimit: function () {
                    return this.getLength() < this.options.limit;
                },
                del:function () {
                    let thiz = this;
                    $(this.options.delBtn).click(function () {
                        $(this).parent().remove();
                        thiz.uploadBtnShow();
                    });
                },
                // 查看大图
                bigPhoto: function () {
                    $(this.options.bigPicBtn).click(function () {
                        let src = $(this).parent().parent().find("img").attr("src");
                        let json = {
                            "title": "",
                            "id": 1,
                            "start": 0,
                            "data": [
                                {
                                    "alt": "",
                                    "pid": '1',
                                    "src": src,
                                }
                            ]
                        };
                        layer.photos({
                            photos: json,
                            anim: 5
                        });
                    });
                }
            }
            func.init(obj, config)
        }

        const imgLists = $(ATTACHMENT_ELEM);

        imgLists.each(function (i, item) {
            handle(item, common.getDataOptions(item));
        })
    }

    exports(MOD_NAME, PTAttachment);
});


/**
 * 1、资源上传功能封装
 */
