<?php
/**
 * CivicScan – Database Connection (PDO Singleton)
 */

require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $instance = null;

    public static function conn(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed.']));
            }
        }
        return self::$instance;
    }

    // Prevent cloning
    private function __clone() {}
}

/**
 * Helper: run a query with optional bindings and return the statement.
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = DB::conn()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Helper: fetch one row.
 */
function db_row(string $sql, array $params = []): ?array {
    $row = db_query($sql, $params)->fetch();
    return $row ?: null;
}

/**
 * Helper: fetch all rows.
 */
function db_rows(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * Helper: get last insert id.
 */
function db_last_id(): string {
    return DB::conn()->lastInsertId();
}
