<?php

declare(strict_types=1);

/**
 * Énumération des rôles utilisateurs de l'application
 * Permet une gestion typée et sécurisée des permissions.
 */
enum Role: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    /**
     * Retourne le libellé lisible du rôle (ex: "Administrateur")
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::USER => 'Utilisateur',
        };
    }

    /**
     * Vérifie si le rôle actuel est celui d'un administrateur
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
