# haxibiao/dimension

> haxibiao/dimension 是哈希表维度统计系统

## 导语

注意，元数据在本模块实现，主要提供每天 archive 的报表数据，方便运营分析宏观数据

## 环境要求

1. nova, 主要依赖 nova 便捷的 metric 来展示报表

## 安装步骤

1. `composer.json`改动如下：
   在`repositories`中添加 vcs 类型远程仓库指向
   `http://code.haxibiao.cn/packages/haxibiao-dimension`
1. 执行`composer require haxibiao/dimension`
1. 如果不是 laravel 5.6 以上，需要执行`php artisan dimension:install`
1. 完成

### 如何完成更新？

> 远程仓库的 composer package 发生更新时如何进行更新操作呢？

1. 执行`composer update haxibiao/dimension`

## GQL 接口说明

## Api 接口说明
