<?php
// app/Services/BackupManager.php

namespace App\Services;

use App\Database;
use Exception;
use ZipArchive;
use App\Services\Logger;

class BackupManager {
    /**
     * Generate database dump as SQL script
     */
    public static function createDatabaseBackup(): string {
        $pdo = Database::getConnection();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        
        $sqlDump = "-- Palghar LIVE Database Dump\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            $sqlDump .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
            $createTable = $pdo->query("SHOW CREATE TABLE `" . $table . "`")->fetch(\PDO::FETCH_ASSOC);
            $sqlDump .= $createTable['Create Table'] . ";\n\n";
            
            $rows = $pdo->query("SELECT * FROM `" . $table . "`")->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $sqlDump .= "INSERT INTO `" . $table . "` VALUES \n";
                $valuesList = [];
                foreach ($rows as $row) {
                    $rowValues = array_map(function($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, $row);
                    $valuesList[] = "(" . implode(", ", $rowValues) . ")";
                }
                $sqlDump .= implode(",\n", $valuesList) . ";\n\n";
            }
        }
        
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        $config = require __DIR__ . '/../Config/app.php';
        $backupDir = $config['paths']['cache'] . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filePath = $backupDir . '/db_backup_' . date('Y-m-d_H-i-s') . '.sql';
        file_put_contents($filePath, $sqlDump);
        
        Logger::info("Database backup created successfully", ['file' => basename($filePath)]);
        return $filePath;
    }

    /**
     * Zip user-uploaded media files
     */
    public static function createUploadsBackup(): string {
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive PHP extension is not enabled on this server.");
        }
        
        $config = require __DIR__ . '/../Config/app.php';
        $uploadDir = $config['paths']['uploads'];
        $backupDir = $config['paths']['cache'] . '/backups';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $zipPath = $backupDir . '/uploads_backup_' . date('Y-m-d_H-i-s') . '.zip';
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create zip archive: " . $zipPath);
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($uploadDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $count = 0;
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($uploadDir) + 1);
                $zip->addFile($filePath, $relativePath);
                $count++;
            }
        }
        
        $zip->close();
        
        Logger::info("Uploads directory backup completed", ['file' => basename($zipPath), 'files_count' => $count]);
        return $zipPath;
    }
}
