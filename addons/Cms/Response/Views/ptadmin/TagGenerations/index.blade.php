@extends('ptadmin.layouts.base')

@section("content")
    <div class="ptadmin-tag-generations-box">
        <aside class="aside-box">
            <div class="title">标签生成器</div>
        </aside>
        <main class="main-box">
            <section class="section-box"></section>
            <footer class="tag-generations-footer layui-btn-group">
                <button type="button" class="layui-btn create">生成模板标签</button>
                <button type="button" class="layui-btn layui-btn-danger reset">重置</button>
            </footer>
        </main>
    </div>
@endsection

@section("script")
    <script>
        layui.use(['element','form', 'layCascader','layer','MOCK'], function () {
            const { element, form , layCascader,layer, MOCK} = layui
            const loadIndex = layer.load(0);

            const viewData = {
                result:undefined,
                current: undefined, // 当前数据
                currentTagText:'', // 当前选择单标签
                layCascader:{} // 下拉多选
            }

            const ASIDE = 'aside-box'
            const NAVBOX = 'nav-box'
            const SECTIONBOX = 'section-box'
            const RESULTSID = 'generate-results'
            const events = {

                // 导航事件
                handleNav:function(obj){
                    const elem = obj.elem
                    const id = elem.data('id')
                    elem.addClass('active').siblings().removeClass('active')
                    if(id === viewData.current.id) {
                        return
                    }
                    viewData.current = viewData.result.find(item=>item.id === id)
                    viewData.layCascader = {}
                    viewData.currentTagText = ''
                    init()
                },

                // 点击单标签
                handleTag(obj){
                    const elem = obj.elem
                    const content = elem.data('content')
                    elem.parent().addClass('generations-tag-active').siblings().removeClass('generations-tag-active')
                    viewData.currentTagText = content
                    $('#generate-results').val(viewData.currentTagText)
                    const formData = form.val('tag-generations-form');
                    setSingleText(formData)
                },

                // 复制事件
                copy(){
                    const ele = document.getElementById(RESULTSID);
                    if(!ele.value){
                        layer.msg('请先生成模板标签后进行复制');
                        return
                    }
                    ele.select();
                    document.execCommand('copy', false, null);
                    layer.msg('复制成功');
                }
            }

            // 渲染导航
            const renderNav = function(){
                const asideBox = $(`.${ASIDE}`)
                const itemHtml = []
                viewData.result.forEach((item,idx) => {
                    itemHtml.push(`<li class="item ${!idx?'active':''}" data-id="${item.id}" ptadmin-event="handleNav">${item.title}</li>`)
                });
                asideBox.append(`<ul class="nav-box">${itemHtml.join('')}</ul>`)
            }

            // 渲染tag
            const renderTags = function(dom){
                const tagHtml = []
                viewData.current.directs.forEach(item => {
                    let contentBox = $(`<div class="tag-generations-content">
                                            <div class="tag-generations-common-header"> ${item.title} </div>
                                            <div class="layui-row layui-col-space10 tags-content"></div>
                                        </div>`)

                    item.params.forEach(ele => {
                        let tag = ``
                        let placeholder = ele.placeholder ? ele.placeholder:'默认无提示信息'
                        let tip = ele.tip ? `<i class="layui-icon layui-icon-about tips-layer" data-text="${ele.tip}"></i>`:''
                        const randomId = Math.random().toString(16).substring(2, 8);

                        // 单标签
                        if(ele.type === 'content'){
                            tag = `
                                <div class="layui-col-sm6 layui-col-md4 layui-col-lg3 generations-tag" >
                                    <a href="javascript:void(0);" data-content=${ele.content} ptadmin-event="handleTag">${ele.title}</a>
                                </div>
                            `
                        }

                        // 文本
                        if(ele.type === 'text'){
                            tag = `
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">${tip} ${ele.title}  </label>
                                    <div class="layui-input-block">
                                        <input type="text" name="${ele.name}" placeholder="${placeholder}" class="layui-input">
                                    </div>
                                </div>`
                        }

                        // 数字
                        if(ele.type === 'number'){
                            tag=`
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">${tip} ${ele.title} </label>
                                    <div class="layui-input-block">
                                        <input type="number" name="${ele.name}" lay-affix="number" value="${ele.default}" placeholder="${placeholder}" class="layui-input">
                                    </div>
                                </div>
                            `
                        }

                        // 下拉选择
                        if(ele.type ==='select'){
                            tag=`
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">${tip}${ele.title}</label>
                                    <div class="layui-input-block">
                                        <select lay-search="" name="${ele.name}">
                                        <option value="">${placeholder}</option>
                            `
                            ele.options.forEach(it => {
                                tag += `<option value="${it.value}" ${ele['default']===it.value?'selected':''}>${it.label}</option>`
                            });
                                tag += `</select></div> </div>`
                        }

                        // 下拉多选
                        if(ele.type ==='multiple'){
                            tag = `
                                <div class="layui-col-md6">
                                    <label class="layui-form-label">${tip}${ele.title}</label>
                                    <div class="layui-input-block">
                                        <input id="${randomId}" name="${ele.name}" class="layui-input"/>
                                    </div>
                                </div>
                            `
                        }
                        contentBox.find('.tags-content').append(tag)
                        // 渲染下拉多选
                        if(ele.type ==='multiple'){
                            viewData.layCascader[randomId] =  layCascader({
                                elem:contentBox.find(`#${randomId}`),
                                clearable: true,
                                collapseTags: true,
                                minCollapseTagsNumber: 2,
                                options: ele.options,
                                placeholder:placeholder,
                                filterable: true,
                                props: {
                                    multiple: true,
                                    checkStrictly: true,
                                },
                                value: ele['default'],
                            });
                        }
                    });
                    tagHtml.push(contentBox)
                    contentBox = ''
                });
                dom.find('.tag-generations-form').append(tagHtml)
            }

            // 渲染tag容器
            const renderTagBox = function(){

                // 提示文本
                let hint = $(`<x-hint></x-hint>`)
                hint.append(`<p><strong>${viewData.current.title}（${viewData.current.name}）</strong>`)
                const hintList = viewData.current.hint
                if (Array.isArray(hintList)) {
                    hintList.forEach((item,idx) => { hint.append(`<p>${idx+1}、${item}</p>`) });
                }else{
                    hint.append(`<p>1、${hintList}</p>`)
                }

                // 内容
                let contentContainer = $(`
                                            <div class="content-container">
                                                <div class="layui-row layui-col-space10">
                                                    <form class="layui-col-md8 layui-form tag-generations-form" lay-filter="tag-generations-form">
                                                        <button type="reset"  class="layui-btn layui-btn-primary  tag-generations-form-reset">重置</button>
                                                    </form>
                                                    <div class="layui-col-md4 result-box">
                                                        <div class="tag-generations-common-header"> 标签预览 </div>
                                                        <textarea name="" class="layui-textarea" id="generate-results"></textarea>
                                                        <div class="result-btns">
                                                            <button type="button" class="layui-btn">预留文本</button>
                                                            <button type="button" class="layui-btn layui-bg-blue" ptadmin-event="copy">复制</button>
                                                        </div>

                                                        <div class="tag-generations-common-header"> 执行结果 </div>
                                                        <textarea name="" class="layui-textarea"></textarea>
                                                    </div>
                                                </div>
                                            </div>`)


                renderTags(contentContainer)
                const anim = $(`<div class="layui-anim layui-anim-fadein"></div>`)
                anim.html([hint , contentContainer])
                $(`.${SECTIONBOX}`).html(anim)
                form.render()
                hint = ''
                contentContainer = ''
            }


            // 提示
            const tipsFn = function(){
                let tips = ''
                $(".tips-layer").on({
                    mouseenter: function () {
                        let text = $(this).data('text');
                        tips = layer.tips(text, this,{area:'200', time:10000});
                    },
                    mouseleave: function () {
                        layer.close(tips);
                    }
                });
            }

            // 初始化
            const init = function(){
                renderTagBox()
                tipsFn()
                $('*[ptadmin-event]').on('click',function(){
                    const event = $(this).attr('ptadmin-event')
                    const obj = {
                        elem:$(this)
                    }
                    events[event].call(undefined,obj)
                })
            }

            $.ajax({
                url: "{{admin_route('cms/topics')}}",
                type: "GET",
                dataType: "json",
                success: function(data) {
                    if(data.code === 0){
                        viewData.result = MOCK
                        viewData.current = viewData.result[0]
                        renderNav()
                        init()
                        return
                    }
                },
                complete:function(){
                    layer.close(loadIndex)
                }
            });

            // 拼接单标签
            const setSingleText = function(formData){
                const arrStr = []
                for (const key in formData) {
                    if(formData[key]){
                        arrStr.push(`"${formData[key]}"`)
                    }
                }
                let str = viewData.currentTagText + '(' + arrStr.join(', ') +')'
                if(arrStr.length === 0) { str = viewData.currentTagText }
                $(`#${RESULTSID}`).val(str)
            }

            // 取值
            $('.tag-generations-footer .create').on('click',function(){
                const formData = form.val('tag-generations-form');

                // 单标签
                if(viewData.current.single){
                    if(!viewData.currentTagText){
                        layer.msg('请先选择标签');
                        return
                    }
                    setSingleText(formData)
                }

                // 闭合标签
                if(!viewData.current.single){
                    const start = viewData.current.start_tag
                    const end = viewData.current.end_tag
                    const arrStr = [] // 转换后的数组
                    // 转换字符串
                    for (const key in formData) {
                        if(formData[key]){
                            try {
                                const parsed = JSON.parse(formData[key]);
                                const convert = parsed.join(',')
                                arrStr.push(`${key}="${convert}"`)
                            } catch (e) {
                                arrStr.push(`${key}="${formData[key]}"`)
                            }
                        }
                    }
                    // 拼接字符串
                    let str = start + '(' + arrStr.join(', ') + ')' + '\n' + end;
                    if(arrStr.length===0){
                        str = start + '\n' +  end // 如果没有选择展示标签，则只拼接start标签
                    }
                    $(`#${RESULTSID}`).val(str)
                }
            })

            // 重置
            $('.tag-generations-footer .reset').on('click',function(){
                $('.tag-generations-form-reset').click()
                for (const key in viewData.layCascader) {
                    viewData.layCascader[key].clearCheckedNodes()
                }
            })
        })
    </script>
@endsection
