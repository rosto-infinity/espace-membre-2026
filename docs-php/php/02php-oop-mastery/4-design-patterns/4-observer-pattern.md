---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-observer-pattern"
---

# Le Pattern Observateur (Observer Pattern)

Le pattern Observateur **définit une dépendance un-à-plusieurs** entre objets. Quand un objet change d'état, tous ses dépendants sont notifiés automatiquement.

## Implémentation de Base

```php
<?php
interface Observer
{
    public function update(string $event, mixed $data): void;
}

interface Subject
{
    public function attach(Observer $observer): void;
    public function detach(Observer $observer): void;
    public function notify(string $event, mixed $data): void;
}

trait Observable
{
    private array $observers = [];

    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function detach(Observer $observer): void
    {
        $this->observers = array_filter(
            $this->observers,
            fn($o) => $o !== $observer
        );
    }

    public function notify(string $event, mixed $data): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }
}
```

## Exemple d'Événement de Domaine

```php
<?php
class User implements Subject
{
    use Observable;

    public function __construct(
        public readonly int $id,
        private string $email
    ) {}

    public function changeEmail(string $newEmail): void
    {
        $oldEmail = $this->email;
        $this->email = $newEmail;

        $this->notify('email_changed', [
            'user_id' => $this->id,
            'old_email' => $oldEmail,
            'new_email' => $newEmail,
        ]);
    }
}

// Les observateurs
class EmailNotifier implements Observer
{
    public function update(string $event, mixed $data): void
    {
        if ($event === 'email_changed') {
            $this->sendConfirmationEmail($data['new_email']);
        }
    }
}

class AuditLogger implements Observer
{
    public function update(string $event, mixed $data): void
    {
        $this->log->info("Événement : $event", $data);
    }
}

class CacheInvalidator implements Observer
{
    public function update(string $event, mixed $data): void
    {
        if ($event === 'email_changed') {
            $this->cache->delete("user:{$data['user_id']}");
        }
    }
}

// Utilisation
$user = new User(1, 'ancien@example.com');
$user->attach(new EmailNotifier());
$user->attach(new AuditLogger());
$user->attach(new CacheInvalidator());

$user->changeEmail('nouveau@example.com');
// Tous les observateurs sont notifiés automatiquement
```

## Dispatcher d'Événements

```php
<?php
class EventDispatcher
{
    private array $listeners = [];

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $listener) {
            $listener($payload);

            // Arrêter la propagation si le listener retourne false
            if ($listener($payload) === false) {
                break;
            }
        }
    }
}

// Utilisation
$dispatcher = new EventDispatcher();

$dispatcher->listen('user.created', function($data) {
    echo "Envoi email de bienvenue à {$data['email']}\n";
});

$dispatcher->listen('user.created', function($data) {
    echo "Ajout à la newsletter : {$data['email']}\n";
});

$dispatcher->dispatch('user.created', [
    'id' => 1,
    'email' => 'user@example.com',
]);
```

## Implémentation avec SPL de PHP

```php
<?php
class UserRegistration extends SplSubject
{
    private SplObjectStorage $observers;
    private array $data;

    public function __construct()
    {
        $this->observers = new SplObjectStorage();
    }

    public function attach(SplObserver $observer): void
    {
        $this->observers->attach($observer);
    }

    public function detach(SplObserver $observer): void
    {
        $this->observers->detach($observer);
    }

    public function notify(): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($this);
        }
    }

    public function register(array $data): void
    {
        $this->data = $data;
        // Sauvegarder l'utilisateur...
        $this->notify();
    }

    public function getData(): array
    {
        return $this->data;
    }
}
```

## Les Grimoires

- [SPL SplObserver (Documentation Officielle)](https://www.php.net/manual/en/class.splobserver.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
