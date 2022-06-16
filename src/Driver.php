<?php
declare(strict_types=1);

namespace WorkBunny\Storage;

use Closure;
use SQLite3;
use SQLite3Result;
use Throwable;
use WorkBunny\Storage\Exceptions\StorageException;

class Driver
{
    /** @see gettype() @var array 类型映射 */
    protected array $_typeMap = [
        'NULL'              => SQLITE3_NULL,
        'integer'           => SQLITE3_INTEGER,
        'double'            => SQLITE3_FLOAT,
        'boolean'           => SQLITE3_INTEGER,
        'string'            => SQLITE3_TEXT,
        'object'            => SQLITE3_BLOB,
        'resource'          => SQLITE3_BLOB,
        'resource (closed)' => SQLITE3_BLOB,
        'unknown type'      => SQLITE3_TEXT
    ];

    /** @var SQLite3|null  */
    protected ?SQLite3 $_driver = null;

    /**
     * @var array = [
     *  ‘filename’ => '',
     *  'flags' => 'SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE',
     *  'encryptionKey' => '',
     *  'debug' => true
     * ]
     */
    protected array $_configs = [];

    /** @var float 最后一次的耗时 */
    protected float $_lastDuration = 0.0;

    /** @var string|null 最后一次SQL */
    protected ?string $_lastSQL = null;

    /** @var array 最后一次的map */
    protected array $_lastMap = [];

