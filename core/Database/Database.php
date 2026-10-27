<?php
namespace Core\Database;

use PDO;
use Exception;

class Database {
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (Exception $e) {
                throw new Exception("Database Connection Failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    public static function beginTransaction(): bool {
        return self::getConnection()->beginTransaction();
    }

    public static function commit(): bool {
        return self::getConnection()->commit();
    }

    public static function rollBack(): bool {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function insert(string $table, array $data): int {
        $fields = array_keys($data);
        $cols = implode('`, `', $fields);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO `{$table}` (`{$cols}`) VALUES ({$placeholders})";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($data);
        return (int)self::getConnection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClauses = [];
        $params = [];
        foreach ($data as $col => $val) {
            $setClauses[] = "`{$col}` = :set_{$col}";
            $params["set_{$col}"] = $val;
        }
        $setSql = implode(', ', $setClauses);
        $sql = "UPDATE `{$table}` SET {$setSql} WHERE {$where}";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(array_merge($params, $whereParams));
        return $stmt->rowCount();
    }

    public static function getNextDocumentNumber(string $docType, int $companyId = 1): string {
        $pdo = self::getConnection();
        $fy = date('Y') . '-' . substr((string)(date('Y') + 1), -2);
        
        // Find or create sequence
        $seq = self::fetch("SELECT * FROM document_sequences WHERE company_id = ? AND document_type = ? FOR UPDATE", [$companyId, $docType]);
        if (!$seq) {
            $prefix = strtoupper(substr($docType, 0, 3)) . '-' . substr(date('Y'), -2) . '-';
            $pdo->prepare("INSERT INTO document_sequences (company_id, document_type, prefix, next_number, padding, financial_year) VALUES (?, ?, ?, 1, 5, ?)")
                ->execute([$companyId, $docType, $prefix, $fy]);
            $seq = ['prefix' => $prefix, 'next_number' => 1, 'padding' => 5];
        }

        $num = str_pad((string)$seq['next_number'], (int)$seq['padding'], '0', STR_PAD_LEFT);
        $docNo = $seq['prefix'] . $num;

        $pdo->prepare("UPDATE document_sequences SET next_number = next_number + 1 WHERE company_id = ? AND document_type = ?")
            ->execute([$companyId, $docType]);

        return $docNo;
    }
}
