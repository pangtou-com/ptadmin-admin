layui.define(function (exports) {
    const { $ } = layui
    const MOD_NAME = 'PTMultipleSelect'
    // 已选盒子class
    const MULTIPLE_SELECTED = 'pt-selected'
    // 选择框激活class
    const MULTIPLE_ACTIVE_BOX = 'pt-multiple-box-active'
    // 选项盒子class
    const MULTIPLE_OPTIONS = 'pt-options'
    // 最外层盒子class
    const MULTIPLE_BOX = 'pt-multiple-box'
    // 计数盒子class
    const MULTIPLE_COUNT = 'pt-count'
    /** 渲染选项 */
    const renderOptions = (function (obj) {
        const ele = $(obj.ele)
        let optionsItem = ''
        obj.options.forEach(item => {
            optionsItem += `<div class="pt-item" isActive="false" isDisabled="${item.disabled || false}" val="${item.value}">
                        <span class="label">${item.label}</span>
                        <i class="layui-icon layui-icon-ok"></i>
                    </div>`
        });
        ele.children(`.${MULTIPLE_OPTIONS}`).append(optionsItem)
        /** 存在默认值时 */
        if (obj.value) {
            const defaultArr = obj.value.split(',')
            const same = obj.options.filter(item => defaultArr.indexOf((item.value).toString()) > -1)
            const items = ele.children(`.${MULTIPLE_OPTIONS}`).children('.pt-item')
            ele.children('.input').val(obj.value)
            $.each(items, function (idx, item) {
                let $item = $(item)
                const val = $item.attr('val')
                same.forEach(res => {
                    if (+res.value === +val) {
                        $item.attr('isActive', true)
                    }
                });
            })
        }
    })

    /** 被选择的数据展示 */
    const renderSelected = (function (obj) {
        /** 获取input的值 */
        const ele = $(obj.ele)
        const inputVal = ele.children('.input').val()
        if (inputVal) {
            /** 获取第一个数据设置为展示数据 */
            const inputValArr = inputVal.split(',')
            /** 通过选中的值匹配数据 */
            const firstData = inputValArr[0]
            const number = inputValArr.length - 1
            const same = obj.options.find((item) => {
                return +item.value === +firstData
            })
            /** 展示被选中数据第一个 */
            const firstSelectedStr = `
                        <div class="pt-selected-item" val="${same.value}">
                            <span class="label">${same.label}</span>
                            <i class="layui-icon layui-icon-close"></i>
                        </div>`
            /** 隐藏其他被选择的盒子 鼠标点击显示隐藏数据 */
            ele.children('.pt-placeholder').attr('show', false)
            ele.children(`.${MULTIPLE_SELECTED}`).attr('show', true)
            ele.children(`.${MULTIPLE_SELECTED}`).children('.default-first').html(firstSelectedStr)
            if (number) {
                let tooltips = ''
                /** 找到隐藏的数据 */
                const sameInputValArr = inputValArr.filter(item => firstData.indexOf((item).toString()) === -1)
                const sameData = obj.options.filter(item => sameInputValArr.indexOf((item.value).toString()) !== -1)
                ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).children('.number').text(number)
                ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).show()
                sameData.forEach(item => {
                    tooltips += `
                                    <li class="item" val="${item.value}">
                                        <span class="label">${item.label}</span>
                                        <i class="layui-icon layui-icon-close"></i>
                                    </li>
                                `
                });
                ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).children('.pt-collapse-tooltip').html(tooltips)
            }
            else {
                ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).hide()
            }
        } else {
            ele.children('.pt-placeholder').attr('show', true)
            ele.children(`.${MULTIPLE_SELECTED}`).attr('show', false)
        }

    })
    const init = function (obj) {
        const ele = $(obj.ele)
        const inputStr = `<input class="input" type="hidden" name="${obj.name}" value="" lay-verify="${obj.required ? 'required' : ''}"/>`
        const placeholder = `<div class="pt-placeholder" show="true">${obj.placeholder || 'Select'}</div>`
        /** 右侧箭头 */
        const rightArrowStr = `	<div class="pt-iconfont">
                                    <i class="layui-icon layui-icon-right"></i>
                                </div>`
        /** 被选中盒子 */
        const selectedStr = `<div class="${MULTIPLE_SELECTED}" show="false"></div>`
        /** 计数盒子 */
        const hiddenSelectedOtherBox = `<div class="${MULTIPLE_COUNT}" tooltips="false">+<span class="number"></span></div>`
        /** 提示盒子 */
        const tooltipsBox = `<ul class="pt-collapse-tooltip"></ul>`
        /** 默认展示的第一个盒子 */
        const firstBox = `<div class="default-first"></div>`
        /** 选项盒子 */
        const optionsBox = `<div class="${MULTIPLE_OPTIONS}"></div>`
        ele.append(inputStr, placeholder, selectedStr, rightArrowStr, optionsBox)
        ele.children(`.${MULTIPLE_SELECTED}`).append(firstBox)
        ele.children(`.${MULTIPLE_SELECTED}`).append(hiddenSelectedOtherBox)
        ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).append(tooltipsBox)
        /** 选项 */
        renderOptions(obj)
        /** 被选中的数据 */
        renderSelected(obj)
    }

    /** 关闭选项 */
    const closeSelects = function () {
        /** 存在多个 每次点击关闭非当前点击的选择框 */
        $.each($(`.${MULTIPLE_BOX}`), function (idx, item) {
            const $item = $(item)
            $item.removeClass(MULTIPLE_ACTIVE_BOX)
            $item.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).attr('tooltips', false)
            $item.children(`.${MULTIPLE_OPTIONS}`).fadeOut(300)
        })
    }
    /** 点击打开选项 */
    const openSelects = function (obj) {
        const ele = $(obj.ele)
        ele.click(function (e) {
            e.stopPropagation()
            const isActive = $(this).is(`.${MULTIPLE_ACTIVE_BOX}`)
            ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).attr('tooltips', false)
            closeSelects()
            if (!isActive) {
                $(this).addClass(MULTIPLE_ACTIVE_BOX)
                $(this).children(`.${MULTIPLE_OPTIONS}`).fadeIn(300)
            } else {
                $(this).removeClass(MULTIPLE_ACTIVE_BOX)
                $(this).children(`.${MULTIPLE_OPTIONS}`).fadeOut(300)
            }
        })
    }

    /**
 *  @param {Object} obj
 *  @param {string} obj.name 输入框name属性
 *  @param {Array<{id:number,label:string,value:number | string,disabled:Boolean}>} obj.options label：选项标题，value：选项值,disabled:禁用某项
 *  @param {string} obj.ele 元素class
 *  @param {string} obj.placeholder 提示文字：默认‘Select’
 *  @param {string} obj.value   默认值 例：'1,2,3'
 *  @param {boolean} obj.disabled   禁用整个表单
 *  @param {boolean} obj.required   是否必选
 */
    const render = function (obj) {
        const ele = $(obj.ele)
        init(obj)
        /** 禁用整个 */
        if (obj.disabled) {
            ele.attr('disabled', obj.disabled)
            return
        } else {
            ele.removeAttr('disabled')
        }
        openSelects(obj)
        /** 绑定鼠标点击查看全部事件 */
        ele.delegate(`.${MULTIPLE_COUNT}`, 'click', function (e) {
            e.stopPropagation()
            const isShowTooltips = JSON.parse($(this).attr('tooltips'))
            /** 存在多个 每次点击关闭非当前点击的选择框 */
            const ptCountBox = $(`.${MULTIPLE_BOX}`).children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`)
            $.each(ptCountBox, function (idx, item) {
                const $item = $(item)
                $item.attr('tooltips', false)
            })
            if (isShowTooltips) {
                $(this).attr('tooltips', false)
            } else {
                $(this).attr('tooltips', true)
                ele.removeClass(MULTIPLE_ACTIVE_BOX)
                ele.children(`.${MULTIPLE_OPTIONS}`).fadeOut(300)
            }
        })

        /** 绑定点击选项 */
        ele.delegate('.pt-item', 'click', function (e) {
            e.stopPropagation()
            const disabled = JSON.parse($(this).attr('isDisabled'))
            const selectVal = $(this).attr('val')
            let inputVal = ele.children('.input').val() ? ele.children('.input').val().split(',') : []
            if (disabled) return
            const index = inputVal.indexOf(selectVal)
            if (index === -1) {
                inputVal.push(selectVal)
                $(this).attr('isActive', true)
            } else {
                inputVal.splice(index, 1);
                $(this).attr('isActive', false)
            }
            ele.children('.input').val(inputVal)
            renderSelected(obj)
        })

        /** 绑定删除选项按钮 */
        ele.delegate('.layui-icon-close', 'click', function (e) {
            e.stopPropagation()
            const selectVal = $(this).parent().attr('val')
            const openSelects = ele.children(`.${MULTIPLE_OPTIONS}`).children('.pt-item')
            let inputVal = ele.children('.input').val().split(',')
            const index = inputVal.indexOf(selectVal)
            const parentBox = $(this).parents(`.${MULTIPLE_BOX}`)
            if (index !== -1) {
                inputVal.splice(index, 1);
            }
            /** 点击删除按钮关闭其他打开选项 */
            $.each($(`.${MULTIPLE_BOX}`), function (idx, item) {
                const $item = $(item)
                if (!$item.is(parentBox)) {
                    $item.removeClass(MULTIPLE_ACTIVE_BOX)
                    $item.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).attr('tooltips', false)
                    $item.children(`.${MULTIPLE_OPTIONS}`).fadeOut(300)
                }
            })
            ele.children('.input').val(inputVal)
            renderSelected(obj)
            $.each(openSelects, function (idx, item) {
                const $item = $(item)
                if ($item.attr('val') === selectVal) {
                    $(item).attr('isActive', false)
                }
            })
        })

        /** 点击页面其他元素关闭 */
        $(document).click(function (e) {
            e.stopPropagation()
            ele.children(`.${MULTIPLE_SELECTED}`).children(`.${MULTIPLE_COUNT}`).attr('tooltips', false)
            ele.removeClass(MULTIPLE_ACTIVE_BOX)
            ele.children(`.${MULTIPLE_OPTIONS}`).fadeOut(300)
        })
    }
    exports(MOD_NAME, render)
});
