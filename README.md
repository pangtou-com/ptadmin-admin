# 1、目录权限：
```shell
# storage 存储、日志目录权限
chown -R www:www storage
chmod -R 755 storage

# bootstrap/cache 运行时缓存目录权限
chown -R www:www bootstrap/cache  # 改变目录用户组 www:www 为nginx运行用户  
chmod -R 755 bootstrap/cache # 设置目录权限

# addons 插件目录权限
chown -R www:www addons 
chmod -R 755 addons

# resources/views 模版目录权限 
chown -R www:www resources/views
chmod -R 755 resources/views
```
