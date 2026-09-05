# 来源与许可

## 本项目自定义代码

plugins/nrc-catalog、themes/nrc-child、infra、本项目脚本、测试和文档采用 GPL-2.0-or-later，见根目录 LICENSE。没有修改官方父主题代码。

## 第三方组件

- WordPress 7.1：<https://wordpress.org/download/>，GPL-2.0-or-later；官方镜像来源 <https://hub.docker.com/_/wordpress>。
- Twenty Twenty-One 2.9：<https://wordpress.org/themes/twentytwentyone/>，WordPress 社区主题；官方目录显示更新于 2026-08-19，最低 WP 5.3 / PHP 5.6。保留包内 GPL 声明及资源许可。
- MySQL 8.4.11：<https://hub.docker.com/_/mysql>，保留上游许可，Compose 记录确切 digest。
- WP-CLI 2.12.0：<https://wp-cli.org/>，MIT；官方 CLI 镜像中的其他依赖保留自己的条款。
- PHPCS / WPCS 等工具：版本锁定见 tests/composer.lock，其中保留包来源和许可证元数据。

核对依据：WordPress 推荐 PHP 8.3+ 和 MySQL 8.0+；实际本机是 PHP 8.3.33 / MySQL 8.4.11。浏览器与集成检查证据独立于官方兼容范围声明。

## 少量复用图片

复制来源为用户的 `C:\dev\lipanpan\public\images\products\` 中 6 个 `nr-demo-*.webp` 文件。只读核对旧项目 README：项目说明其为原创 Demo 产品与主视觉；根 LICENSE 为 MIT，Copyright (c) 2026 koharu4ever。用户任务书明确授权来源检查后复用少量原创演示图片。

6 个原文件保存在 assets，保留原 MIT 全文于 `assets/LICENSE-MIT.txt`。这些图不是实拍证明，不是制造商规格依据；部分不同虚构型号共用演示图。新产品文案在 seed.php 编写。未复制私有文档、旧 .env、真实数据库或私人截图。品牌/产品均明确标注 Portfolio Demo。

根目录 GPL 声明只覆盖本项目新写的文件；不把旧 Laravel MIT 套用到 WordPress、主题、Docker 镜像或其他依赖，也不覆盖上述图片原有条款。
