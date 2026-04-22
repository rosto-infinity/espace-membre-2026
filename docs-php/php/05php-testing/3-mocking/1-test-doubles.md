---
source_course: "php-testing"
source_lesson: "php-testing-test-doubles"
---

# Comprendre les Doublures de Test (Test Doubles)

Les doublures de test **remplacent les vraies dépendances dans les tests** pour isoler le code sous test.

## Types de Doublures de Test

| Type  | But                                  | Vérifie les Appels ? |
| ----- | ------------------------------------ | -------------------- |
| Dummy | Remplit un paramètre, jamais utilisé | Non                  |
| Stub  | Retourne des valeurs prédéfinies     | Non                  |
| Spy   | Enregistre les interactions          | Après le test        |
| Mock  | Attentes pré-programmées             | Oui, pendant le test |
| Fake  | Implémentation fonctionnelle         | Non                  |

## Créer un Stub

```php
<?php
class OrderServiceTest extends TestCase
{
    public function testCalculateTotal(): void
    {
        // Créer un stub
        $productRepository = $this->createStub(ProductRepository::class);

        // Configurer la valeur de retour
        $productRepository->method('findById')
            ->willReturn(new Product(id: 1, name: 'Widget', price: 10.00));

        $service = new OrderService($productRepository);
        $total = $service->calculateTotal([1, 1, 1]);  // 3 widgets

        $this->assertEquals(30.00, $total);
    }
}
```

## Stub avec Retours Multiples

```php
<?php
public function testMultipleProducts(): void
{
    $repo = $this->createStub(ProductRepository::class);

    // Retourner différentes valeurs selon les arguments
    $repo->method('findById')
        ->willReturnMap([
            [1, new Product(1, 'Widget', 10.00)],
            [2, new Product(2, 'Gadget', 20.00)],
            [3, null],  // Introuvable
        ]);

    $service = new OrderService($repo);

    $this->assertEquals(30.00, $service->calculateTotal([1, 2]));
}
```

## Stub Retournant un Callback

```php
<?php
public function testDynamicReturn(): void
{
    $repo = $this->createStub(ProductRepository::class);

    $repo->method('findById')
        ->willReturnCallback(function (int $id) {
            return new Product($id, "Produit $id", $id * 10.0);
        });

    $service = new OrderService($repo);
    $total = $service->calculateTotal([1, 2, 3]);

    // 10 + 20 + 30 = 60
    $this->assertEquals(60.00, $total);
}
```

## Stub Lançant des Exceptions

```php
<?php
public function testHandlesNotFound(): void
{
    $repo = $this->createStub(ProductRepository::class);

    $repo->method('findById')
        ->willThrowException(new ProductNotFoundException());

    $service = new OrderService($repo);

    $this->expectException(OrderException::class);
    $service->calculateTotal([999]);
}
```

## Les Grimoires

- [Test Doubles (Documentation PHPUnit)](https://docs.phpunit.de/en/11.4/test-doubles.html)

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
