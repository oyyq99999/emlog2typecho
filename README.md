Emlog2Typecho
=============

简介
----

这是一个帮助从emlog到typecho的数据库迁移脚本。

使用方法
--------

   1. 需要依赖[PHP-MySQLi-Database-Class](https://github.com/joshcam/PHP-MySQLi-Database-Class)
   2. 需要php包含`mysqli`扩展
   2. 在[emlog2typecho.php开头处](https://github.com/oyyq99999/emlog2typecho/blob/master/emlog2typecho.php#L2-L13)设置好_require路径_以及_数据库相关配置_
   3. 运行emlog2typecho.php

迁移内容列表
------------
  - [x] 博客和页面
    - [x] 博客
    - [x] 页面
    - [x] 草稿
    - [x] 审核
    - [x] 查看密码
  - [x] 分类和标签
    - [x] 分类
    - [x] 标签
    - [x] 分类和博客/页面对应关系
    - [x] 标签和博客/页面对应关系
  - [x] 评论
    - [x] 评论内容
    - [x] 评论人属性
    - [ ] 评论用户属性
    - [x] 评论审核状态
  - [x] 配置项
    - [x] 站点名称
    - [x] 站点描述
    - [x] 关键字
    - [x] 站点url
    - [x] 一页文章数
    - [x] 首页侧边评论数
    - [x] 首页侧边文章数
    - [x] 评论分页每页条数
    - [x] 评论发表时间间隔
    - [x] 允许的附件类型
    - [x] RSS输出全文/摘要
    - [ ] RSS输出文章数(typecho无此功能?)
    - [x] 是否使用评论头像
    - [x] 评论是否分页
    - [x] 评论是否需要审核
    - [x] 是否启用gzip
    - [x] 评论显示顺序
    - [x] 时区
    - [ ] Rewrite相关(过于复杂)
  - [ ] 附件(迁移不便)
  - [ ] 链接(typecho貌似没有此功能?)
  - [ ] 导航(迁移不便)
  - [ ] 用户(**因密码问题无法迁移**)
  - [ ] 碎语相关内容(*typecho无此功能*)

遗留问题
--------
 - 博客里面的附件、图片等会失效

测试版本
--------

emlog `5.3.0` => typecho `0.9 (13.12.12)`  
PHP `5.3.10`  
MySQL `5.5.23`