    /**
     * 执行结束回调
     * @var Closure|null
     */
    protected static ?Closure $onAfterExec = null;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        if(!isset($configs['filename'])){
            throw new StorageException('invalid config. ');
        }
        $this->_configs = $configs;
        $this->connect();
    }

    /** 关闭连接 */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 回调入参第一个参数为$this，第二个参数为bool类型的是否执行成功
     * @param Closure $closure = function(Driver, bool){}
     * @return void
     */
    public static function onAfterExec(Closure $closure){
        self::$onAfterExec = $closure;
    }

    /**
     * @param string $string
     * @param array $map
     * @return Raw
     */
    public static function raw(string $string, array $map = []): Raw
    {
        return new Raw($map, $string);
    }

    /** 是否是debug */
    public function isDebug(): bool
    {
        return (isset($this->_configs['debug']) and $this->_configs['debug']);
    }

    /**
     * @throws StorageException
     */
    public function connect() : void
    {
        if($this->isDebug()){
            return;
        }
        if(!$this->_driver instanceof SQLite3){
            $this->_driver = new SQLite3(
                $this->_configs['filename'],
                $this->_configs['flags'] ?? SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE,
                $this->_configs['encryptionKey'] ?? '',
            );
            $this->_driver->enableExceptions(true);

            if($this->_configs['commands'] ?? null){
                foreach ($this->_configs['commands'] as $command){
                    $this->_driver->exec($command);
                }
            }
        }
    }

    /** 关闭连接 */
    public function close()
    {
        if($this->_driver instanceof SQLite3){
            $this->_driver->close();
        }
        $this->_driver = null;
    }

    /**
     * 获取SQLite3实例
     * @return SQLite3|null
     */
    public function driver() : ?SQLite3
    {
        return $this->_driver;
    }

    /**
     * 获取配置信息
     * @return array
     */
    public function configs() : array
    {
        return $this->_configs;
    }

    /**
     * 执行
     * @param string $statement
     * @return bool
     * @throws StorageException
     */
    public function exec(string $statement): bool
    {
        try {
            $this->connect();
            $this->_log($statement = $this->_buildRaw(self::raw($statement, $map = []), $map), $map);
            if($this->isDebug()){
                return true;
            }
            $start = microtime(true);
            return $this->driver()->exec($statement);
        }catch (StorageException $exception){
            throw $exception;
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        } finally {
            if(isset($start)){
                $this->_lastDuration = microtime(true) - $start;
            }
            if(self::$onAfterExec){
                (self::$onAfterExec)($this, !isset($throwable));
            }
        }
    }

    /**
     * query执行
     * @param string $statement
     * @param array $map
     * @return SQLite3Result|null
     * @throws StorageException
     */
    public function query(string $statement, array $map = []): ?SQLite3Result
    {
        try {
            $this->connect();
            $this->_log($statement = $this->_buildRaw(self::raw($statement, $map), $map), $map);
            if($this->isDebug()){
                return null;
            }
            $start = microtime(true);
            return $this->driver()->query($statement) ?? null;
        }catch (StorageException $exception){
            throw $exception;
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        } finally {
            if(isset($start)){
                $this->_lastDuration = microtime(true) - $start;
            }
            if(self::$onAfterExec){
                (self::$onAfterExec)($this, !isset($throwable));
            }
        }
    }

    /**
     * 预处理执行
     * @param string $statement
     * @param array $map
     * @return SQLite3Result|null
     * @throws StorageException
     */
    public function execute(string $statement, array $map = []): ?SQLite3Result
    {
        try {
            $this->connect();
            $this->_log($statement, $map);
            if($this->isDebug()){
                return null;
            }
            if(!$stmt = $this->driver()->prepare($statement) ?? null){
                throw new StorageException(
                    $this->driver()->lastErrorMsg() ?? 'prepare failed. ',
                    $this->driver()->lastErrorCode() ?? null,
                );
            }
            foreach ($map as $key => $value) {
                $stmt->bindValue($key, $value[0], $value[1]);
            }
            $start = microtime(true);
            if(!$result = $stmt->execute() ?? null){
                throw new StorageException(
                    $this->driver()->lastErrorMsg() ?? 'exec failed. ',
                    $this->driver()->lastErrorCode() ?? null
                );
            }
            return $result;
        }catch (StorageException $exception){
            throw $exception;
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        } finally {
            if($success = !isset($throwable) and isset($start)){
                $this->_lastDuration = microtime(true) - $start;
            }
            if(self::$onAfterExec){
                (self::$onAfterExec)($this, $success);
            }
        }
    }

    /**
     * @param $raw
     * @return bool
     */
    public function isRaw($raw) : bool
    {
        if($raw instanceof Raw){
            return true;
        }
        return false;
    }

    /**
     * @param string $string
     * @return string
     */
    public function quote(string $string): string
    {
        return "'" . preg_replace('/\'/', '\'\'', $string) . "'";
    }

    /**
     * @param string $table
     * @return string
     * @throws StorageException
     */
    public function tableQuote(string $table): string
    {
        if (preg_match('/^[\p{L}_][\p{L}\p{N}@$#\-_]*$/u', $table)) {
            return '`' . $table . '`';
        }
        throw new StorageException("Incorrect Table Name: {$table}.");
    }

    /**
     * @param string $column
     * @return string
     * @throws StorageException
     */
    public function columnQuote(string $column): string
    {
        if (preg_match('/^[\p{L}_][\p{L}\p{N}@$#\-_]*(\.?[\p{L}_][\p{L}\p{N}@$#\-_]*)?$/u', $column)) {
            return strpos($column, '.') !== false ?
                '`' . str_replace('.', '`.`', $column) . '`' :
                '`' . $column . '`';
        }
        throw new StorageException("Incorrect column name: {$column}.");
    }

    /**
     * @param string $table
     * @param array $columns
     * @param string[] $indexSQLs
     * @return bool
     * @throws StorageException
     */
    public function create(string $table, array $columns, array $indexSQLs = []): bool
    {
        $stack = [];
        $tableName = $this->tableQuote($table);

        foreach ($columns as $name => $definition) {
            if (is_int($name)) {
                $stack[] = preg_replace('/\<([\p{L}_][\p{L}\p{N}@$#\-_]*)\>/u', '"$1"', $definition);
            } elseif (is_array($definition)) {
                $stack[] = $this->columnQuote((string)$name) . ' ' . implode(' ', $definition);
            } elseif (is_string($definition)) {
                $stack[] = $this->columnQuote((string)$name) . ' ' . $definition;
            }
        }

        $command = "CREATE TABLE IF NOT EXISTS {$tableName} (" . implode(',', $stack) . ');';
        foreach ($indexSQLs as $indexSQL){
            $command .= $indexSQL;
        }

        return $this->exec($command);
    }

    /**
     * @param string $table
     * @return bool
     * @throws StorageException
     */
    public function drop(string $table): bool
    {
        return $this->exec('DROP TABLE IF EXISTS ' . $this->tableQuote($table));
    }

    /**
     * @param string $table
     * @param array $values = [
     *  'a' => true,
     *  'b' => 2,
     *  'c' => 3.0,
     *  'd' => '4'
     * ]
     * @return SQLite3Result|null
     * @throws StorageException
     */
    public function insert(string $table, array $values): ?SQLite3Result
    {
        $stack = [];
        $map = [];
        $columns = [];
        $values = count($values) === count($values, COUNT_RECURSIVE) ? [$values] : $values;

        foreach ($values as $index => $value){
            $data = [];
            foreach ($value as $k => $v){
                $columns[] = $this->columnQuote($k);

                if ($raw = $this->_buildRaw($data, $map)) {
                    $data[] = $raw;
                    continue;
                }

                $map[$data[] = $this->_mapKey("{$k}_{$index}")] = $this->_typeMap($v);
            }
            $stack[] = '(' . implode(', ', $data) . ')';
        }
        $query = 'INSERT INTO ' . $this->tableQuote($table)
            . ' (' . implode(', ', array_flip(array_flip($columns))) . ')'
            . ' VALUES ' . implode(', ', $stack);

        return $this->execute($query, $map);
    }

    /**
     * @param callable $actions
     * @return void
     * @throws StorageException
     */
    public function action(callable $actions): void
    {
        try {
            $this->begin();
            if ($actions($this) === false) {
                $this->rollback();
            } else {
                $this->commit();
            }
        } catch (Throwable $e) {
            $this->rollback();
            throw new StorageException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param callable $function
     * @param string ...$values
     * @return bool
     */
    public function function(callable $function, string ...$values): bool
    {
        try{
            $this->connect();
            $this->driver()->createFunction('func', $function);
            return $this->exec(
                $values ?
                    'SELECT func(' . implode(', ', $values) . ');' :
                    'SELECT func();'
            );
        }catch (StorageException $exception){
            throw $exception;
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * 开启事务
     * @return void
     * @throws StorageException
     */
    public function begin(){
        try {
            $this->exec('BEGIN;');
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * 回滚事务
     * @return void
     * @throws StorageException
     */
    public function rollback(){
        try {
            $this->exec('ROLLBACK;');
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * 提交事务
     * @return void
     * @throws StorageException
     */
    public function commit(){
        try {
            $this->exec('COMMIT;');
        }catch (Throwable $throwable){
            throw new StorageException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param bool $expend
     * @return array = [ (string|null) LAST_SQL, (float) LAST_DURATION ]
     */
    public function last(bool $expend = false): array
    {
        return [
            $expend ? $this->generate($this->_lastSQL, $this->_lastMap) : $this->_lastSQL,
            $this->_lastDuration
        ];
    }

    /**
     * @todo 大字符串的执行效率比较低
     * @param string $statement
     * @param array $map
     * @return string
     */
    public function generate(string $statement, array $map): string
    {
        $statement = preg_replace(
            '/(?!\'[^\s]+\s?)"([\p{L}_][\p{L}\p{N}@$#\-_]*)"(?!\s?[^\s]+\')/u',
            '`$1`',
            $statement
        );
        foreach ($map as $key => $value) {
            if ($value[1] === SQLITE3_TEXT) {
                $replace = $this->quote((string)$value[0]);
            } elseif ($value[1] === SQLITE3_NULL) {
                $replace = 'NULL';
            } elseif ($value[1] === SQLITE3_BLOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = $value[0] . '';
            }
            $statement = str_replace($key, $replace, $statement);
        }
        return $statement;
    }

    /**
     * @param string $statement
     * @param array $map
     * @return void
     */
    protected function _log(string $statement, array $map): void
    {
        $this->_lastSQL = $statement;
        $this->_lastMap = $map;
    }

    /**
     * @param $value
     * @return array = [value, SQLITE_TYPE]
     */
    protected function _typeMap($value): array
    {
        $type = gettype($value);
        if ($type === 'boolean') {
            $value = $value ? 1 : 0;
        }
        return [$value, $this->_typeMap[$type]];
    }

    /**
     * @param string $value
     * @return string
     */
    protected function _mapKey(string $value): string
    {
        return ":wb_{$value}";
    }

    /**
     * @param $object
     * @return bool
     */
    protected function _isRaw($object): bool
    {
        if(is_object($object)){
            return $object instanceof Raw;
        }
        return false;
    }

    /**
     * @param mixed $raw
     * @param array $map
     * @return string|null
     */
    protected function _buildRaw($raw, array &$map): ?string
    {
        if (!$this->_isRaw($raw)) {
            return null;
        }
        $query = preg_replace_callback(
            '/(([`\']).*?)?((FROM|TABLE|INTO|UPDATE|JOIN)\s*)?\<(([\p{L}_][\p{L}\p{N}@$#\-_]*)(\.[\p{L}_][\p{L}\p{N}@$#\-_]*)?)\>([^,]*?\2)?/u',
            function ($matches) {
                if (!empty($matches[2]) && isset($matches[8])) {
                    return $matches[0];
                }
                if (!empty($matches[4])) {
                    return $matches[1] . $matches[4] . ' ' . $this->tableQuote($matches[5]);
                }
                return $matches[1] . $this->columnQuote((string)$matches[5]);
            },
            $raw->value
        );
        $rawMap = $raw->map;
        if (!empty($rawMap)) {
            foreach ($rawMap as $key => $value) {
                $map[$key] = $this->_typeMap($value);
            }
        }
        return $query;
    }
}