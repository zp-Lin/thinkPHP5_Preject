ThinkPHP 5.0.19 ������

���ļ���ԭʼλ��Ϊ��

C:\phpStudy\PHPTutorial\WWW\thinkphp5

���������޸ģ�

1����Ϊ��phpStudy���޸�public\.hdaccess�ļ���
 
ר�����phpStudy��public\.htaccess�ļ�����
<IfModule mod_rewrite.c>
Options +FollowSymlinks -Multiviews
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [L,E=PATH_INFO:$1]
</IfModule>

2�������˵���ģʽ���޸�Application\config.php�ļ�����app_debug����Ϊtrue��

3��Ϊ�˷������ݿ⣬�޸�Application\database.php�ļ���

// ���ݿ���
    
'database'        => 'testdb',
    
// �û���
    
'username'        => 'root',
    
// ����
    
'password'        => '123456',
    


����
2020��7��28��