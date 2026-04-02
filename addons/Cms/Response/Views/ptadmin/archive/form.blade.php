@extends('ptadmin.layouts.base')

@section("content")
    <div class="layui-fluid">
        <div class="layui-card-body">
            <form action="" class="layui-form">
                <input type="hidden" name="id" value="{{ data_get($dao, 'id') }}">
                <input type="hidden" name="mod_id" value="{{ data_get($dao, 'mod_id') }}">
                @method(isset($dao['id']) ? 'put' : 'post')
                <div class="layui-row layui-col-space10">
                    <div class="layui-col-md8">
                        <fieldset class="layui-elem-field">
                            <legend>基础信息</legend>
                            <div class="layui-field-box">
                                {!! \PTAdmin\Build\Layui::select("category_id", '所属栏目', data_get($dao, 'category_id'))->default($categoryId ?? 0)->options($category)->required() !!}
                                {!! \PTAdmin\Build\Layui::text("title", '文章标题', data_get($dao, 'title'))->required() !!}
                                {!! \PTAdmin\Build\Layui::text("subtitle", '副标题', data_get($dao, 'subtitle')) !!}
                                {!! \PTAdmin\Build\Layui::text("author", '作者', data_get($dao, 'author')) !!}
                                {!! \PTAdmin\Build\Layui::checkbox("attribute", '推荐属性', data_get($dao, 'attribute'))->options(\Addon\Cms\Enum\AttributeEnum::getMaps()) !!}
                                {!! \PTAdmin\Build\Layui::img("cover", '封面图', data_get($dao, 'cover')) !!}
                                {!! \PTAdmin\Build\Layui::images("picture", '相册', data_get($dao, 'picture')) !!}
                                @if(1 === (int)$currentCategory['is_related'])
                                    {!! \PTAdmin\Build\Layui::select("related_category_id", '关联栏目', data_get($dao, 'related_category_id'))->options($categoryList) !!}
                                @endif
                                {!! \PTAdmin\Build\Layui::richTxt("content", '内容', data_get($dao, 'content')) !!}
                            </div>
                        </fieldset>
                        @if($render)
                            <fieldset class="layui-elem-field">
                                <legend>扩展内容</legend>
                                <div class="layui-field-box">
                                    {!! $render !!}
                                </div>
                            </fieldset>
                        @endif
                    </div>
                    <div class="layui-col-md4">
                        <fieldset class="layui-elem-field">
                            <legend>其他内容</legend>
                            <div class="layui-field-box">
                                {!! \PTAdmin\Build\Layui::radio("is_comment", '是否评论', data_get($dao, 'is_comment'))->options(\PTAdmin\Admin\Enum\WhetherEnum::getMaps()) !!}
                                {!! \PTAdmin\Build\Layui::radio("is_visitor", '游客访问', data_get($dao, 'is_visitor'))->options(\PTAdmin\Admin\Enum\WhetherEnum::getMaps()) !!}
                                {!! \PTAdmin\Build\Layui::number("views", '访问量', data_get($dao, 'views')) !!}
                                {!! \PTAdmin\Build\Layui::number("praise_num", '点赞数', data_get($dao, 'praise_num')) !!}
                                {!! \PTAdmin\Build\Layui::number("tread_num", '踩数量', data_get($dao, 'tread_num')) !!}
                                {!! \PTAdmin\Build\Layui::number("comment_num", '评论数', data_get($dao, 'comment_num')) !!}
                                {!! \PTAdmin\Build\Layui::number("collection_num", '收藏数', data_get($dao, 'collection_num')) !!}
                                {!! \PTAdmin\Build\Layui::number("weight", '权重', data_get($dao, 'weight')) !!}
                            </div>
                        </fieldset>
                        <fieldset class="layui-elem-field">
                            <legend>SEO</legend>
                            <div class="layui-field-box">
                                {!! \PTAdmin\Build\Layui::text("seo_title", 'SEO标题', data_get($dao, 'seo_title')) !!}
                                {!! \PTAdmin\Build\Layui::text("seo_keyword", 'SEO关键词', data_get($dao, 'seo_keyword')) !!}
                                {!! \PTAdmin\Build\Layui::textarea("seo_doc", 'SEO描述', data_get($dao, 'seo_doc')) !!}
                            </div>
                        </fieldset>
                    </div>
                </div>
            </form>
            <div class="footer">
                @if(!isset($dao['id']))
                    <div class="checkbox">
                        <input type="checkbox" name="continue" id="continue">
                        <label for="continue">
                            保存后继续
                        </label>
                    </div>
                @endif
                <div class="container-footer layui-btn-group">
                    <button class="layui-btn layui-btn-sm layui-bg-blue" ptadmin-event="submit">保存</button>
                    <button class="layui-btn layui-btn-sm" ptadmin-event="close">关闭</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        .footer{
            clear: both;
            display: flex;
            justify-content: center;
        }
        .checkbox{
            display: flex;
            align-items: center;
            margin-right: 20px;

        }
        .checkbox label {
            margin-left: 5px;
            cursor: pointer;
        }
    </style>
@endsection

@section("script")
<script>
    layui.use(["PTForm", "form", "common"], function () {
        const { PTForm, form, common } = layui
        PTForm.init();
        const events = {
            close: function (is_refresh = false) {
                if (window === window.parent || window.parent.layui === undefined) {
                    return
                }
                if (is_refresh) {
                    if (window.parent.cms !== undefined) {
                        window.parent.cms.refresh()
                    }
                }
                window.parent.layui.layer.closeAll()
            },
            submit: function () {
                const elem = $("form").eq(0) //当前所在表单域
                const verifyElem = elem.find('*[lay-verify]') //获取需要校验的元素
                const isValid = form.validate(verifyElem)
                if (!isValid) {
                    return
                }
                const field = form.getValue(null, elem);
                const method = $("input[name=_method]").val();

                common.post($('form').attr('action'), common.formFilter(field), method, function (res) {
                    if (res.code === 0) {
                        layer.msg(res.message);
                        setTimeout(() => {
                            const con = $("#continue");
                            if (con.length > 0 && con[0].checked) {
                                location.reload()
                            } else {
                                events.close(true)
                            }
                        }, 1500)
                    } else {
                        layer.msg(res.message, { icon: 2 });
                    }
                });
            }
        }
        // 监听封面图片改变事件
        $('div[data-name="cover"]').on('cover-change', function () {
            const img = arguments[0]['detail']['val'][0]['url'];
            // 获取富文本编辑器
            const editor = window['current_content_editor'];
            if (editor === undefined) {
                return;
            }
            if (editor.getContent === undefined) {
                return;
            }
            // 如果富文本编辑器内容为空，则将封面图设置为内容
            if(null === editor.getContent() || editor.getContent().length === 0) {
                const content = editor.setContent("<p><img style='display: block; margin-left: auto; margin-right: auto;' src='"+img+"' alt='' /></p>");
                $("textarea[id='content']").val(content);
            }
        })


        $('.layui-fluid').on("click", "*[ptadmin-event]", function () {
            const event = $(this).attr("ptadmin-event")
            if (events[event] !== undefined) {
                events[event].call()
            }
        })
    });

</script>
@endsection
