@extends('ptadmin.layouts.base')
@section("content")
<!-- URL规则说明 -->
<div class="ptadmin-seo-config-container">
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            <li class="layui-this">URL配置</li>
            <li>Sitemap</li>
        </ul>
        <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
                @include("cms::ptadmin.seo.url", ['seoConfigData' => $seoConfigData ?? null])
            </div>
            <div class="layui-tab-item">
                @include("cms::ptadmin.seo.sitemap", ['sitemapConfigData' => $sitemapConfigData ?? null])
            </div>
        </div>
    </div>
</div>
<!-- URL规则说明 end -->
@endsection

@section("script")
    <script>
        layui.use(['form','element','common','layer','util'], function () {
            /** URL规则说明 */
            const { form, util,element,layer, common} = layui;
            form.on('submit(submit-url)', function(data){
                const field = data.field; // 获取表单字段值
                $.ajax({
                    url: '{{admin_route('cms/seo-update')}}',
                    data:field,
                    type:'put',
                    success:function(res){
                        if (res.code === 0) {
                            layer.msg('保存成功', {icon: 1});
                            setTimeout(()=>{
                                window.location.reload()
                            },1500);
                        } else {
                            layer.msg(res.message, {icon: 2});
                        }
                    },
                    error:function (e){
                        layer.msg(e);
                    }
                })
            });
            element.tab({
                headerElem: '.ptadmin-url-tabs>div',
                bodyElem: '.ptadmin-url-section>div'
            });
            $('[format-hover]').on('click',function(){
                const formatDemo = $(this).siblings('[format-demo]');
                if( formatDemo.attr('style') === 'display: inline;'){
                    $(this).siblings('[format-demo]').hide()
                }else{
                    $(this).siblings('[format-demo]').show()
                }
            })

            const urlConfig = {
                '{category}':'dom',
                '{category_id}':'1',
                '{mod}':'dom',
                '{mod_id}':'1',
                '{topic}':'dom',
                '{topic_id}':'1',
                '{tag_id}':'1',
                '{page}':'dom'
            }

            const formatConfig = {
                '{category_title}':'dom',
                '{page}':'dom',
                '{site_title}':'dom',
                '{title}':'dom',
                '{topic_title}':'dom',
                '{page}':'dom',
                '{tag_title}':'dom',
                '{mod_title}':'dom'
            }

            const replaceStr = function(val,obj){
                return val.replace(/{([^{}]+)}/g, (match) => {
                    return obj[match] || match; // 如果没有找到对应的值，则保留原匹配字符串
                });
            }

            $(".url-demo").on('click',function(){
                const urlVal = $(this).prev().val()
                const frontVal =  $(this).closest('.layui-form-item').siblings().find('input[input-type="front"]').val()
                const result = replaceStr( urlVal, urlConfig )
                const content = "{{config('app.url')}}" + '/' + frontVal + '/' + result
                layer.open({
                        type: 1,
                        title: false, // 不显示标题栏
                        shadeClose: true, // 点击遮罩关闭层
                        content: `<div style="padding: 15px;">${content}</div>`
                });
            });

            $('.format-demo').on('click',function(){
                const formatVal = $(this).prev().val()
                const result = replaceStr( formatVal, formatConfig )
                const content =  "{{config('app.url')}}" + '/' + result
                layer.open({
                        type: 1,
                        title: false, // 不显示标题栏
                        shadeClose: true, // 点击遮罩关闭层
                        content: `<div style="padding: 15px;">${content}</div>`
                });
            })
            /** URL规则说明 end*/

            /** Sitemap */
            form.on('checkbox(sitemap_type)', function(data){
                const elem = data.elem; // 获得 checkbox 原始 DOM 对象
                const checked = elem.checked; // 获得 checkbox 选中状态
                const value = elem.value; // 获得 checkbox 值
                const othis = data.othis; // 获得 checkbox 元素被替换后的 jQuery 对象

                if(checked){
                    othis.siblings('.operate').show()
                }else{
                    othis.siblings('.operate').hide()
                }
            });

            $('.look-template >a').on('click',function(){
                const templatePath = $(this).siblings('.text')
                if(templatePath.hasClass('show')){
                    templatePath.removeClass('show')
                }else{
                    templatePath.addClass('show')
                }
            })

            let tips = ''
            $(".tips-layer").on({
                mouseenter: function () {
                    let text = $(this).data('text');
                    tips = layer.tips(text, this,{area:'200', tips: 2, time:10000});
                },
                mouseleave: function () {
                    layer.close(tips);
                }
            });
            form.on('submit(submit-sitemap)', function(data){
                const field = data.field; // 获取表单字段值
                console.log(field);
                $.ajax({
                    url: '{{admin_route('cms/sitemap-save')}}',
                    data:field,
                    type:'put',
                    success:function(res){
                        if (res.code === 0) {
                            layer.msg('保存成功', {icon: 1});
                            setTimeout(()=>{
                                window.location.reload()
                            },1500);
                        } else {
                            layer.msg(res.message, {icon: 2});
                        }
                    },
                    error:function (e){
                        layer.msg(e);
                    }
                })
            });
            /** Sitemap end */
        });
    </script>
@endsection

@section("head")
    <style>
        .layui-table-tree-iconCustom i {
            margin-right: 5px;
        }
    </style>
@endsection
