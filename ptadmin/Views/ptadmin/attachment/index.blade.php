@extends('ptadmin.layouts.base')

@section("content")
<div class="ptadmin-attachment-box ptadmin-default-attachment-box">
	<header class="ptadmin-attachment-header">
		<div class="title">文件管理器</div>
		<div class="layui-btn-group">
			<button type="button" class="layui-btn layui-btn-sm" ptadmin-event="addFile">新建文件</button>
		</div>
	</header>
	<div class="ptadmin-attachment-container">
		<aside class="ptadmin-attachment-aside">
			<ul class="layui-menu ptadmin-attachment-nav layui-menu-lg">
				<li>
					<div class="layui-menu-body-title">全部文件</div>
				</li>
				<li class="layui-menu-item-group layui-menu-item-up">
					<div class="layui-menu-body-title">
						图片文件
						<i class="layui-icon layui-icon-up"></i>
						<div class="layui-btn-group">
							<button type="button" class="layui-btn layui-btn-xs" ptadmin-event="addFile">
								<i class="layui-icon layui-icon-add-1"></i>
							</button>
							<button
								type="button"
								class="layui-btn layui-btn-xs layui-bg-red"
								ptadmin-event="deleteFile">
								<i class="layui-icon layui-icon-delete"></i>
							</button>
						</div>
					</div>
					<ul class="ptadmin-children">
						<li>
							<div class="layui-menu-body-title">
								图片文件一
								<div class="layui-btn-group">
									<button
										type="button"
										class="layui-btn layui-btn-xs layui-bg-red"
										ptadmin-event="deleteFile">
										<i class="layui-icon layui-icon-delete"></i>
									</button>
								</div>
							</div>
						</li>
						<li>
							<div class="layui-menu-body-title">
								图片文件二
								<div class="layui-btn-group">
									<button
										type="button"
										class="layui-btn layui-btn-xs layui-bg-red"
										ptadmin-event="deleteFile">
										<i class="layui-icon layui-icon-delete"></i>
									</button>
								</div>
							</div>
						</li>
					</ul>
				</li>
			</ul>
		</aside>
		<main class="ptadmin-attachment-main">
			<div class="attachment-top">
				<div class="attachment-top-l layui-form">
					<div class="layui-btn-group">
						<!-- <button type="button" class="layui-btn layui-btn-sm">
							<i class="layui-icon layui-icon-ok"></i> 确认
						</button> -->
						<button type="button" class="layui-btn layui-btn-sm layui-bg-blue">
							<i class="layui-icon layui-icon-upload"></i> 上传
						</button>
						<button class="layui-btn layui-bg-red layui-btn-sm">
							<i class="layui-icon layui-icon-delete"></i>删除
						</button>
					</div>
					<input type="checkbox" name="selectAll" value="1" lay-skin="tag" />
					<div lay-checkbox>
						<i
							class="layui-icon layui-icon-success"
							style="position: relative; top: 1px; line-height: normal"></i>
						全选
					</div>
				</div>
				<form class="layui-form attachment-top-r layui-form" action="">
					<div class="select search-item">
						<select>
							<option value="">请选择文件类型</option>
							<option value="JPG">JPG</option>
							<option value="PNG">PNG</option>
							<option value="MP4">MP4</option>
						</select>
					</div>
					<div class="layui-input-group search-item">
						<input type="text" placeholder="请输入关键字进行搜索" class="layui-input" />
						<div class="layui-input-split layui-input-suffix" style="cursor: pointer">
							<i class="layui-icon layui-icon-search"></i>
						</div>
					</div>
				</form>
				<!-- 平板菜单 -->
				<div class="attachment-top-r-sm">
					<button type="button" class="layui-btn layui-btn-sm layui-bg-purple" lay-on="attachment-menu-sm">
						<i class="layui-icon layui-icon-shrink-right"></i> 菜单
					</button>
				</div>
			</div>
			<div class="content">
				<div class="empty-box">
					<div class="layui-icon layui-icon-face-cry"></div>
					<div class="text">暂无更多数据</div>
				</div>

				<ul class="lists">
					<li class="item active">
						<div class="image">
							<img src="http://www.pangtouweb.com/storage/default/20240906/4B7sKpu62D7nMSVx5Inl8v29XnRx2PrhrXPZiJMJ.jpg" alt="" />
							<div class="mask-operate">
								<div class="layui-btn-group">
									<button type="button" class="layui-btn layui-btn-xs">
										<i class="layui-icon layui-icon-eye"></i>
									</button>
									<button type="button" class="layui-btn layui-btn-xs layui-bg-blue">
										<i class="layui-icon layui-icon-edit"></i>
									</button>
									<button type="button" class="layui-btn layui-btn-xs layui-bg-red">
										<i class="layui-icon layui-icon-delete"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="section">
							<span class="count"><i class="iconfont icon-tap"></i>99</span>
							<span class="layui-badge layui-bg-blue">JPG</span>
						</div>
						<div class="title">测试测试测试测试</div>
						<i class="layui-icon layui-icon-ok"></i>
					</li>
					<li class="item">
						<div class="image">
							<img src="http://www.pangtouweb.com/storage/default/20240906/4B7sKpu62D7nMSVx5Inl8v29XnRx2PrhrXPZiJMJ.jpg" alt="" />
							<div class="mask-operate">
								<div class="layui-btn-group">
									<button type="button" class="layui-btn layui-btn-xs">
										<i class="layui-icon layui-icon-eye"></i>
									</button>
									<button type="button" class="layui-btn layui-btn-xs layui-bg-blue">
										<i class="layui-icon layui-icon-edit"></i>
									</button>
									<button type="button" class="layui-btn layui-btn-xs layui-bg-red">
										<i class="layui-icon layui-icon-delete"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="section">
							<span class="count"><i class="iconfont icon-tap"></i>99</span>
							<span class="layui-badge layui-bg-blue">JPG</span>
						</div>
						<div class="title">测试测试测试测试</div>
						<i class="layui-icon layui-icon-ok"></i>
					</li>
				</ul>
			</div>
			<div class="attachment-footer">
				<div id="demo-laypage-normal-2"></div>
			</div>
		</main>
	</div>
</div>
@endsection

@section("script")
<script>
    layui.use(['PTPage', 'common','laypage','layer','util','form'], function() {
        const { $ , laypage, layer , util,form } = layui

        const container = 'ptadmin-attachment-box'
		//  新增文件
		$(`.${container}`).on('click', '[ptadmin-event="addFile"]', function (e) {
			e.stopPropagation()
			layer.prompt({ title: '请输入文件夹名称' }, function (value, index, elem) {
				if (value === '') {
					layer.msg('请输入文件夹名称')
					elem.focus()
					return
				}
				layer.close(index)
			})
		})
        util.on('lay-on', {
                'attachment-menu-sm': function(){
                    layer.open({
                        type: 1,
                        offset: 'r',
                        anim: 'slideLeft', // 从右往左
                        shade: 0.1,
                        title:false,
                        closeBtn:0,
                        shadeClose: true,
                        content: $('.ptadmin-attachment-aside')
                    });
                }
        })
		laypage.render({
				elem: 'demo-laypage-normal-2',
				count: 100, // 数据总数
		})
    })
</script>
@endsection
