#!/usr/bin/env bash
set -euo pipefail
cd /work
php -v | head -n 1
composer --version
wp --version
if ! wp core is-installed; then
    echo 'WordPress is not initialized. Run scripts/setup.ps1 from host PowerShell, then reopen this container.' >&2
    exit 1
fi
echo '开发容器就绪。终端位于 /work，可直接运行 php、composer、wp 和 git。'
echo '前台：http://127.0.0.1:8081/   后台：http://127.0.0.1:8081/wp-admin/'
echo '修改代码：plugins/nrc-catalog 与 themes/nrc-child。此终端连接的是开发数据库。'
echo '集成测试请按 README 在独立测试实例运行，不要在这里执行 tests/integration.php。'
