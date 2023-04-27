<?php

namespace App;

/**
 * SQLite Create Table
 */
class SQLiteCreateZillowTable {

    /**
     * PDO object
     * @var \PDO
     */
    private $pdo;

    /**
     * Connect to the SQLite database
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * create tables 
     */
    public function createTables() {
        $commands = [
            'CREATE TABLE IF NOT EXISTS zillow (
                id INTEGER PRIMARY KEY,
                `insert_timestamp` INTEGER NOT NULL,
                zpid  INTEGER NOT NULL,
                date_sold INTEGER NOT NULL,
                price INTEGER NOT NULL,
                acres REAL,
                county TEXT)',
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_zpid 
                ON zillow (zpid)'
            ];
        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }
    }

    /**
     * drop tables 
     */
    public function dropTables() {
        $commands = [
            'DROP TABLE IF EXISTS zillow'
            ];
        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }
    }
}