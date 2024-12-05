layui.define(function(exports) {
    "use strict";
    const { $, laytpl, common, upload } = layui
    const MOD_NAME = 'PTDemo'
    const BOX = $('.ptadmin-add-demo-table')
    const uploadDemoImage = function(){
        upload.render({
            elem: '.ptadmin-demo-code-upload',
            url: common.url("upload"),
            done: function (res) {
                const $this = this.item
                const prevElem =$this.prevAll('.image-success')
                if (prevElem.length > 0) {
                    $this.prevAll('.image-success').remove()
                }
                $this.prevAll('input').val(res.data.url)
                $this.before(`
                                <div class="ptadmin-image image-html image-success">
                                    <img class="layui-img-content" src="${res.data.url}"/>
                                    <div class="del delete-file"> <i class="layui-icon layui-icon-delete"></i> </div>
                                </div>
                        `)
            },
            error: function () {
                return layer.msg('上传失败');
            },
        });
    }
    // 上传初始化
    if(BOX.find('tbody').children().length >0){
        uploadDemoImage()
    }

    // 删除二维码事件
    BOX.on(`click`,`.image-success .delete-file`,function(){
        $(this).parent().siblings('input').val('')
        $(this).parents('.image-success').remove()
    })

    // 删除地址或图片行数据
    BOX.on(`click`,`.del-row`,function(e){
        e.stopPropagation()
        $(this).parents('tr').remove()
        if (BOX.find('tbody').children().length === 1){
            $(".empty_row").css('display', 'table-row')
        }
    })

    /**
     * 应用演示地址
     */
    const demoAdd = function () {
        const type = $(this).attr('data-type')
        BOX.addClass('ptadmin-add-demo-table-show')
        const html = $("#demo_html").html()
        laytpl(html).render({type: type, idx: new Date().getTime()}, function (str) {
            BOX.find('tbody').append(str)
            $(".empty_row").css('display', 'none')
            if (type !== 'address') {
                uploadDemoImage()
            }
        })
    }

    const events = {
        demoAdd: demoAdd
    }

    $(".ptadmin-add-demo-table-box").on("click", "*[ptadmin-event]", function (e) {
        const event = $(this).attr("ptadmin-event")
        if (events[event] !== undefined) {
            e.stopPropagation()
            events[event].call(this, e)
        }
    })


    exports(MOD_NAME, {})
})
