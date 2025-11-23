<?php

namespace App\Domain\Users\Enums;

/**
 * Token Abilities/Scopes
 * 
 * Define what actions a token can perform.
 * Use these when creating tokens to limit their scope.
 */
enum TokenAbility: string
{
    // Read-only access
    case READ = 'read';
    case READ_MARKET = 'read:market';
    case READ_ANALYSIS = 'read:analysis';
    case READ_QUANT = 'read:quant';
    case READ_SENTIMENT = 'read:sentiment';
    
    // Write access
    case WRITE = 'write';
    case CREATE_ANALYSIS = 'create:analysis';
    case UPDATE_PROFILE = 'update:profile';
    
    // Admin abilities
    case ADMIN = 'admin';
    case MANAGE_USERS = 'manage:users';
    case MANAGE_KEYS = 'manage:api-keys';
    
    // Special abilities
    case ALL = '*';  // All abilities
    
    /**
     * Get all read abilities
     */
    public static function readAbilities(): array
    {
        return [
            self::READ->value,
            self::READ_MARKET->value,
            self::READ_ANALYSIS->value,
            self::READ_QUANT->value,
            self::READ_SENTIMENT->value,
        ];
    }
    
    /**
     * Get all write abilities
     */
    public static function writeAbilities(): array
    {
        return [
            self::WRITE->value,
            self::CREATE_ANALYSIS->value,
            self::UPDATE_PROFILE->value,
        ];
    }
    
    /**
     * Get standard user abilities (read + write)
     */
    public static function userAbilities(): array
    {
        return array_merge(
            self::readAbilities(),
            self::writeAbilities()
        );
    }
    
    /**
     * Get admin abilities
     */
    public static function adminAbilities(): array
    {
        return [
            self::ADMIN->value,
            self::MANAGE_USERS->value,
            self::MANAGE_KEYS->value,
        ];
    }
    
    /**
     * Get all abilities
     */
    public static function allAbilities(): array
    {
        return array_merge(
            self::userAbilities(),
            self::adminAbilities()
        );
    }
}
