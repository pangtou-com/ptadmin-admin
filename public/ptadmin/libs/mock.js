layui.define(function (exports) {
    const MOD_NAME = 'MOCK'
    const mock = [
        {
            title: '站点配置',
            id: 1,
            hint: ['站点配置提示', '站点配置提示2'],
            single: true,  // 是否为单标签
            name: 'site',
            directs: [
                {
                    title: '基础配置',
                    params: [
                        { title: '站点标题测试测试测试测试', name: 'title', type: 'content', content: '@PT::site.title' },
                        { title: '站点状态', name: 'title', type: 'content', content: '@PT::site.status' },
                        { title: '站点logo', name: 'title', type: 'content', content: '@PT::site.logo' },
                    ],
                },
                {
                    title: '其它',
                    params: [
                        { title: '测试输入文本', name: 'default', type: 'text', placeholder: '请输入测试输入文本', tip: '一段提示文本' },
                    ],
                },
            ],
        },
        {
            title: '文章列表',
            id: 2,
            hint: '文章列表提示',
            single: false,
            name: 'arclist',
            start_tag: '@PT::arclist',
            end_tag: '@PTEnd::arclist',
            directs: [
                {
                    title: '全局参数',
                    params: [
                        { title: '为空提示（empty）', name: 'empty', type: 'text', placeholder: '默认无提示信息', tip: '一段提示文本' },
                        { title: '每页数量（limit）', name: 'limit', type: 'number', default: '10', placeholder: '一段文章列表，默认10篇', tip: '一段提示文本' },
                        {
                            title: '排序（order）',
                            name: 'order',
                            type: 'select',
                            placeholder: '下拉单选操作',
                            tip: '一段提示文本',
                            options: [
                                { label: '默认', value: 'default1' },
                                { label: '随机', value: 'rand1' },
                                { label: '时间', value: 'time1' },
                                { label: '点击', value: 'click1' },
                                { label: '评论', value: 'comment1' },
                                { label: '点赞', value: 'good1' },
                            ],
                        },
                        {
                            title: '多选（multiple）',
                            name: 'multiple',
                            type: 'multiple',
                            default: ['rand2', 'comment2'],
                            options: [
                                { label: '默认', value: 'default2' },
                                { label: '随机', value: 'rand2' },
                                { label: '时间', value: 'time2' },
                                { label: '点击', value: 'click2' },
                                { label: '评论', value: 'comment2' },
                                { label: '点赞', value: 'good2' },
                            ],
                            placeholder: '默认无提示信息'
                        },
                        {
                            title: '多选测试2（multiple）',
                            name: 'multipleTEXT',
                            type: 'multiple',
                            placeholder: '默认无提示信息',
                            options: [
                                { label: '默认1', value: 'default3' },
                                { label: '随机2', value: 'rand3' },
                                { label: '时间3', value: 'time3' },
                                { label: '点击4', value: 'click3' },
                                { label: '评论5', value: 'comment3' },
                                { label: '点赞6', value: 'good3' },
                            ],
                        },
                    ],
                },
                {
                    title: '特有参数',
                    name: '',
                    params: [
                        { title: '测试名称', name: 'test', type: 'text', placeholder: '测试名称默认值', tip: '测试测试测试测试测试测试测试测试' },
                    ],
                },
            ],
        },

        {
            title: '栏目列表',
            id: 3,
            hint: ['栏目列表提示', '栏目列表提示', '栏目列表提示'],
            single: false,
            name: 'channellist',
            start_tag: '@PT::channellist',
            end_tag: '@PTEnd::channellist',
            directs: [
                {
                    title: '全局参数',
                    params: [
                        { title: '为空提示（empty）', name: 'empty', type: 'text', placeholder: '默认无提示信息' },
                        { title: '每页数量（limit）', name: 'limit', type: 'number', default: '10', placeholder: '一段文章列表，默认10篇' },
                        {
                            title: '排序（order）',
                            name: 'order',
                            type: 'select',
                            placeholder: '下拉单选操作',
                            tip: '一段提示文本',
                            default: 'comment1',
                            options: [
                                { label: '默认', value: 'default1' },
                                { label: '随机', value: 'rand1' },
                                { label: '时间', value: 'time1' },
                                { label: '点击', value: 'click1' },
                                { label: '评论', value: 'comment1' },
                                { label: '点赞', value: 'good1' },
                            ],
                        },
                        {
                            title: '多选（multiple）',
                            name: 'multiple',
                            type: 'multiple',
                            options: [
                                { label: '默认', value: 'default2' },
                                { label: '随机', value: 'rand2' },
                                { label: '时间', value: 'time2' },
                                { label: '点击', value: 'click2' },
                                { label: '评论', value: 'comment2' },
                                { label: '点赞', value: 'good2' },
                            ],
                            placeholder: '默认无提示信息'
                        },
                        {
                            title: '多选测试2（multiple）',
                            name: 'multipleTEXT',
                            type: 'multiple',
                            placeholder: '默认无提示信息',
                            options: [
                                { label: '默认1', value: 'default3' },
                                { label: '随机2', value: 'rand3' },
                                { label: '时间3', value: 'time3' },
                                { label: '点击4', value: 'click3' },
                                { label: '评论5', value: 'comment3' },
                                { label: '点赞6', value: 'good3' },
                            ],
                        },
                    ],
                },
                {
                    title: '特有参数',
                    name: '',
                    params: [
                        { title: '测试名称', name: 'test', type: 'text', placeholder: '测试名称默认值', tip: '测试测试测试测试测试测试测试测试' },
                    ],
                },
            ],
        },

    ]
    exports(MOD_NAME, mock)
});
