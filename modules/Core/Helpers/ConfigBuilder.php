<?php

namespace Modules\Core\Helpers;

use Modules\Theme\ThemeManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConfigBuilder
{
    /**
     * Initialize the configuration system
     */
    public static function init(): void
    {
        self::maybeMigrateToJson();
    }

    /**
     * Get the path to the config file
     */
    public static function getConfigFile(): string
    {
        return storage_path('bc.json');
    }

    /**
     * Get the backup config file path
     */
    protected static function getBackupConfigFile(): string
    {
        return storage_path('bc.json.bak');
    }

    /**
     * Get all configuration as an array
     */
    public static function all(): array
    {
        $configFile = self::getConfigFile();
        
        if (!File::exists($configFile)) {
            return [];
        }

        try {
            $content = File::get($configFile);
            $config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return is_array($config) ? $config : [];
        } catch (Throwable $e) {
            Log::error('Failed to read config file: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the list of whitelisted config keys that can be modified
     */
    protected static function getWhitelistedKeys(): array
    {
        return [
            // Add your whitelisted config keys here
            // Example: 'app_name', 'timezone', 'mail_driver', etc.
            
            ...WHITELISTED_CONFIG_KEYS,
        ];
    }

    /**
     * Check if a config key is whitelisted for modification
     */
    protected static function isKeyWhitelisted(string $key): bool
    {
        $whitelist = self::getWhitelistedKeys();
        return empty($whitelist) || in_array($key, $whitelist, true);
    }

    /**
     * Update a configuration value
     * 
     * @throws \RuntimeException If the key is not whitelisted or update fails
     */
    public static function updateConfig(string $name, $value): bool
    {
        if (!self::isKeyWhitelisted($name)) {
            throw new \RuntimeException("The config key [{$name}] is not whitelisted for modification.");
        }

        try {
            $config = self::all();
            $config[$name] = $value;
            
            // Validate the value can be JSON encoded
            json_encode($config, JSON_THROW_ON_ERROR);
            
            // Create backup
            $configFile = self::getConfigFile();
            $backupFile = self::getBackupConfigFile();
            
            if (File::exists($configFile)) {
                File::copy($configFile, $backupFile);
            }
            
            // Write with exclusive lock
            $result = File::put(
                $configFile,
                json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                LOCK_EX
            );
            
            if ($result === false) {
                throw new \RuntimeException('Failed to write config file');
            }
            
            // Remove backup if write was successful
            if (File::exists($backupFile)) {
                File::delete($backupFile);
            }
            
            return true;
        } catch (Throwable $e) {
            Log::error('Failed to update config: ' . $e->getMessage());
            
            // Try to restore from backup on failure
            if (isset($backupFile) && File::exists($backupFile)) {
                File::move($backupFile, $configFile);
            }
            
            return false;
        }
    }

    /**
     * Get a configuration value
     */
    public static function get(string $key, $default = null)
    {
        $config = self::all();
        return $config[$key] ?? $default;
    }

    /**
     * Check if a configuration key exists
     */
    public static function has(string $key): bool
    {
        $config = self::all();
        return array_key_exists($key, $config);
    }

    /**
     * Migrate from PHP to JSON config if needed
     */
    protected static function maybeMigrateToJson(): void
    {
        $configFile = self::getConfigFile();
        
        // If JSON config already exists, nothing to do
        if (File::exists($configFile)) {
            return;
        }

        // Define default configuration
        $data = [
            'BC_ACTIVE_THEME' => ThemeManager::current(),
            'BC_ACTIVE_STYLE' => 'default',
        ];

        try {
            // Ensure directory exists
            File::ensureDirectoryExists(dirname($configFile), 0755, true);
            
            // Write initial config
            File::put(
                $configFile,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                LOCK_EX
            );

            // Only delete old file if JSON write was successful
            if (defined('CONFIG_PHP_PATH') && File::exists(CONFIG_PHP_PATH)) {
                // Read old values from PHP config
                $oldValues = [];
                
                try {
                    $oldConfig = require CONFIG_PHP_PATH;
                    if (is_array($oldConfig)) {
                        foreach ($oldConfig as $key => $value) {
                            if (is_string($key) && is_scalar($value)) {
                                $data[$key] = $value;
                            }
                        }
                        // Update JSON with old values
                        File::put(
                            $configFile,
                            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                            LOCK_EX
                        );
                    }
                    
                    // Only delete after successful migration
                    File::delete(CONFIG_PHP_PATH);
                } catch (Throwable $e) {
                    Log::warning('Failed to migrate old config: ' . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            Log::error('Migration to JSON config failed: ' . $e->getMessage());
        }
    }
}
