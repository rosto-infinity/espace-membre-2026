<?php

declare(strict_types=1);

/**
 * Enregistre un message flash en session
 * @param 'success'|'error' $type
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupère et supprime le message flash de la session
 * Retourne null s'il n'y a pas de message en attente
 */
function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
