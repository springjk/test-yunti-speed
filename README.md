# 云梯测速 PHP 脚本
## 功能
为云梯 VPN 进行 `ping` 测速选择最优路线并自动连接

## 全局安装
``` bash
$ curl -sS https://raw.githubusercontent.com/springjk/test-yunti-speed/master/installer | php
$ yunti
```

## 局部安装
``` bash
$ git clone https://github.com/springjk/test-yunti-speed.git
$ cd test-yunti-speed
$ composer install
$ php yunti
```

## 工作流程
1. 要求用户输入云梯账号密码并存储
2. 抓取云梯服务器列表并存储
3. 对云梯服务器列表进行 ping 测速
4. 连接延迟最小的 VPN

## License
MIT