@extends('ptadmin.layouts.base')

@section('content')
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title">
            <li class="layui-this">资源列表</li>
            <li>在线上传</li>
        </ul>
        <div class="layui-tab-content">
            <div class="layui-tab-item layui-show">
                <table id="dataTable" lay-filter="dataTable"></table>
            </div>
            <div class="layui-tab-item">
                <div class="layui-upload-drag" style="display: block;" id="ptadmin-upload-drag">
                    <i class="layui-icon layui-icon-upload"></i>
                    <div>点击上传，或将文件拖拽到此处</div>
                </div>
                <div class="layui-upload-list">
                    <table class="layui-table">
                        <colgroup>
                            <col style="min-width: 100px;">
                            <col width="150">
                            <col width="260">
                            <col width="150">
                        </colgroup>
                        <thead>
                        <th>文件名</th>
                        <th>大小</th>
                        <th>上传进度</th>
                        <th>操作</th>
                        </thead>
                        <tbody id="ptadmin-upload-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- 限制内容条数 --}}
    <input type="hidden" id="limit" name="limit" value="{{(int)$data['limit']}}">
    {{-- 已选择的内容 --}}
    <input type="hidden" id="currentLen" name="currentLen" value="{{(int)$data['currentLen']}}">

    <script id="titleHtml" type="text/html">
        <a href="@{{ d.url }}" target="_blank" style="color: #4397fd;">
            @{{d.title}}
        </a>
    </script>

    <script id="previewHtml" type="text/html">
        <img src="@{{ d.preview }}" style="max-width: 60px;max-height: 60px;">
    </script>
    <style>
        .layui-table-cell {
            height: 70px;
            line-height: 70px;
        }
    </style>
@endsection


@section('script')
    <script>
        // 存储选中数据
        const data = [];
        layui.use(['PTPage', 'upload', 'element'], function () {
            let { PTPage, upload, element } = layui;

            /**
             * 选中事件处理
             * @param res
             * @param thiz
             */
            const handleSelected = function (res, thiz) {
                // 判断操作属性选中或取消选中状态
                if (thiz.hasClass('layui-btn-danger')) {
                    deleteData(res.data)
                    thiz.removeClass('layui-btn-danger')
                    thiz.html("选择")
                } else {
                    insertData(res.data)
                    thiz.addClass('layui-btn-danger')
                    thiz.html("取消选择")
                }
                updateStatus()
            }

            /**
             * 更新选择状态
             */
            const updateStatus = function () {
                const parent = window.parent[0].parent
                const obj = parent.$('.layui-layer-title')
                const preview = obj.find('.preview')
                const limit = $("#limit").val()
                const len = $("#currentLen").val()
                if (data.length > (limit - len)) {
                    layer.msg(`最多选择【${limit}】个`)
                    return
                }
                if (preview.length > 0) {
                    preview.html(`已选择【${data.length}】`)
                } else {
                    obj.attr("style", 'display:flex;align-items: center;')
                    obj.append(`<div class="preview" style="color: red;margin-left: 10px">已选择【${data.length}】</div>`)
                }
            }

            /**
             * 插入数据
             * @param result
             */
            const insertData = function (result) {
                for (let i = 0; i < data.length; i++) {
                    if (data[i].id === result.id) {
                        return
                    }
                }
                data.push(result)
            }

            /**
             * 删除数据
             * @param result
             */
            const deleteData = function (result) {
                for (let i = 0; i < data.length; i++) {
                    if (data[i].id === result.id) {
                        data.splice(i, 1)
                        break
                    }
                }
            }

            const elem = $("#ptadmin-upload-list");
            const uploadObj = upload.render({
                elem: '#ptadmin-upload-drag',
                multiple: true,
                url: '{{admin_route('upload')}}',
                choose: function (obj) {
                    const files = this.files = obj.pushFile(); // 将每次选择的文件追加到文件队列
                    // 读取本地文件
                    obj.preview(function (index, file, result) {
                        const tr = $([`<tr id="upload-${index}">`,
                            `<td>${file.name}</td>`,
                            '<td>' + (file.size / 1024).toFixed(1) + 'kb</td>',
                            `<td><div class="layui-progress" lay-filter="progress-${index}"><div class="layui-progress-bar" lay-percent=""></div></div></td>`,
                            `<td>`,
                            `<button class="layui-btn layui-btn-xs reload layui-hide">重传</button>`,
                            `<button class="layui-btn layui-btn-xs layui-btn-danger delete">删除</button>`,
                            `</td>`,
                            `</tr>`].join(''));

                        // 单个重传
                        tr.find('.reload').on('click', function () {
                            obj.upload(index, file);
                        });

                        // 删除
                        tr.find('.delete').on('click', function () {
                            delete files[index];
                            tr.remove();
                            uploadObj.config.elem.next()[0].value = ''; // 清空 input file 值，以免删除后出现同名文件不可选
                        });
                        elem.append(tr);
                        element.render('progress'); // 渲染新加的进度条组件
                    });
                },
                done: function (res, index, upload) {
                    if (res.code === 0) {
                        const tr = elem.find('tr#upload-' + index)
                        const tds = tr.children();
                        const handle = $(`<button class="layui-btn layui-btn-xs select">选择</button>`)
                        tds.eq(3).html(''); // 清空操作
                        tds.eq(3).append(handle);
                        handle.on('click', () => handleSelected(res, handle));
                        delete this.files[index]; // 删除文件队列已经上传成功的文件
                        return;
                    }
                    this.error(index, upload);
                },
                error: function (index) {
                    const tr = elem.find('tr#upload-' + index);
                    const tds = tr.children();
                    tds.eq(3).find('.reload').removeClass('layui-hide');
                },
                progress: function (n, elem, e, index) {
                    element.progress('progress-' + index, n + '%');
                }
            });

           const page = PTPage.make({
                urls: {
                    index_url: '{{admin_route('attachments')}}',
                },
                table: [[
                    {type: 'checkbox', width: 50},
                    {field: 'id', title: 'ID', width: 50},
                    {field: 'title', title: '{{ __("table.attachments.title") }}', templet: "#titleHtml"},
                    {field: 'preview', title: '{{ __("table.attachments.preview") }}', templet: "#previewHtml"},
                    {field: 'mime', title: '{!! __("table.attachments.mime") !!}'},
                    {field: 'size', title: '{!! __("table.attachments.size") !!}'},
                    {field: 'driver', title: '{!! __("table.attachments.driver") !!}'},
                ]]
            });

            page.on('checkbox', function (obj) {
                if (obj.type === 'all') {
                    const res = page.getCurrentTable?.getData() || []
                    res.map((item) => {
                        obj.checked ? insertData(item) : deleteData(item)
                    })
                } else {
                    obj.checked ? insertData(obj.data) : deleteData(obj.data)
                }
                updateStatus()
            });
        })

        window.getActiveData = () => data

    </script>
@endsection
