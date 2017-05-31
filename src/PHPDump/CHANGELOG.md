## novadump: nova协议抓包解码工具

2017-05-30
加入mysql协议支持

2017-05-10
加入http协议抓包支持

2017-05-07
重构并加入不依赖thrift包反序列化thrift包

2017-05-04
添加copy参数，导出nova调用到nova命令行格式文件

2017-04-21
高亮 src > dst 显示

2017-04-07
发现问题: 超过65535byte的nova包受限于tcpdump版本捕获字节数不足导致程序退出

2017-03-31
1. fix bug, 在链路层linux_sll过滤非IPV4type帧, 在网络层过滤非IPV4type报文
2. 加入解析pcap文件功能, 异步分段读取文件, 无论文件多大只占用很少内存(依赖yz-swoole 2.x版本, 文件异步读取接口)
3. thrift replay|exception 打印具体异常类型
4. 从linux_sll读取时间戳, 替代当前时间显示

2017-03-07
1. 从 `tcpdump -w` 实时解码pcap流: pcap -> linux sll -> ip -> tcp -> nova -> thrfit
2. 支持从项目读取thrift spec声明文件, 解码完整的thrift对象
注意: !!! pcap文件 链路层不是以太网协议, 而是linux_sll