/**
 * 搜索功能表单
 */
layui.define(['PTMultipleSelect'], function (exports) {
    const MOD_NAME = "PTSearchFormat"
    const { $, PTMultipleSelect } = layui;
    //text|select|date|range|date_range|select_multiple
    const PTSearch = {}

    /**
     * 普通文本
     * @param obj
     * @returns {string}
     */
    PTSearch.text =  function (obj) {
        return `<input type="text" name="${obj.field}[value]" placeholder="${obj.placeholder || '请输入' + obj.title}" autocomplete="off" class="layui-input" />`
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
    PTSearch.select_multiple = function(obj){
        return ""
    }

    /**
     * 区间搜索
     * @param obj
     * @returns {string}
     */
    PTSearch.range = function(obj){

        return ""
    }

    /**
     * 日期搜索
     * @param obj
     * @returns {string}
     */
    PTSearch.date = function(obj){
        return ""
    }

    /**
     * 日期区间
     * @param obj
     * @returns {string}
     */
    PTSearch.date_range = function(obj){
        return ""
    }

    /**
     * 时间日期
     * @param obj
     * @returns {string}
     */
    PTSearch.datetime = function(obj){
        return ""
    }


    exports(MOD_NAME, PTSearch)
})

