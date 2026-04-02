@extends('ptadmin.layouts.base')

@section("content")

<div class="ptadmin-cm-association">
    <div class="layui-tab" lay-filter="test-hash">
        <ul class="layui-tab-title">
            <li class="layui-this" lay-id="11">可关联</li>
            <li lay-id="22">已关联</li>
        </ul>
    </div>
    <div class="ptadmin-page-container">
            <table id="dataTable" lay-filter="dataTable"></table>
            <script type="text/html" id="options">
            -----
            </script>
    </div>
</div>
@endsection

@section("script")
    <script>
        layui.use(['common', 'table', 'PTPage', 'dropdown', 'rate','element'], function () {
            const { table, common , PTPage, dropdown, rate, element} = layui;
            let tag_id = @json($id);
            let pageSize = null;
            let currentPage = null;
            // 定义已选中的复选框数组
            let checked = {};
            let checkedBoxes = {};

            const page = PTPage.make({
                url: "{{admin_route("cms/tag-archive-list")}}",
                btn_left:[{event:'association',theme: 'info', text:'添加关联'}],
                table: {
                    cols: [[
                        {type: 'checkbox', fixed: 'left'},
                        {field: 'id', title: 'ID', width: 80},
                        {field: 'title', title: '文章名称',search: true},
                        {field: 'category.title', title: '所属栏目',search: {type: 'select', options: @json(\Addon\Cms\Models\Category::getCategoryOptions())}},
                        {field: 'mod.title', title: '所属模型',search: {type: 'select', options: @json(\Addon\Cms\Models\Category::getCategoryOptions())}},
                        {field: 'author', title: '作者'},
                        {
                            fixed: 'right',
                            title: '{{ __("system.btn_handle") }}',
                            width: 80,
                            align: 'center',
                            toolbar: "#options"
                        },
                    ]],
                    done: function (res) {
                        pageSize = $(".layui-laypage-limits").find("option:selected").val() //分页数目
                        currentPage = $(".layui-laypage-skip").find("input").val() //当前页码值
                        table.setRowChecked('dataTable', {
                            index: checkedBoxes[currentPage] // 2.9.1+
                        });
                    },
                },
            });

            page.on('association', function () {
                // 提取已选中数据的id checked数组
                let ids_arr = [];
                let checkedArr = objectToArray(checked);
                for (let i = 0; i < checkedArr.length; i++) {
                    for (let j = 0; j < checkedArr[i].length; j++) {
                        ids_arr.push(checkedArr[i][j]);
                    }
                }
                let ids = ids_arr.join(',')
                if(ids === ''){
                    layer.alert('请选择关联的文章');
                    return false;
                }
                let url = "{{admin_route("cms/tag-association")}}?tag_id="+tag_id+"&ids="+ids;
                common.put(url, {}, function (res) {
                    if (res.code === 0) {
                        layer.msg(res.message, {icon: 1});
                        setTimeout(function () {
                            checked = {};
                            checkedBoxes = {};
                            page.getCurrentTable.reload()
                            // parent.location.reload();
                        }, 1000)
                    } else {
                        layer.msg(res.message, {icon: 3});
                    }
                });
            })

            page.on('delAssociation',function (obj){
                let data = obj.data;
                let ids_arr = [];
                if(undefined === data) {
                    let checkedArr = objectToArray(checked);
                    for (let i = 0; i < checkedArr.length; i++) {
                        for (let j = 0; j < checkedArr[i].length; j++) {
                            ids_arr.push(checkedArr[i][j]);
                        }
                    }
                }else {
                    ids_arr.push(data.id);
                }
                let ids = ids_arr.join(',')
                if(ids === ''){
                    layer.alert('请选择要删除的数据');
                    return false;
                }
               let url = "{{admin_route("cms/tag-del-association")}}?tag_id="+tag_id+"&archive_ids="+ids;
                common.put(url, {}, function (res) {
                    if (res.code === 0) {
                        layer.msg(res.message, {icon: 1});
                        setTimeout(function () {
                            checked = {};
                            checkedBoxes = {};
                            page.getCurrentTable.reload()
                            // parent.location.reload();
                        }, 1000)
                    } else {
                        layer.msg(res.message, {icon: 3});
                    }
                });
            });

            page.on('checkbox', function(obj){
                if(obj.checked){
                    if (currentPage in checked){
                        checked[currentPage].push(obj.data.id); // 选中时将选中数据添加到数组中
                        let checkBoxes = checkedBoxes[currentPage] ?? [];
                        checkBoxes.push(obj.index); // 选中时数据的下标添加到选中下标数组中
                    }else {
                        let currentChecked = []; // 定义当前被选中的数据数组
                        let currentCheckedBoxes = []; // 定义当前被选中的下标数组

                        // 将当前选中数据存入数据数组
                        currentChecked.push(obj.data.id);
                        checked[currentPage] = currentChecked;

                        // 将当前选中数据的下标存入选中下标数组
                        currentCheckedBoxes.push(obj.index);
                        checkedBoxes[currentPage] = currentCheckedBoxes;
                    }
                } else {
                    checked[currentPage] = checked[currentPage].filter(function(id) {
                        return id !== obj.data.id; // 取消选中时从数组中移除
                    });
                    checkedBoxes[currentPage] = checked[currentPage].filter(function(index) {
                        return index !== obj.index; // 取消选中时从数组中移除
                    });
                }

                if (obj.type === 'all') {
                    let checkedData = table.checkStatus('dataTable').data;
                    checked[currentPage] = checkedData.map(item => item.id);
                }
            });

            // 切换选项卡后展示操作按钮
            element.on('tab(test-hash)', function(data){
                checked = {};
                checkedBoxes = {};
                // 获取模板元素
                let templateElement = document.getElementById('options');

                let delButton = `<div class="layui-btn-group" >
                    <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="delAssociation"><i class="layui-icon layui-icon-delete"></i></a>
                    </div>`;
                // 更新模板内容
                templateElement.innerHTML = 1 === data.index ? delButton : '-----';

                if (0 === data.index) {
                    let obj = $('a[ptadmin-event="delAssociation"]');
                    obj.attr('ptadmin-event', 'association')
                    obj.html('添加关联')
                    obj.removeClass('layui-btn-danger')
                    page.reload({'checked': 0}, 1)
                } else {
                    let obj = $('a[ptadmin-event="association"]');
                    obj.attr('ptadmin-event', 'delAssociation')
                    obj.html('删除关联')
                    obj.addClass('layui-btn-danger')
                    page.reload({'checked': 1}, 1)
                }
            });

            function objectToArray(arr) {
                let keys = Object.keys(arr);
                // 使用map()函数将每个键的值提取出来
                return keys.map(function (key) {
                    return arr[key];
                });
            }


        })

    </script>
@endsection
