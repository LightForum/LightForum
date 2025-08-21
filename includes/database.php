<?php
/**
 * 数据库连接类 - 完全重构版
 * 避免使用MySQL保留字，确保SQL语句安全
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $config_file = __DIR__ . '/../config/config.php';
            
            if (file_exists($config_file)) {
                require_once $config_file;
                
                $host = defined('DB_HOST') ? DB_HOST : 'localhost';
                $dbname = defined('DB_NAME') ? DB_NAME : '';
                $username = defined('DB_USER') ? DB_USER : '';
                $password = defined('DB_PASS') ? DB_PASS : '';
                
                $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            } else {
                // 安装模式，使用临时连接
                $host = isset($_SESSION['db_host']) ? $_SESSION['db_host'] : 'localhost';
                $dbname = isset($_SESSION['db_name']) ? $_SESSION['db_name'] : '';
                $username = isset($_SESSION['db_user']) ? $_SESSION['db_user'] : '';
                $password = isset($_SESSION['db_pass']) ? $_SESSION['db_pass'] : '';
                
                if (empty($dbname)) {
                    $this->pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
                } else {
                    $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
                }
            }
            
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function prepare($sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (PDOException $e) {
            throw new Exception("SQL预处理失败: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }
    
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("SQL执行失败: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("SQL查询失败: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }
    
    /**
     * 安全插入数据，避免使用保留字
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $quotedFields = array_map(function($field) {
            return "`{$field}`";
        }, $fields);
        
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $sql = "INSERT INTO `{$table}` (" . implode(', ', $quotedFields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->prepare($sql);
        foreach ($data as $field => $value) {
            $stmt->bindValue(":{$field}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 安全更新数据，避免使用保留字
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $params = [];
        
        // 构建SET部分
        foreach ($data as $field => $value) {
            $setParts[] = "`{$field}` = :set_{$field}";
            $params["set_{$field}"] = $value;
        }
        
        // 构建WHERE部分
        $whereClause = $where;
        
        // 如果whereParams是关联数组
        if (!empty($whereParams) && array_keys($whereParams) !== range(0, count($whereParams) - 1)) {
            foreach ($whereParams as $key => $value) {
                $params[$key] = $value;
            }
        } 
        // 如果whereParams是索引数组
        else if (!empty($whereParams)) {
            $i = 0;
            $whereClause = preg_replace_callback('/\?/', function() use (&$i, &$whereParams, &$params) {
                $paramName = "where_{$i}";
                $params[$paramName] = $whereParams[$i];
                $i++;
                return ":{$paramName}";
            }, $where);
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$whereClause}";
        
        $stmt = $this->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 安全删除数据
     */
    public function delete($table, $where, $whereParams = []) {
        $params = [];
        $whereClause = $where;
        
        // 如果whereParams是关联数组
        if (!empty($whereParams) && array_keys($whereParams) !== range(0, count($whereParams) - 1)) {
            foreach ($whereParams as $key => $value) {
                $params[$key] = $value;
            }
        } 
        // 如果whereParams是索引数组
        else if (!empty($whereParams)) {
            $i = 0;
            $whereClause = preg_replace_callback('/\?/', function() use (&$i, &$whereParams, &$params) {
                $paramName = "where_{$i}";
                $params[$paramName] = $whereParams[$i];
                $i++;
                return ":{$paramName}";
            }, $where);
        }
        
        $sql = "DELETE FROM `{$table}` WHERE {$whereClause}";
        
        $stmt = $this->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }
        
        return $stmt->execute();
    }
    
    /**
     * 创建数据库
     */
    public function createDatabase($dbname) {
        $sql = "CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        return $this->execute($sql);
    }
    
    /**
     * 选择数据库
     */
    public function useDatabase($dbname) {
        $sql = "USE `{$dbname}`";
        return $this->execute($sql);
    }
    
    /**
     * 获取最后插入的ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
}
?>

