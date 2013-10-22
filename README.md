Up-Server
=====================
上传服务器端代码，支持断点查询，超大文件上传，多线程上传，多文件一次上传
* * *

使用方法
-----------
> 1. 将up.php拷贝到网站根目录
> 2. 根目录创建uploads文件夹用于存储上传文件,配置权限


使用示例
----------

*   普通文件上传（完成上传返回201）
```
curl -F "action=upload" -F "Filedata=@a.file" -v "http://127.0.0.1/up.php"
* About to connect() to 127.0.0.1 port 80 (#0)
*   Trying 127.0.0.1... connected
> POST /up.php HTTP/1.1
> User-Agent: curl/7.23.1 (x86_64-unknown-linux-gnu) libcurl/7.23.1 OpenSSL/0.9.8h zlib/1.2.3
> Host: 127.0.0.1
> Accept: */*
> Content-Length: 21810
> Expect: 100-continue
> Content-Type: multipart/form-data; boundary=----------------------------e0b0c03a69b3
>
< HTTP/1.1 100 Continue
< HTTP/1.1 201 Created
< Server: ngx_openresty
< Date: Mon, 14 Oct 2013 02:09:55 GMT
< Content-Type: text/html
< Transfer-Encoding: chunked
< Connection: keep-alive
<
* Connection #0 to host 127.0.0.1 left intact
Upload OK* Closing connection #0
```

*   指定分片上传（未完成上传返回202并返回下一个需要上传分片信息，上传完毕返回201，上传错误返回500，对此客户端可以查询上传状态）
```
curl -F "action=upload" -F "Filedata=@a.file" -H "Range: bytes=30720-52226/52227" -v "http://127.0.0.1/up.php"
* About to connect() to 127.0.0.1 port 80 (#0)
*   Trying 127.0.0.1... connected
> POST /up.php HTTP/1.1
> User-Agent: curl/7.23.1 (x86_64-unknown-linux-gnu) libcurl/7.23.1 OpenSSL/0.9.8h zlib/1.2.3
> Host: 127.0.0.1
> Accept: */*
> Range: bytes=30720-52226/52227
> Content-Length: 21810
> Expect: 100-continue
> Content-Type: multipart/form-data; boundary=----------------------------ae9de390286d
>
< HTTP/1.1 100 Continue
< HTTP/1.1 202 Accepted
< Server: ngx_openresty
< Date: Mon, 14 Oct 2013 02:14:41 GMT
< Content-Type: text/html
< Transfer-Encoding: chunked
< Connection: keep-alive
< Range: bytes=0-30719/52227
<
* Connection #0 to host 127.0.0.1 left intact
Upload Continue* Closing connection #0
```

*   查询文件上传状态（上传完毕返回201，不存在此文件返回404，未上传完毕返回202并下一个需要上传的分片范围） 
```
curl -I -H "Filename: a.file" "http://127.0.0.1/up.php"
HTTP/1.1 202 Accepted
Server: ngx_openresty
Date: Mon, 14 Oct 2013 02:16:25 GMT
Content-Type: text/html
Connection: keep-alive
Range: bytes=0-30719/52227
```

*   对于大文件和超大文件可以通过多次分片上传实现
```
curl -F "action=upload" -F "Filedata=@big.file" -H "Range: bytes=0-102399/1024000000" -v "http://127.0.0.1/up.php"
curl -F "action=upload" -F "Filedata=@big.file" -H "Range: bytes=102400-204799/1024000000" -v "http://127.0.0.1/up.php"
curl -F "action=upload" -F "Filedata=@big.file" -H "Range: bytes=204800-303599/1024000000" -v "http://127.0.0.1/up.php"
...
```

*   支持同时上传多个文件（不可与分片上传同时使用）
```
curl -F "action=upload" -F "Filedata1=@nspclient.js" -F "Filedata2=@nspclient_test.js" -v "http://127.0.0.1/up.php"
* About to connect() to 127.0.0.1 port 80 (#0)
*   Trying 127.0.0.1... connected
* Connected to 127.0.0.1 (127.0.0.1) port 80 (#0)
> POST /up.php HTTP/1.1
> User-Agent: curl/7.19.7 (x86_64-suse-linux-gnu) libcurl/7.19.7 OpenSSL/0.9.8j zlib/1.2.3 libidn/1.10
> Host: 127.0.0.1
> Accept: */*
> Content-Length: 13402
> Expect: 100-continue
> Content-Type: multipart/form-data; boundary=----------------------------8ed05e6400ca
>
< HTTP/1.1 100 Continue
< HTTP/1.1 201 Created
< Server: ngx_openresty
< Date: Wed, 23 Oct 2013 14:54:31 GMT
< Content-Type: text/html
< Transfer-Encoding: chunked
< Connection: keep-alive
<
* Connection #0 to host 127.0.0.1 left intact
* Closing connection #0
Upload OK
```

*   由于服务器端文件一旦写入，不会更改，所以支持多线程上传（由于未加锁，存在重复上传某分片的问题）

Weibo Account
-------------

Have a question? [@littley](http://weibo.com/littley)

