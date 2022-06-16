
<p align='center'><img width='260px' src='https://chaz6chez.cn/images/workbunny-logo.png' alt='workbunny'></p>

**<p align='center'>workbunny/storage</p>**

**<p align='center'>🐇 A lightweight storage for PHP based on sqlite3 🐇</p>**

# 说明

- 创建连接

**:memory:** 内存数据库最好用作临时数据库

```php
$client = new \WorkBunny\Storage\Driver([
    # 内存数据库
    'filename' => ':memory:',
    # test.db文件数据库
//    'filename' => 'test.db',

    'flags' => 'SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE',
    'encryptionKey' => ''
]);
```
- 注册执行回调

每次SQL执行完后会触发执行回调

```php
# 注册执行结束回调事件
\WorkBunny\Storage\Driver::onAfterExec(function (\WorkBunny\Storage\Driver $driver){
    # 打印sql及执行时长
    var_dump($driver->last(true));
});
```

- 执行

在执行大SQL语句时，预处理执行的耗时比较长

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# 预处理执行
$res = $client->execute('SELECT * FROM `account` WHERE `id` = 1;');
if($res instanceof SQLite3Result){
    var_dump($res->fetchArray());
    # 受影响行数
    $client->driver()->changes();
    # 最后插入的行号
    $client->driver()->lastInsertRowID();
}

# 普通执行
$res = $client->query('SELECT * FROM `account` WHERE `id` = 1;');
if($res instanceof SQLite3Result){
    var_dump($res->fetchArray());
    # 受影响行数
    $client->driver()->changes();
    # 最后插入的行号
    $client->driver()->lastInsertRowID();
}

# 仅执行
// 成功返回true 失败返回false
$res = $client->exec('SELECT * FROM `account` WHERE `id` = 1;');

```

- 建表

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

$client->create('account', [
    'id' => [
        'INT',
        'PRIMARY KEY',
        'NOT NULL',
    ],
    'name' => [
        'VARCHAR(25)',
        'NOT NULL',
    ],
]);
```

- 建表且建立索引

过多的索引会影响数据插入

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

$client->create('account', [
    'id' => [
        'INT',
        'PRIMARY KEY',
        'NOT NULL',
    ],
    'name' => [
        'VARCHAR(25)',
        'NOT NULL',
    ],
],[
    'CREATE INDEX `account_name` ON `account` (`name`);'
]);
```

- 删除表

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

$client->drop('account');
```

- 插入

尽可能的避免插入多条，插入多条可能会拼接成一个大SQL，导致SQL超出范围或是预处理执行耗时过高；
在事务内循环插入是个好的替代方案；

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# 一次插入单条
$client->insert('account', [
    'id' => 1,
    'name' => 'test'
]);

# 一次插入多条
$client->insert('account', [
    [
        'id' => 1,
        'name' => 'test1'
    ],
    [
        'id' => 2,
        'name' => 'test2'
    ]
]);
```

- 事务

使用 **action()** 时，回调函数内返回false或者抛出异常都可中断事务并回滚

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# 开启
$client->begin();
# 回滚
$client->rollback();
# 提交
$client->commit();

# 事务执行

# 一次插入多条
$client->action(function () {
    $client->insert('account', [
        'id' => 1,
        'name' => 'test1'
    ]);
    
    $client->insert('account', [
        'id' => 2,
        'name' => 'test2'
    ]);
    
    $client->insert('account', [
        'id' => 3,
        'name' => 'test3'
    ]);
    
    # 返回false或者异常抛出则回滚
});
```