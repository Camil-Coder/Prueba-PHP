<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * ============================================================================
 *  Configuración base de la app
 * ============================================================================
 */

const DB_PATH = __DIR__ . '/database.sqlite'; // Ruta absoluta a la base SQLite

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true); // En prod: (false, false, false)

/**
 * ============================================================================
 *  Helpers / Utilidades
 * ============================================================================
 */

/**
 * Escape seguro para HTML.
 * Pequeño atajo para no repetir htmlspecialchars por toda la app.
 */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Retorna una conexión PDO a SQLite e inicializa el esquema la primera vez.
 * - Activa modo excepciones
 * - Activa claves foráneas y WAL para mejor concurrencia
 */
function pdo(): PDO
{
    $firstTime = !file_exists(DB_PATH) || filesize(DB_PATH) === 0;

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // PRAGMAs útiles para SQLite en apps web sencillas
    $pdo->exec('PRAGMA foreign_keys = ON;');
    $pdo->exec('PRAGMA journal_mode = WAL;');

    if ($firstTime) {
        $schemaFile = __DIR__ . '/schema.sql';
        if (!is_file($schemaFile)) {
            throw new RuntimeException('schema.sql no encontrado.'); // sin drama, pero claro.
        }
        $schema = file_get_contents($schemaFile) ?: '';
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }
    }

    return $pdo;
}

/**
 * Devuelve 'class="active"' si la ruta actual empieza por $path.
 * Útil para resaltar el ítem de navegación.
 */
function active(string $path): string
{
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
    return str_starts_with($uri, $path) ? 'class="active"' : '';
}

/**
 * Formatea números como dinero sin decimales (ej: 12.345.678)
 */
function money(float $n): string
{
    return number_format($n, 0, ',', '.');
}

/**
 * Renderiza una tabla HTML a partir de un array de filas asociativas.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function tableHtml(array $rows): string
{
    if (!$rows) {
        return '<div class="pad">Sin datos.</div>';
    }

    $cols = array_keys($rows[0]);
    ob_start(); ?>
    <table>
      <thead>
        <tr>
          <?php foreach ($cols as $c): ?>
            <th><?= e((string)$c) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <?php foreach ($cols as $c):
                $v = $r[$c] ?? null;
                $isMoney = in_array($c, ['Total', 'TotalVentas', 'TotalComprado'], true);
                $cls = $isMoney ? ' class="money"' : '';
            ?>
              <td<?= $cls ?>>
                <?= $isMoney ? money((float)$v) : e((string)$v) ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    return (string)ob_get_clean();
}

/**
 * Plantilla base (layout) para páginas HTML.
 */
