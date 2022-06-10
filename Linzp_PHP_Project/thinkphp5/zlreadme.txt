ThinkPHP 5.0.19 完整版

该文件夹原始位置为：

C:\phpStudy\PHPTutorial\WWW\thinkphp5

我做如下修改：

1）因为是phpStudy，修改public\.hdaccess文件。
 
专门针对phpStudy的public\.htaccess文件代码
<IfModule mod_rewrite.c>
Options +FollowSymlinks -Multiviews
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [L,E=PATH_INFO:$1]
</IfModule>

2）开启了调试模式，修改Application\config.php文件，将app_debug设置为true。

3）为了访问数据库，修改Application\database.php文件。

// 数据库名
    
'database'        => 'testdb',
    
// 用户名
    
'username'        => 'root',
    
// 密码
    
'password'        => '123456',
    


张立
2020年7月28日