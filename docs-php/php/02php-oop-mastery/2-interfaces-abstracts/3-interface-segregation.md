---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-interface-segregation"
---

# La Ségrégation des Interfaces en Pratique

Le Principe de Ségrégation des Interfaces (ISP) stipule que les clients **ne doivent pas être forcés de dépendre d'interfaces qu'ils n'utilisent pas**.

## Le Problème : Les Interfaces Trop Grosses

```php
<?php
// MAUVAIS : L'interface trop grosse force des implémentations inutiles
interface CrudRepository
{
    public function find(int $id): ?object;
    public function findAll(): array;
    public function create(array $data): object;
    public function update(int $id, array $data): object;
    public function delete(int $id): void;
    public function search(string $query): array;
    public function paginate(int $page, int $perPage): array;
    public function export(): string;
    public function import(string $data): void;
}

// ReadOnlyRepository forcé d'implémenter les méthodes d'écriture !
class ReadOnlyRepository implements CrudRepository
{
    public function create(array $data): object
    {
        throw new BadMethodCallException('Lecture seule !');  // Laid !
    }

    // ... plus de méthodes inutilisées
}
```

## La Solution : Interfaces Séparées

```php
<?php
// BON : Petites interfaces ciblées
interface Readable
{
    public function find(int $id): ?object;
    public function findAll(): array;
}

interface Writable
{
    public function create(array $data): object;
    public function update(int $id, array $data): object;
    public function delete(int $id): void;
}

interface Searchable
{
    public function search(string $query): array;
}

interface Paginatable
{
    public function paginate(int $page, int $perPage): array;
}

interface Exportable
{
    public function export(): string;
}

interface Importable
{
    public function import(string $data): void;
}

// Composer uniquement ce dont vous avez besoin
class UserRepository implements Readable, Writable, Searchable
{
    // N'implémente que ce dont il a besoin
}

class ReportRepository implements Readable, Exportable
{
    // Lecture et export seulement
}

class CacheRepository implements Readable
{
    // Lecture seule — c'est logique ici
}
```

## Exemple Réel : Système de Notifications

```php
<?php
// Interfaces de notifications séparées
interface Notifiable
{
    public function getNotificationId(): string;
}

interface EmailNotifiable extends Notifiable
{
    public function getEmail(): string;
    public function getEmailName(): string;
}

interface SmsNotifiable extends Notifiable
{
    public function getPhoneNumber(): string;
}

interface PushNotifiable extends Notifiable
{
    public function getDeviceTokens(): array;
}

// L'utilisateur implémente toutes les méthodes de notification
class User implements EmailNotifiable, SmsNotifiable, PushNotifiable
{
    public function getNotificationId(): string
    {
        return (string) $this->id;
    }

    public function getEmail(): string { return $this->email; }
    public function getEmailName(): string { return $this->name; }
    public function getPhoneNumber(): string { return $this->phone; }
    public function getDeviceTokens(): array { return $this->devices; }
}

// Le compte système n'a besoin que de l'email
class SystemAccount implements EmailNotifiable
{
    public function getNotificationId(): string { return 'system'; }
    public function getEmail(): string { return 'admin@example.com'; }
    public function getEmailName(): string { return 'Système'; }
}

// Typer exactement ce dont vous avez besoin
class EmailSender
{
    public function send(EmailNotifiable $recipient, string $message): void
    {
        // N'utilise que les méthodes email
        mail($recipient->getEmail(), 'Notification', $message);
    }
}

class SmsSender
{
    public function send(SmsNotifiable $recipient, string $message): void
    {
        // N'utilise que les méthodes téléphone
        $this->twilioClient->send($recipient->getPhoneNumber(), $message);
    }
}
```

## Les Bénéfices

1. **Couplage réduit** : Les classes ne dépendent que des méthodes qu'elles utilisent
2. **Tests facilités** : Interfaces plus petites = mocks plus simples
3. **Meilleure organisation** : But clair pour chaque interface
4. **Flexibilité** : Composer les interfaces selon les besoins

## Les Grimoires

- [Les Interfaces PHP (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.interfaces.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
