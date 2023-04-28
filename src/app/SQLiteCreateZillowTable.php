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
            'CREATE TABLE IF NOT EXISTS zillow_sold (
                id INTEGER PRIMARY KEY,
                insert_timestamp INTEGER NOT NULL,
                zpid  INTEGER NOT NULL,
                date_sold INTEGER NOT NULL,
                price INTEGER NOT NULL,
                acres REAL NOT NULL,
                county TEXT NOT NULL)',
                
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_zpid 
                ON zillow_sold (zpid)',

            'CREATE INDEX IF NOT EXISTS idx_datesold 
            ON zillow_sold (date_sold)',

            'CREATE TABLE IF NOT EXISTS zillow_sold_totals (
                id INTEGER PRIMARY KEY,
                insert_timestamp INTEGER NOT NULL,
                county TEXT NOT NULL,
                total INTEGER NOT NULL)',

            'CREATE TABLE IF NOT EXISTS zillow_forsale (
                id INTEGER PRIMARY KEY,
                insert_timestamp INTEGER NOT NULL,
                zpid  INTEGER NOT NULL,
                days_on_zillow INTEGER NOT NULL,
                price INTEGER NOT NULL,
                acres REAL NOT NULL,
                county TEXT NOT NULL)',

            'CREATE UNIQUE INDEX IF NOT EXISTS idx_zpid 
                ON zillow_forsale (zpid)',

            'CREATE TABLE IF NOT EXISTS zillow_forsale_totals (
                id INTEGER PRIMARY KEY,
                insert_timestamp INTEGER NOT NULL,
                county TEXT NOT NULL,
                total INTEGER NOT NULL)'];
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
            'DROP TABLE IF EXISTS zillow_sold',
            'DROP TABLE IF EXISTS zillow_sold_totals',
            'DROP TABLE IF EXISTS zillow_forsale',
            'DROP TABLE IF EXISTS zillow_forsale_totals'
            ];
        // execute the sql commands to create new tables
        foreach ($commands as $command) {
            $this->pdo->exec($command);
        }
    }
}