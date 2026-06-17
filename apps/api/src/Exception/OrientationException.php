<?php

namespace App\Exception;

/**
 * Erreur métier du moteur d'orientation, porteuse d'un code HTTP à renvoyer au client.
 */
final class OrientationException extends \RuntimeException
{
    public function __construct(string $message, private readonly int $statusCode = 400)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function notFound(string $message): self
    {
        return new self($message, 404);
    }

    public static function invalid(string $message): self
    {
        return new self($message, 400);
    }

    /**
     * Incohérence de configuration de l'arbre (donnée de seed manquante).
     */
    public static function misconfigured(string $message): self
    {
        return new self($message, 500);
    }
}
