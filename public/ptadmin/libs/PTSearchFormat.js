/**
 * 搜索功能表单
 */
layui.define(function (exports) {
    const MOD_NAME = "PTSearchFormat"
    const PTSearch = {}

    /**
     * 普通文本
     * @param obj
     * @returns {string}
     */
    PTSearch.text =  function (obj) {
        const placeholder = obj.search.placeholder || '请输入' + obj.title
        return `<input type="text" name="${obj.field}[value]" placeholder="${placeholder}" autocomplete="off" class="layui-input" />`
    }

    /**
     * 下拉选择
     * @param obj
     * @returns {string}
     */
    PTSearch.select = function (obj) {
        const options = obj.search.options || []

        const html = []
        for (const option of options) {
            html.push(`<option value="${option['value']}">${option['label']}</option>`)
        }
        return `<select name="${obj.field}[value]">
                    <option value="">${obj.placeholder || '请选择' + obj.title}</option>
                    ${html.join("")}
                </select>`;
    }

    /**
     * 符号位
     * @param obj
     * @returns {string}
     */
    PTSearch.op = function (obj) {
        const options = obj.search.op
        const html = []
        for (const key in options) {
            html.push(`<option value="${options[key]}">${options[key]}</option>`)
        }

        return `<div class="ptadmin-prefix">
                    <select name="${obj.field}[op]">${html.join("")}</select>
                </div>`;
    }

    /**
     * 下拉多选
     * @param obj
     * @returns {string}
     */
    PTSearch.select_multiple = function(obj) {
        return ""
    }

    /**
     * 区间搜索
     * @param obj
     * @returns {string}
     */
    PTSearch.number_range = function(obj) {
        const placeholder = obj.search.placeholder || '请输入' + obj.title
        const min = `<input type="number" name="${obj.field}[min]" placeholder="${placeholder}" class="layui-input" lay-affix="number">`
        const max = `<input type="number" name="${obj.field}[max]" placeholder="${placeholder}" class="layui-input" lay-affix="number">`
        return `<div class="ptadmin-interval">${min} <span>-</span> ${max}</div>`
    }

    /**
     * 数字搜索
     * @param obj
     * @returns {string}
     */
    PTSearch.number = function(obj) {
        const placeholder = obj.search.placeholder || '请输入' + obj.title
        return `<input type="number" name="${obj.field}[value]" placeholder="${placeholder}" class="layui-input" lay-affix="number">
`
    }

    /**
     * 日期搜索
     * @param obj
     * @returns {string}
     */
    PTSearch.date = function(obj) {
        const placeholder = obj.search.placeholder || '请选择' + obj.title
        return `<input ptadmin-type="date" type="text" class="layui-input" name="${obj.field}[value]" placeholder="${placeholder}">`
    }

    /**
     * 日期区间
     * @param obj
     * @returns {string}
     */
    PTSearch.date_range = function(obj) {
        const placeholder = obj.search.placeholder || '请选择' + obj.title
        const min = `<input ptadmin-type="date_range" type="text" name="${obj.field}[min]" placeholder="${placeholder}" class="layui-input">`
        const max = `<input ptadmin-type="date_range" type="text" name="${obj.field}[max]" placeholder="${placeholder}" class="layui-input">`
        return `<div class="ptadmin-interval">${min} <span>-</span> ${max}</div>`
    }

    /**
     * 时间日期
     * @param obj
     * @returns {string}
     */
    PTSearch.datetime = function(obj) {
        const placeholder = obj.search.placeholder || '请选择' + obj.title
        return `<input ptadmin-type="datetime" type="text" class="layui-input" name="${obj.field}[value]" placeholder="${placeholder}">`
    }


    exports(MOD_NAME, PTSearch)
})

