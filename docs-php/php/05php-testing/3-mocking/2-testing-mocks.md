---
source_course: "php-testing"
source_lesson: "php-testing-mocks"
---

# Les Mocks avec Attentes

Les mocks **vérifient que les méthodes sont appelées avec des arguments spécifiques**.

## Mock de Base

```php
<?php
public function testSendsWelcomeEmail(): void
{
    // Créer un mock avec des attentes
    $mailer = $this->createMock(Mailer::class);

    // Attendre que send() soit appelé exactement une fois
    $mailer->expects($this->once())
        ->method('send')
        ->with(
            $this->equalTo('jean@exemple.com'),
            $this->equalTo('Bienvenue !'),
            $this->stringContains('Merci de vous être inscrit')
        );

    $service = new UserService($mailer);
    $service->register('jean@exemple.com', 'motdepasse123');

    // Le mock vérifie automatiquement les attentes après le test
}
```

## Matchers d'Attentes

```php
<?php
$mock->expects($this->once())         // Exactement une fois
$mock->expects($this->never())        // Jamais appelé
$mock->expects($this->atLeastOnce())  // 1 fois ou plus
$mock->expects($this->exactly(3))     // Exactement 3 fois
$mock->expects($this->atMost(2))      // 0, 1 ou 2 fois
$mock->expects($this->any())          // N'importe quel nombre de fois
```

## Contraintes d'Arguments

```php
<?php
public function testWithConstraints(): void
{
    $mock = $this->createMock(Logger::class);

    $mock->expects($this->once())
        ->method('log')
        ->with(
            $this->identicalTo('error'),             // Comparaison stricte
            $this->stringContains('échec'),          // Contient sous-chaîne
            $this->arrayHasKey('timestamp'),         // Tableau a la clé
            $this->isInstanceOf(Exception::class),   // Vérification de type
            $this->greaterThan(0),                   // Comparaison numérique
            $this->matchesRegularExpression('/\d+/'), // Regex
            $this->callback(fn($arg) => $arg > 10)  // Callback personnalisé
        );
}
```

## Appels Consécutifs

```php
<?php
public function testConsecutiveCalls(): void
{
    $generator = $this->createMock(IdGenerator::class);

    $generator->expects($this->exactly(3))
        ->method('generate')
        ->willReturnOnConsecutiveCalls(1, 2, 3);

    $service = new ItemService($generator);
    $item1 = $service->create('Premier');   // Utilise l'ID 1
    $item2 = $service->create('Deuxième');  // Utilise l'ID 2
    $item3 = $service->create('Troisième'); // Utilise l'ID 3

    $this->assertEquals(1, $item1->id);
    $this->assertEquals(2, $item2->id);
    $this->assertEquals(3, $item3->id);
}
```

## Exemple Complet

**Mock complet avec services de paiement et notification**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

interface PaymentGateway {
    public function charge(string $customerId, float $amount): string;
    public function refund(string $transactionId): bool;
}

interface NotificationService {
    public function sendEmail(string $to, string $subject, string $body): void;
    public function sendSms(string $phone, string $message): void;
}

class OrderService {
    public function __construct(
        private PaymentGateway $payment,
        private NotificationService $notifications
    ) {}

    public function processOrder(Order $order): string {
        $transactionId = $this->payment->charge(
            $order->customerId,
            $order->total
        );

        $this->notifications->sendEmail(
            $order->customerEmail,
            'Commande Confirmée',
            "Votre commande #{$order->id} a été traitée."
        );

        return $transactionId;
    }
}

class OrderServiceTest extends TestCase {
    public function testProcessOrderChargesAndNotifies(): void {
        // Arrange : Créer les mocks
        $payment = $this->createMock(PaymentGateway::class);
        $notifications = $this->createMock(NotificationService::class);

        $order = new Order(
            id: 123,
            customerId: 'cust_456',
            customerEmail: 'jean@exemple.com',
            total: 99.99
        );

        // Définir les attentes sur le mock de paiement
        $payment->expects($this->once())
            ->method('charge')
            ->with(
                $this->equalTo('cust_456'),
                $this->equalTo(99.99)
            )
            ->willReturn('txn_789');

        // Définir les attentes sur le mock de notification
        $notifications->expects($this->once())
            ->method('sendEmail')
            ->with(
                $this->equalTo('jean@exemple.com'),
                $this->equalTo('Commande Confirmée'),
                $this->stringContains('123')
            );

        // Act
        $service = new OrderService($payment, $notifications);
        $result = $service->processOrder($order);

        // Assert
        $this->assertEquals('txn_789', $result);
    }

    public function testProcessOrderFailsOnPaymentError(): void {
        $payment = $this->createMock(PaymentGateway::class);
        $notifications = $this->createMock(NotificationService::class);

        $order = new Order(id: 123, customerId: 'cust_456', customerEmail: 'jean@exemple.com', total: 99.99);

        // Le paiement va échouer
        $payment->expects($this->once())
            ->method('charge')
            ->willThrowException(new PaymentException('Carte refusée'));

        // L'email NE doit PAS être envoyé
        $notifications->expects($this->never())
            ->method('sendEmail');

        $service = new OrderService($payment, $notifications);

        $this->expectException(PaymentException::class);
        $service->processOrder($order);
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
