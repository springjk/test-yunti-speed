# 云梯测速 PHP 脚本
## 功能
为云梯 VPN 进行 `ping` 测速选择最优路线并自动连接

## 已知问题
* macOS Sierra 已移除 PPTP 类型 VPN 原生支持
* IKEV2 类型 VPN 无法通过 Apple Script 及 Command 方式连接

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

## One More Thing
该脚本通过访问云梯网站获取服务器信息，如需通过本地配置进行测速可以使用 [VPN-Helper](https://github.com/springjk/vpn-helper)，该项目针对更多厂商提供的 VPN，受众更广。

## License
MIT