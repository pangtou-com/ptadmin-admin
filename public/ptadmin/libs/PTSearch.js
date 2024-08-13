layui.define(['table', 'element', 'PTMultipleSelect'], function (exports) {
    const MOD_NAME = "PTSearch"
    const { $, form, table, PTMultipleSelect } = layui;
    const ELEM_DATE = 'ptadmin_date'
    const DATE_TIME = 'ptadmin_date_time'
    const UP_DIV = 'UD'
    //text|select|date|range|date_range|select_multiple
    const PTSearch = {
        event: {
            create: function (arr) {
                let html = '';
                let multiples = [];
                $.each(arr, function (index, value) {
                    if (value.search !== null && value.search !== undefined) {
                        let obj = value.search;
                        let defaultNumber = 2;
                        if (obj.type === 'datetime' || obj.type === 'date_range') {
                            defaultNumber = 3;
                        }

                        html += PTSearch.format.searchFirst(value.search.col, defaultNumber);
                        html += '<div class="layui-input-wrap">';
                        html += '<div class="layui-input-inline layui-input-wrap" >\n';

                        if (value.search.layout === UP_DIV) {
                            html += '<div>' + value.title + '</div>'
                        } else {
                            html += obj.type === 'select_multiple' ? '<label class="layui-form-label">' + value.title + '</label>' : '<div class="layui-input-inline" >' + value.title + '&ensp;</div>';
                        }

                        switch (obj.type) {
                            case 'select':
                                html += PTSearch.format.select(value);
                                break;
                            case 'select_multiple':
                                html += PTSearch.format.select_multiple(value);
                                multiples.push({
                                    label: value.field,
                                    options: value.search.options
                                })
                                break;
                            case 'range':
                                html += PTSearch.format.range(value);
                                break;
                            case 'date':
                                html += PTSearch.format.date(value);
                                break;
                            case 'date_range':
                                html += PTSearch.format.date_range(value);
                                break;
                            case 'datetime':
                                html += PTSearch.format.datetime(value);
                                break;
                            default:
                                html += PTSearch.format.text(value);
                                break;
                        }
                    }
                })
                let btnHtml = ''
                if (html !== '') {
                    btnHtml = '<div >';
                    btnHtml += '<a class="layui-btn search_btn" data-id="1"><i class="layui-icon layui-icon-search"></i></a>';
                    btnHtml += '</div>';
                    html += '<div class="layui-btn-container layui-col-xs12">';
                    html += '<button class="layui-btn" lay-submit lay-filter="table-search">搜索</button>';
                    html += '</div>';
                }
                html = btnHtml + '<form class="layui-form layui-row layui-col-space16 searchForm" style="display: none">' + html + '</form>';
                $('#searchForm').html(html)

                $.each(multiples, function (index, value) {
                    layui.PTMultipleSelect({
                        ele: value.label,
                        disabled: false,
                        name: value.label + '[value]',
                        placeholder: '请选择',
                        options: value.options,
                    })
                })


                form.render();
                form.render('select');
                layui.laydate.render({
                    elem: `.${ELEM_DATE}`
                })

                layui.laydate.render({
                    elem: `.${DATE_TIME}`,
                    type: 'datetime'
                });

                form.on('submit(table-search)', function (data) {
                    var field = data.field; // 获得表单字段
                    // 执行搜索重载
                    table.reload('dataTable', {
                        page: {
                            curr: 1 // 重新从第 1 页开始
                        },
                        where: field // 搜索的字段
                    });
                    return false; // 阻止默认 form 跳转
                });

                $(document).on('click', '.search_btn', function () {
                    let id = $(this).data('id')
                    id = parseInt(id);
                    if (id === 1) {
                        $('.searchForm').show()
                        $(this).data('id', 0)
                    } else {
                        $('.searchForm').hide()
                        $(this).data('id', 1)
                    }
                })

            }
        },
        format: {
            searchFirst: function (col, number) {
                return '<div class="layui-col-md' + ((col !== undefined && col !== null) ? col : number) + '">';
            },
            text: function (obj) {
                let search = '      <div class="layui-input-inline" style="width: 70px;">\n';
                search += '<select name="' + obj.field + '[symbol]" >';
                if (Array.isArray(obj.search.symbol)) {
                    $.each(obj.search.symbol, function (i, v) {
                        search += '<option value="' + v + '">' + v + '</option>';
                    })
                } else {
                    search += '<option value="like">like</option>';
                }
                search += '</select>';
                search += '      </div>\n';
                search += '      <div class="layui-input-inline" >\n' +
                    '        <input type="text" name="' + obj.field + '[value]" value="" placeholder="' + (obj.search.pl !== undefined ? obj.search.pl : '') + '" class="layui-input" lay-affix="clear">\n' +
                    '      </div>\n' +
                    '    </div>\n' +
                    '</div>'
                search += '</div></div>';
                return search;
            },
            select: function (obj) {
                let search = '<div class="layui-input-inline layui-input-wrap">';
                search += '<select name="' + obj.field + '" >';
                search += '<option value="">请选择</option>';
                if (Array.isArray(obj.search.options)) {
                    $.each(obj.search.options, function (index, option) {
                        search += '<option value="' + option.value + '">' + option.label + '</option>';
                    })
                }
                search += '</select></div></div>';
                return search;
            },
            select_multiple: function (obj) {
                let search = '<div class="layui-input-block">' +
                    '<div class="multiple-box ' + obj.field + '"></div>\n' +
                    '</div>\n' +
                    '</div>';
                search += '</div></div>';
                return search;


            },
            range: function (obj) {
                let search = '<div class="layui-input-inline layui-input-wrap" >\n' +
                    '      <div class="layui-input-inline" style="width: 100px;">\n' +
                    '        <input type="number" name="' + obj.field + '[value][0]" placeholder="" autocomplete="off" class="layui-input" min="0" step="1" lay-affix="number">\n' +
                    '        <input type="hidden" name="' + obj.field + '[symbol]" value="between"/>\n' +
                    '      </div>\n' +
                    '      <div class="layui-input-inline">-</div>\n' +
                    '      <div class="layui-input-inline" style="width: 100px;">\n' +
                    '        <input type="number" name="' + obj.field + '[value][1]" placeholder="" autocomplete="off" class="layui-input" min="0" step="1" lay-affix="number">\n' +
                    '      </div>\n' +
                    '    </div>\n' +
                    '</div>'
                search += '</div></div>';


                return search;
            },
            date: function (obj) {
                let search = '<div class="layui-input-inline layui-input-wrap">\n' +
                    '        <div class="layui-input-prefix">\n' +
                    '          <i class="layui-icon layui-icon-date"></i>\n' +
                    '        </div>\n' +
                    '        <input type="text" name="' + obj.field + '[value]" lay-verify="date" placeholder="yyyy-MM-dd" autocomplete="off" class="layui-input ' + ELEM_DATE + '" style="width: 200px">\n' +
                    '        <input type="hidden" name="' + obj.field + '[symbol]" value="between"/>\n' +
                    '      </div>'
                search += '</div></div>';
                return search;
            },
            date_range: function (obj) {
                let search = '<div class="layui-input-inline layui-input-wrap">\n' +
                    '      <div class="layui-input-inline" >\n' +
                    '         <div class="layui-input-prefix">\n' +
                    '             <i class="layui-icon layui-icon-date"></i>\n' +
                    '         </div>\n' +
                    '        <input type="text" name="' + obj.field + '[value][0]" lay-verify="date" placeholder="yyyy-MM-dd" autocomplete="off" class="layui-input ' + ELEM_DATE + '" style="width: 200px">\n' +
                    '        <input type="hidden" name="' + obj.field + '[symbol]" value="between_date"/>\n' +
                    '      </div>\n' +
                    '      <div class="layui-input-inline">-</div>\n' +
                    '      <div class="layui-input-inline" ">\n' +
                    '         <div class="layui-input-prefix">\n' +
                    '             <i class="layui-icon layui-icon-date"></i>\n' +
                    '         </div>\n' +
                    '        <input type="text" name="' + obj.field + '[value][1]" lay-verify="date" placeholder="yyyy-MM-dd" autocomplete="off" class="layui-input ' + ELEM_DATE + '" style="width: 200px">\n' +
                    '      </div>\n' +
                    '    </div>\n' +
                    '</div>'
                search += '</div></div>';
                return search;
            },
            datetime: function (obj) {
                let search = '<div class="layui-input-inline layui-input-wrap">\n' +
                    '      <div class="layui-input-inline" >\n' +
                    '         <div class="layui-input-prefix">\n' +
                    '             <i class="layui-icon layui-icon-date"></i>\n' +
                    '         </div>\n' +
                    '        <input type="text" name="' + obj.field + '[value][0]" lay-verify="datetime"  placeholder="yyyy-MM-dd HH:mm:ss" autocomplete="off" class="layui-input ' + DATE_TIME + '" style="width: 200px">\n' +
                    '        <input type="hidden" name="' + obj.field + '[symbol]" value="between_date"/>\n' +
                    '      </div>\n' +
                    '      <div class="layui-input-inline">-</div>\n' +
                    '      <div class="layui-input-inline" ">\n' +
                    '         <div class="layui-input-prefix">\n' +
                    '             <i class="layui-icon layui-icon-date"></i>\n' +
                    '         </div>\n' +
                    '        <input type="text" name="' + obj.field + '[value][1]" lay-verify="datetime" placeholder="yyyy-MM-dd HH:mm:ss" autocomplete="off" class="layui-input ' + DATE_TIME + '" style="width: 200px">\n' +
                    '      </div>\n' +
                    '    </div>\n' +
                    '</div>'
                search += '</div></div>';
                return search;
            }
        }
    }
    exports(MOD_NAME, PTSearch)
})

