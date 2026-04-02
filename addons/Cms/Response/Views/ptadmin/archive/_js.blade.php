<script>

    (function ($win) {
        layui.use(['tree', 'dropdown', 'form', 'layer', 'PTPage', 'table'], function () {
            const { tree, dropdown, layer, form, PTPage, table } = layui
            const TREE_ID = 'zTree'
            const DEFAULT_TITLE = "内容管理"
            let clickedCategoryId = null;
            let page = undefined
            // 默认列
            const default_col = [
                {checkbox: true, fixed: true},
                {field: 'id', title: 'ID', width: 60},
                {field: 'title', title: '文章标题', minWidth: 200, search: true, templet: "#expand"},
                {field: 'category.title', title: '所属栏目', width: 120},
                // {field: 'mod.title', title: '所属模型', width: 120},
                {field: 'price', title: '服务价格', width: 90},
                {field: 'views', title: '访问量', width: 80},
                {field: 'attribute_text', title: '推荐属性', width:  100, search: {type: 'select', options: @json(\Addon\Cms\Enum\AttributeEnum::getMapToOptions())}},
                // {field: 'attribute_text', title: '推荐属性', width:  100},
                {field: 'spider', title: '收录状态', width: 90},
                {field: 'status_text', title: '状态',align: 'center', templet: PTPage.format.tags({
                        key: "status",
                        map: {0: 'default', 1: 'warning', 2: 'success', 7: 'info', 8: 'primary', 9: 'danger'}
                    }), width:  80},
                {
                    fixed: 'right', width: 100, title: '{{ __("system.btn_handle") }}', align: 'center',
                    operate: ['edit', 'del']
                },
            ]

            const events = {
                'close-navigation': function() {
                    $('.ptadmin-cms-aside').hide()
                    $('.open-navigation').show()
                    table.resize(page.getCurrentTable.config.id)
                },
                'open-navigation': function() {
                    $('.ptadmin-cms-aside').show()
                    $(this).hide()
                    page.getCurrentTable.reload()
                },
                batch: () => {
                    console.log('批量操作')
                }
            }

            events.refresh = function (ids) {
                if (ids === undefined || ids.length === 0) {
                    page.getCurrentTable.reload({
                        where: {}
                    })
                    return
                }
                page.getCurrentTable.reload({
                    where: { category_id: ids },
                })
            }

            events.create = function () {
                if(null === clickedCategoryId || 0 === clickedCategoryId) {
                    layer.msg('请先选择栏目')
                    return
                }
                /*const { ids } = getCheckedNodes()*/
                const index = layer.open({
                    type: 2,
                    title: '添加内容',
                    content: '{{admin_route("cms/archive")}}?category_id=' + clickedCategoryId,
                    skin: "layui-layer-lan",
                })
                layer.full(index);
            }

            const getCheckedNodes = () => {
                const ids = []
                const data = tree.getChecked(TREE_ID)

                let title = ""
                const readData = (data) => {
                    data.forEach(item => {
                        if (title === "") {
                            title = item.title
                        }
                        ids.push(item.id)
                        item.children && readData(item.children)
                    })
                }
                readData(data)

                return {ids, title}
            }

            const reloadTreeData = (data, spread, checkedIds) => {
                for (const key in data) {
                    data[key].spread = spread
                    data[key].checked = (typeof(checkedIds) === "boolean") ? checkedIds : checkedIds.includes(data[key].id)
                    if (data[key].children && data[key].children.length > 0) {
                        data[key].children = reloadTreeData(data[key].children, spread, checkedIds)
                    }
                }

                return data
            }

            const loadPage = () => {
                page = PTPage.make({
                    btn_left: null,
                    btn_right: null,
                    urls: {
                        index_url: "{{admin_route('cms/archive-pages')}}",
                        edit_url: "{{admin_route('cms/archive')}}/{id}",
                        del_url: "{{admin_route('cms/archive')}}/{id}",
                    },
                    table: {
                        cols: [default_col],
                        css: [
                            '.layui-table-page{display: flex;justify-content: space-between;}.layui-table-pageview{order:2;}' // 让分页栏居中
                        ].join(''),
                        pagebar:'#batch-operation',
                        done:function(){
                            let tips = ''
                            $(".ptadmin-page-expand-image-box > .image").on({
                                mouseenter: function () {
                                    const imgUrl = $(this).data('url')
                                    const img = `<div style="width:100px;text-align: center;" class="image-box"><img style="max-width:100%;max-height:100%" src="${imgUrl}"></div>`
                                    tips = layer.tips(img, this,{area:'200', tips:[4,'#fff'], time:0});
                                },
                                mouseleave: function () {
                                    layer.close(tips);
                                }
                            });

                            table.on('pagebar(dataTable)', function(obj){
                                const params = {
                                    obj,
                                    elem:this
                                }
                                events[obj.event].call(undefined, params)
                            });
                        }
                    }
                })

                page.on("edit", function(params){
                    const { id } = params.data
                    const index = layer.open({
                        type: 2,
                        title: '编辑内容',
                        content: '{{admin_route("cms/archive")}}/' + id,
                        skin: "layui-layer-lan",
                    })
                    layer.full(index);
                })
            }

            const init = () => {
                tree.render({
                    id: `${TREE_ID}`,
                    elem: `#${TREE_ID}`,
                    data: dataTree,
                    showCheckbox: true,
                    onlyIconControl: true,
                    click: function (obj) {
                        obj.elem.find('.layui-form-checkbox').click();
                    },
                    oncheck: function(obj) {
                        const { ids, title } = getCheckedNodes()
                        clickedCategoryId = true === obj.checked ? obj.data.id : obj.data.id === clickedCategoryId ? null : clickedCategoryId
                        events.refresh(ids)
                        $('.main-header > .title').text(title === "" ? DEFAULT_TITLE : title)
                    },
                });

                loadPage()
            }

            form.on('checkbox(aside-checkbox)', function(data){
                const { checked, name } = data.elem;
                if (name === "expand") {
                    const { ids } = getCheckedNodes()
                    const data = reloadTreeData(dataTree, checked, ids)
                    tree.reload(TREE_ID, {data: data})
                    return
                }
                if (name === 'all') {
                    const data = reloadTreeData(dataTree, $("input[name=expand]").prop("checked"), checked)
                    tree.reload(TREE_ID, {data: data})
                    $('.main-header > .title').text(DEFAULT_TITLE)
                }
            })

            $('.ptadmin-cms-box').on('click', '*[ptadmin-event]', function () {
                const event = $(this).attr('ptadmin-event')
                events[event] && events[event].call(this)
            })


            init()

            $win['cms'] = {
                refresh: () => {
                    page.getCurrentTable.reload()
                }
            }
        })
    })(window)


</script>
