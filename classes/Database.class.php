<?php

class Database {
    private $dbh;
    private $stmt;
    private $currentQuery;
    public $error;

    public function __construct($dbType, $host, $dbname, $user, $pass, $port=null) {
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8';

        switch (strtolower($dbType)) {
            case 'mysql':
                $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
                $options = [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
                ];
                break;

            case 'sqlsrv':
                if ($port) {
                    $dsn = "sqlsrv:Server=$host,$port;Database=$dbname";
                } else {
                    $dsn = "sqlsrv:Server=$host;Database=$dbname";
                }
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ];
                break;

            case 'dblib':
                $dsn = "dblib:host=$host;dbname=$dbname";
                $options = [];
                break;

            default:
                throw new Exception("Driver DB non supportato: $dbType");
        }
        try {
            $this->dbh = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            if (defined('DEBUG') && DEBUG) echo "❌ Connessione fallita: " . $this->error;
        }
    }

    public function prepare($query) {
        $this->stmt = $this->dbh->prepare($query);
        $this->currentQuery = $query;
    }

    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value): $type = PDO::PARAM_INT; break;
                case is_bool($value): $type = PDO::PARAM_BOOL; break;
                case is_null($value): $type = PDO::PARAM_NULL; break;
                default: $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        if (isset($this->currentQuery)) {
            $this->currentQuery = str_replace($param, '"' . $value . '"', $this->currentQuery);
        }
    }

    public function execute() {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("❌ ERRORE QUERY: " . $this->debugSql());
            error_log("📣 MESSAGGIO: " . $this->error);
            return false;
        }
    }

    public function fetch() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rowCount() {
        return $this->stmt->rowCount();
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }

    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }

    public function commit() {
        return $this->dbh->commit();
    }

    public function rollBack() {
        return $this->dbh->rollBack();
    }

    public function inTransaction() {
        return $this->dbh->inTransaction();
    }

    public function debugSql() {
        return $this->currentQuery;
    }

    public function debugDumpParams() {
        ob_start();
        $this->stmt->debugDumpParams();
        return ob_get_clean();
    }
}
