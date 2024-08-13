/**
 * 整合layui表单功能
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2021/12/7.
 */
layui.define(['common', 'PTAttachment', 'PTIcon', 'PTEditor', 'util', 'form', 'laydate'], function (exports) {
    "use strict";
    const { PTAttachment, PTEditor, PTIcon, common, form, $, laydate, layer } = layui;

    const MOD_NAME = 'PTForm';

    const CONFIG = {
        formClass: '.layui-form',
        url: "",                         // 手动设置提交地址
        listen: "submit(PT-submit)",     // 需要监听的form 的提交按钮
        close: true,                     // 是否自动关闭
        closeBtn: "#close",              // 关闭监听按钮
        reload: true,                    // 关闭弹出层时是否需要刷新页面
        callback: undefined              // 表单提交后的回调处理，不设置使用默认处理方式
    }

    const PTForm = {
        obj: null,
        config: {},
        init: function (options = {}) {
            $.extend(true, this.config, CONFIG, options);
            this.bindEvent();
        },
        submit: function () {
            const thiz = this
            const handle = {
                submit: () => {
                    const config = thiz.config
                    form.on(config.listen, function (data) {
                        let url = config.url || $('form').attr('action');
                        let method = $(":input[name='_method']").val() || "post";
                        common.post(url, common.formFilter(data.field), method, function (res) {
                            if (config.callback !== undefined) {
                                config.callback(res);
                                return;
                            }
                            if (res.code === 0) {
                                handle.close();
                            } else {
                                layer.msg(res.message, { icon: 2 });
                            }
                        });
                        return false;
                    });
                },
                closeBtn: () => {
                    $(thiz.config.closeBtn).click(function () {
                        thiz.config.reload = false;
                        handle.close();
                    });
                },
                close: () => {
                    if (thiz.config.close === true) {
                        let index = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(index);
                        if (thiz.config.reload === true) {
                            window.parent.location.reload();
                        }
                    }
                },
            };
            handle.submit();
            handle.closeBtn();

            return {
                // 自定义主动触发的提交事件，
                customSubmit: function () {
                    const elem = $(thiz.config.formClass).eq(0) //当前所在表单域
                    const verifyElem = elem.find('*[lay-verify]') //获取需要校验的元素
                    const filter = thiz.config.listen //获取过滤器
                    const isValid = form.validate(verifyElem)
                    if (!isValid) return
                    let field = form.getValue(null, elem);
                    layui.event.call(form, 'form', filter, {
                        elem: elem,
                        form: elem,
                        field: field
                    });
                }
            }
        },
        activeSubmit: function () {
            const $this = this
            document.onkeydown = function (e) {
                if (e.keyCode === 13) {
                    $this.obj.customSubmit();
                }
            }
            $this.obj.customSubmit();
        },
        components: {
            icon: function () {
                PTIcon.render('ICON')
            },
            attachment: function () {
                PTAttachment();
            },
            color: function (options = {}) {
                const config = {
                    format: 'rgb',
                    predefine: true,
                    alpha: false,
                    obj: ".layui-input-color",
                }
                $.extend(true, config, options);
                const colorPicker = layui.colorpicker;
                $(config.obj).each(function (i, item) {
                    config.elem = $(item).find("div");
                    config.done = function (color) {
                        $(item).find('input').val(color);
                    }
                    colorPicker.render(config);
                })
            },
            date: function (options = {}) {
                const dateList = $(".ptadmin-date");
                if (dateList.length === 0) {
                    return
                }
                const config = {
                    format: 'yyyy-MM-dd HH:mm:ss',
                    type: 'datetime',
                }
                dateList.each(function (i, item) {
                    let data = common.getDataOptions($(item)[0]);
                    $.extend(true, config, data, options);
                    laydate.render({
                        elem: $(item).find('input'),
                        ...config
                    });
                })
            },
            rate: function () {
                // 评分
                const config = {
                    obj: ".ptadmin-rate",
                };
                const rate = layui.rate;
                $(config.obj).each(function (i, item) {
                    rate.render({
                        elem: item,
                        choose: function (val) {
                            $(item).siblings("input").val(val);
                        }
                    });
                });
            },
            number: function () {
                // 数字选择框
                $(".ptadmin-number").on('click', 'span', function () {
                    const number = $(this).siblings("input");
                    const symbol = $(this).attr('ptadmin-symbol');
                    let val = number.val() || 0;
                    (symbol === '-') ? val-- : val++;
                    number.val(val);
                }).on('selectstart', 'span', function () {
                    return false;
                });
            },
            editor: function () {
                const editorObj = $("textarea.editor");
                if (editorObj.length === 0) {
                    return;
                }
                editorObj.each(function (i, item) {
                    let type = $(item).attr('data-type') || 'tiny';
                    let elem = $(item).attr('id');
                    console.log(type)
                    PTEditor.init({ elem: elem, type: type });
                });
            }
        },
        bindEvent: function () {
            this.components.icon();
            this.components.attachment();
            this.components.color();
            this.components.date();
            this.components.rate();
            this.components.number();
            this.components.editor();
            // 开启提交监听
            this.obj = this.submit();
        }
    }

    exports(MOD_NAME, PTForm);
});