function layout(string $title, string $bodyHtml, ?string $subtitle = null): string
{
    ob_start(); ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <title><?= e($title) ?></title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <style>
        :root{
          --brand:#2563eb; --accent:#7c3aed;
          --bg:#f6f8fb; --card:#ffffff; --text:#0f172a;
          --muted:#6b7280; --border:#e5e7eb; --hover:#eff6ff;
          --shadow:0 8px 30px rgba(2,6,23,.08);
        }
        @media (prefers-color-scheme: dark){
          :root{
            --bg:#0b1220; --card:#0f172a; --text:#e5e7eb;
            --muted:#94a3b8; --border:#1f2937; --hover:#111827;
            --shadow:0 8px 24px rgba(0,0,0,.5);
          }
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:var(--bg);color:var(--text)}
        .topbar{position:sticky;top:0;z-index:10;background:linear-gradient(90deg,var(--brand),var(--accent));color:#fff; box-shadow:var(--shadow)}
        .topbar .wrap{max-width:1100px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;gap:18px}
        .brand{font-weight:800;letter-spacing:.3px}
        nav a{color:#ffffffcc;text-decoration:none;margin-right:10px;padding:8px 12px;border-radius:12px;transition:.2s}
        nav a:hover{background:#ffffff1a;color:#fff}
        nav a.active{background:#ffffff33;color:#fff}
        .container{max-width:1100px;margin:28px auto;padding:0 20px}
        .card{background:var(--card);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);overflow:hidden;margin-bottom:22px}
        .head{padding:18px 20px;border-bottom:1px solid var(--border)}
        h1{font-size:1.25rem;margin:0}
        .subtitle{color:var(--muted);font-size:.95rem;margin-top:6px}
        .pad{padding:16px}
        .table-wrap{overflow-x:auto}
        table{border-collapse:separate;border-spacing:0;width:100%}
        th,td{padding:12px 14px;border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
        thead th{background:var(--hover);font-weight:600;position:sticky;top:0}
        tbody tr:hover{background:var(--hover)}
        td.money{text-align:right;font-variant-numeric:tabular-nums}
        /* Footer personal */
        .brand-footer{margin-top:28px;background:#0b1220;color:#e5e7eb;border-top:1px solid #111827;box-shadow:var(--shadow)}
        .brand-footer .wrap{max-width:1100px;margin:0 auto;padding:20px;text-align:center}
        .brand-footer .line1{font-weight:700}
        .brand-footer .line2{font-weight:900;margin-top:6px;letter-spacing:.3px}
        .brand-footer .line3{color:#94a3b8;margin-top:2px}
        .brand-footer .links{margin-top:10px}
        .brand-footer a{color:#93c5fd;text-decoration:none;font-weight:700;margin:0 8px}
        .brand-footer a:hover{text-decoration:underline}
        .sep{color:#64748b;margin:0 2px}
      </style>
    </head>
    <body>
      <header class="topbar">
        <div class="wrap">
          <div class="brand">Reportes Prueba Cari</div>
          <nav>
            <a <?= active('/tablas') ?> href="/tablas">Tablas</a>
            <a <?= active('/reporte') ?> href="/reporte">Ventas por producto</a>
            <a <?= active('/reporte-televisores') ?> href="/reporte-televisores">Televisores</a>
            <a <?= active('/reporte-clientes-10m') ?> href="/reporte-clientes-10m">Clientes &gt; 10M</a>
          </nav>
        </div>
      </header>

      <main class="container">
        <section class="card">
          <div class="head">
            <h1><?= e($title) ?></h1>
            <?php if ($subtitle): ?>
              <div class="subtitle"><?= e($subtitle) ?></div>
            <?php endif; ?>
          </div>
          <div class="table-wrap"><?= $bodyHtml ?></div>
        </section>
      </main>

      <footer class="brand-footer">
        <div class="wrap">
          <div class="line1">💡 <strong>Cámil Code</strong> — Soluciones digitales con propósito</div>
          <div class="line2">CRISTIAN CAMILO CASTILLO RODRIGUEZ</div>
          <div class="line3">Desarrollador de Software Full Stack — En formación constante</div>
          <div class="links">
            <a href="https://www.linkedin.com/in/TU_USUARIO" target="_blank" rel="noopener">LinkedIn</a><span class="sep">•</span>
            <a href="https://github.com/TU_USUARIO" target="_blank" rel="noopener">GitHub</a><span class="sep">•</span>
            <a href="mailto:TU_CORREO@dominio.com">Correo</a><span class="sep">•</span>
            <a href="tel:+573143846187">314 384 6187</a>
          </div>
        </div>
      </footer>
    </body>
    </html>
    <?php
    return (string)ob_get_clean();
}

/**
 * ============================================================================
 *  Rutas
 * ============================================================================
 */

// Home → redirección a /tablas
$app->get('/', function (Request $req, Response $res): Response {
    return $res->withHeader('Location', '/tablas')->withStatus(302);
});

/**
 * Tablas (todas): Client, Product, Orders
 */
$app->get('/tablas', function (Request $req, Response $res): Response {
    $pdo = pdo();

    $client  = $pdo->query("SELECT * FROM Client")->fetchAll();
    $product = $pdo->query("SELECT * FROM Product")->fetchAll();
    $orders  = $pdo->query("SELECT * FROM Orders")->fetchAll();

    $html  = "<div class='pad'><strong>Client</strong></div>" . tableHtml($client);
    $html .= "<div class='pad'><a href='/tabla/Client'>Ver solo Client</a></div>";
    $html .= "<div class='pad'><strong>Product</strong></div>" . tableHtml($product);
    $html .= "<div class='pad'><a href='/tabla/Product'>Ver solo Product</a></div>";
    $html .= "<div class='pad'><strong>Orders</strong></div>" . tableHtml($orders);
    $html .= "<div class='pad'><a href='/tabla/Orders'>Ver solo Orders</a></div>";

    $res->getBody()->write(layout('Tablas base', $html, 'Visualiza Client, Product y Orders.'));
    return $res;
});

/**
 * Tabla individual (whitelist para evitar inyecciones)
 */
$app->get('/tabla/{name}', function (Request $req, Response $res, array $args): Response {
    $table = $args['name'] ?? '';
    $allowed = ['Client', 'Product', 'Orders']; // si agregas tablas, añádelas aquí

    if (!in_array($table, $allowed, true)) {
        $res->getBody()->write(layout('Tabla no permitida', '<div class="pad">Tabla inválida.</div>'));
        return $res->withStatus(400);
    }

    // Nota: aquí no usamos parámetros porque el nombre viene validado por whitelist
    $rows = pdo()->query("SELECT * FROM $table")->fetchAll();
    $res->getBody()->write(layout("Tabla: $table", tableHtml($rows)));
    return $res;
});

/**
 * Reporte: ventas por producto (TOTAL DESC)
 */
$app->get('/reporte', function (Request $req, Response $res): Response {
    $sql = "
      SELECT p.Name AS Producto, p.Reference,
             SUM(o.Quantity) AS Cantidad, SUM(o.Total) AS Total
      FROM Orders o
      JOIN Product p ON o.ProductId = p.ProductId
      GROUP BY p.ProductId, p.Name, p.Reference
      ORDER BY Total DESC
    ";
    $rows = pdo()->query($sql)->fetchAll();

    $res->getBody()->write(
        layout('Reporte total de ventas por producto', tableHtml($rows), 'Ordenado de mayor a menor por Total.')
    );
    return $res;
});

/**
 * Reporte: compras de televisores por cliente
 */
$app->get('/reporte-televisores', function (Request $req, Response $res): Response {
    $sql = "
      SELECT p.Name AS Producto, c.Name AS Cliente, o.Quantity AS Cantidad, o.Total
      FROM Orders o
      JOIN Client c  ON o.ClientId  = c.ClientId
      JOIN Product p ON o.ProductId = p.ProductId
      WHERE p.Name = 'Televisor'
      ORDER BY o.OrderId
    ";
    $rows = pdo()->query($sql)->fetchAll();

    $res->getBody()->write(
        layout('Compras de televisores', tableHtml($rows), 'Detalle por cliente del producto Televisor.')
    );
    return $res;
});

/**
 * Reporte: clientes con compras > 10M
 */
$app->get('/reporte-clientes-10m', function (Request $req, Response $res): Response {
    $sql = "
      SELECT c.ClientId, c.Name, c.LastName, SUM(o.Total) AS TotalComprado
      FROM Orders o
      JOIN Client c ON o.ClientId = c.ClientId
      GROUP BY c.ClientId, c.Name, c.LastName
      HAVING SUM(o.Total) > 10000000
      ORDER BY TotalComprado DESC
    ";
    $rows = pdo()->query($sql)->fetchAll();

    $res->getBody()->write(
        layout('Clientes con compras mayores a 10 millones', tableHtml($rows))
    );
    return $res;
});

$app->run();
