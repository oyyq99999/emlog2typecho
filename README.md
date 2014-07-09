Emlog2Typecho
=============

简介
----

这是一个帮助从emlog到typecho的数据库迁移脚本。

使用方法
--------

   1. 需要依赖[PHP-MySQLi-Database-Class](https://github.com/joshcam/PHP-MySQLi-Database-Class)
   2. 设置好数据库相关配置
   3. 运行emlog2typecho.php

迁移内容列表
------------
  - [x] 博客和页面
  - [x] 分类和标签
  - [x] 评论
  - [x] 相同的配置项
  - [ ] 附件(迁移不便)
  - [ ] 链接(typecho貌似没有此功能?)
  - [ ] 导航(迁移不便)
  - [ ] 用户(因密码问题无法迁移)
  - [ ] 碎语相关内容(typecho无此功能)

测试版本
--------

emlog 5.3.0 => typecho 0.9 (13.12.12)
