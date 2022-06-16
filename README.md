
<p align='center'><img width='260px' src='https://chaz6chez.cn/images/workbunny-logo.png' alt='workbunny'></p>

**<p align='center'>workbunny/storage</p>**

**<p align='center'>🐇 A lightweight storage for PHP based on sqlite3 🐇</p>**

# 说明

- 创建连接

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

- 执行

```php
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
$client->create('account', [
    'id' => [
        'INT',
        'PRIMARY KEY',
        'NOT NULL',
        'AUTOINCREMENT'
    ],
    'name' => [
        'VARCHAR(25)',
        'NOT NULL',
        'UNIQUE'
    ],
]);
```

- 建表且建立索引

```php
$client->create('account', [
    'id' => [
        'INT',
        'PRIMARY KEY',
        'NOT NULL',
        'AUTOINCREMENT'
    ],
    'name' => [
        'VARCHAR(25)',
        'NOT NULL',
        'UNIQUE'
    ],
],[
    'CREATE INDEX `account_name` ON `account` (`name`);'
]);
```

- 删除表

```php
$client->drop('account');
```

- 插入

```php
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

```php
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
    return true;
});
```