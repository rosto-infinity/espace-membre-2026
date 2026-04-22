---
source_course: "php-testing"
source_lesson: "php-testing-api-testing"
---

# Tests d'API HTTP

**Testez vos endpoints API** pour vérifier la gestion des requêtes et des réponses.

## Test d'API Simple

```php
<?php
class ApiTestCase extends TestCase
{
    protected function request(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): array {
        // Simuler l'environnement de requête
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$key] = $value;
        }

        // Simuler le corps JSON
        $GLOBALS['test_input'] = json_encode($data);

        // Capturer la sortie
        ob_start();

        try {
            require 'public/index.php';
        } catch (SystemExit $e) {
            // Attraper l'appel exit()
        }

        $output = ob_get_clean();

        return [
            'status' => http_response_code(),
            'body' => json_decode($output, true),
            'raw' => $output,
        ];
    }

    protected function get(string $uri, array $headers = []): array {
        return $this->request('GET', $uri, [], $headers);
    }

    protected function post(string $uri, array $data, array $headers = []): array {
        return $this->request('POST', $uri, $data, $headers);
    }
}
```

## Tester les Endpoints API

```php
<?php
class UserApiTest extends ApiTestCase
{
    public function testListUsers(): void
    {
        $response = $this->get('/api/users');

        $this->assertEquals(200, $response['status']);
        $this->assertArrayHasKey('data', $response['body']);
        $this->assertIsArray($response['body']['data']);
    }

    public function testCreateUser(): void
    {
        $response = $this->post('/api/users', [
            'name' => 'Jean Dupont',
            'email' => 'jean@exemple.com',
        ]);

        $this->assertEquals(201, $response['status']);
        $this->assertEquals('Jean Dupont', $response['body']['data']['name']);
    }

    public function testValidationError(): void
    {
        $response = $this->post('/api/users', [
            'name' => 'Jean',
            // Email manquant
        ]);

        $this->assertEquals(422, $response['status']);
        $this->assertArrayHasKey('error', $response['body']);
        $this->assertArrayHasKey('details', $response['body']['error']);
        $this->assertArrayHasKey('email', $response['body']['error']['details']);
    }

    public function testAuthenticationRequired(): void
    {
        $response = $this->get('/api/profile');

        $this->assertEquals(401, $response['status']);
    }

    public function testAuthenticatedRequest(): void
    {
        $token = $this->getTestToken();

        $response = $this->get('/api/profile', [
            'Authorization' => "Bearer $token",
        ]);

        $this->assertEquals(200, $response['status']);
    }
}
```

## Exemple Complet

**Test d'intégration complet pour le workflow de commande**

```php
<?php
declare(strict_types=1);

class OrderWorkflowTest extends TestCase
{
    private PDO $pdo;
    private OrderService $orderService;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->createSchema();
        $this->seedData();

        $productRepo = new ProductRepository($this->pdo);
        $orderRepo = new OrderRepository($this->pdo);

        // Mocker seulement les services externes
        $paymentGateway = $this->createMock(PaymentGateway::class);
        $paymentGateway->method('charge')->willReturn('txn_123');

        $this->inventoryService = new InventoryService($this->pdo);
        $this->paymentService = new PaymentService($paymentGateway);
        $this->orderService = new OrderService(
            $orderRepo,
            $productRepo,
            $this->inventoryService,
            $this->paymentService
        );
    }

    public function testCompleteOrderWorkflow(): void
    {
        $order = $this->orderService->createOrder([
            ['product_id' => 1, 'quantity' => 2],  // 2 Widgets = 20€
            ['product_id' => 2, 'quantity' => 1],  // 1 Gadget = 25€
        ]);

        $this->assertEquals('pending', $order->status);
        $this->assertEquals(45.00, $order->total);

        // Traiter le paiement
        $this->orderService->processPayment($order->id);

        $order = $this->orderService->find($order->id);
        $this->assertEquals('paid', $order->status);
    }

    public function testCannotOrderOutOfStockProduct(): void
    {
        $this->expectException(OutOfStockException::class);

        $this->orderService->createOrder([
            ['product_id' => 3, 'quantity' => 1],  // Stock = 0
        ]);
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
