/**
 * 富文本编辑器集合
 * Author:  Zane
 * Email: 873934580@qq.com
 * Date: 2022/04/09.
 */
layui.define(function (exports) {
    "use strict";
    const { common } = layui
    const MOD_NAME = "PTEditor";
    const config = {
        elem: "",
        type: "KindEditor",
    };

    const editor = {
        kind: function (elem) {
            const url = common.url('upload/kind')
            KindEditor.ready(function(K) {
                K.create(`#${elem}`, {
                    filterMode : false,
                    items :[
                        'source', '|', 'undo', 'redo', '|', 'preview', 'template', 'cut', 'copy', 'paste',
                        'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
                        'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
                        'superscript', 'clearhtml', 'quickformat', 'selectall', '|', 'fullscreen', '/',
                        'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
                        'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image',
                        'media','insertfile','table', 'hr', 'emoticons', 'baidumap', 'pagebreak',
                        'anchor', 'link', 'unlink'
                    ],
                    uploadJson : url,
                    fileManagerJson: url,
                    minHeight: 400,
                    allowFileManager: true,
                    afterBlur: function(){
                        this.sync();
                    }
                });
            });
        },
        tiny: function (elem) {
            tinymce.init({
                selector: `#${elem}`,
                mobile: {menubar: true},
                language:'zh-cn',
                plugins: 'print preview  autolink directionality visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor insertdatetime advlist lists wordcount textpattern help emoticons autosave autoresize',
                height: 650, //编辑器高度
                min_height: 400,
                fontsize_formats: '12px 14px 16px 18px 24px 36px 48px 56px 72px',
                font_formats: '微软雅黑=Microsoft YaHei,Helvetica Neue,PingFang SC,sans-serif;苹果苹方=PingFang SC,Microsoft YaHei,sans-serif;宋体=simsun,serif;仿宋体=FangSong,serif;黑体=SimHei,sans-serif;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats;知乎配置=BlinkMacSystemFont, Helvetica Neue, PingFang SC, Microsoft YaHei, Source Han Sans SC, Noto Sans CJK SC, WenQuanYi Micro Hei, sans-serif;小米配置=Helvetica Neue,Helvetica,Arial,Microsoft Yahei,Hiragino Sans GB,Heiti SC,WenQuanYi Micro Hei,sans-serif',
                extended_valid_elements:'script[src]',
                toolbar_mode : 'wrap',
                automatic_uploads : true,
                images_upload_url: common.url('upload/tiny'),
                file_picker_callback: function(callback, value, meta) {
                    console.log("file_picker_callback", callback, value, meta)
                },
                setup: function(editor){
                    editor.on('change',function(){ editor.save(); });
                },
            });

        },
        ueditor: function (elem) {
            UE.getEditor(elem);
        }
    }

    const zaneEditor = {
        init: function (options = {}) {
            options = Object.assign(config, options);
            if (editor[options.type] === undefined) {
                console.error("异常富文本类型");
                return;
            }
            editor[options.type](options.elem);
        },
        // 同步数据到textarea
        saveContent: function () {

        }

    }

    exports(MOD_NAME, zaneEditor);
});
