
<p align='center'><img width='260px' src='https://chaz6chez.cn/images/workbunny-logo.png' alt='workbunny'></p>

**<p align='center'>workbunny/storage</p>**

**<p align='center'>ð A lightweight storage for PHP based on sqlite3 ð</p>**

[![Latest Stable Version](http://poser.pugx.org/workbunny/storage/v)](https://packagist.org/packages/workbunny/storage) [![Total Downloads](http://poser.pugx.org/workbunny/storage/downloads)](https://packagist.org/packages/workbunny/storage) [![Latest Unstable Version](http://poser.pugx.org/workbunny/storage/v/unstable)](https://packagist.org/packages/workbunny/storage) [![License](http://poser.pugx.org/workbunny/storage/license)](https://packagist.org/packages/workbunny/storage) [![PHP Version Require](http://poser.pugx.org/workbunny/storage/require/php)](https://packagist.org/packages/workbunny/storage)

# è¯´æ

- åå»ºè¿æ¥

**:memory:** åå­æ°æ®åºæå¥½ç¨ä½ä¸´æ¶æ°æ®åº

```php
$client = new \WorkBunny\Storage\Driver([
    # åå­æ°æ®åº
    'filename' => ':memory:',
    # test.dbæä»¶æ°æ®åº
//    'filename' => 'test.db',

    'flags' => 'SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE',
    'encryptionKey' => ''
]);
```
- æ³¨åæ§è¡åè°

æ¯æ¬¡SQLæ§è¡å®åä¼è§¦åæ§è¡åè°

```php
# æ³¨åæ§è¡ç»æåè°äºä»¶
\WorkBunny\Storage\Driver::onAfterExec(function (\WorkBunny\Storage\Driver $driver){
    # æå°sqlåæ§è¡æ¶é¿
    var_dump($driver->last(true));
});
```

- æ§è¡

å¨æ§è¡å¤§SQLè¯­å¥æ¶ï¼é¢å¤çæ§è¡çèæ¶æ¯è¾é¿

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# é¢å¤çæ§è¡
$res = $client->execute('SELECT * FROM `account` WHERE `id` = 1;');
if($res instanceof SQLite3Result){
    var_dump($res->fetchArray());
    # åå½±åè¡æ°
    $client->driver()->changes();
    # æåæå¥çè¡å·
    $client->driver()->lastInsertRowID();
}

# æ®éæ§è¡
$res = $client->query('SELECT * FROM `account` WHERE `id` = 1;');
if($res instanceof SQLite3Result){
    var_dump($res->fetchArray());
    # åå½±åè¡æ°
    $client->driver()->changes();
    # æåæå¥çè¡å·
    $client->driver()->lastInsertRowID();
}

# ä»æ§è¡
// æåè¿åtrue å¤±è´¥è¿åfalse
$res = $client->exec('SELECT * FROM `account` WHERE `id` = 1;');

```

- å»ºè¡¨

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

- å»ºè¡¨ä¸å»ºç«ç´¢å¼

è¿å¤çç´¢å¼ä¼å½±åæ°æ®æå¥

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

- å é¤è¡¨

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

$client->drop('account');
```

- æå¥

å°½å¯è½çé¿åæå¥å¤æ¡ï¼æå¥å¤æ¡å¯è½ä¼æ¼æ¥æä¸ä¸ªå¤§SQLï¼å¯¼è´SQLè¶åºèå´ææ¯é¢å¤çæ§è¡èæ¶è¿é«ï¼
å¨äºå¡åå¾ªç¯æå¥æ¯ä¸ªå¥½çæ¿ä»£æ¹æ¡ï¼

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# ä¸æ¬¡æå¥åæ¡
$client->insert('account', [
    'id' => 1,
    'name' => 'test'
]);

# ä¸æ¬¡æå¥å¤æ¡
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

- äºå¡

ä½¿ç¨ **action()** æ¶ï¼åè°å½æ°åè¿åfalseæèæåºå¼å¸¸é½å¯ä¸­æ­äºå¡å¹¶åæ»

```php
$client = new \WorkBunny\Storage\Driver(['filename' => ':memory:']);

# å¼å¯
$client->begin();
# åæ»
$client->rollback();
# æäº¤
$client->commit();

# äºå¡æ§è¡
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
    
    # è¿åfalseæèå¼å¸¸æåºååæ»
});
```
