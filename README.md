# Reporte en PHP con Slim 4 (SQLite)

**Framework:** Slim 4 (PHP)  
**Base de datos:** SQLite (autogenerada desde `schema.sql`)  
**Descripción:** Aplicación para visualizar tablas base y los reportes solicitados con estilos modernos (modo claro/oscuro, barra superior, tarjetas y tablas con hover) y footer personalizable.

---

## 📸 Captura / Demo

![Vista del reporte](./capturas/reporte.png)

![Vista del reporte (link)](https://TU-LINK-A-LA-IMAGEN)

---

## 🎯 Objetivo

Entregar un reporte en PHP usando un framework con instrucciones claras para visualizarlo.

La app permite:
- Ver las tablas base **Client**, **Product** y **Orders** (todas e individualmente).
- Ver el **reporte de ventas por producto** (Producto, Referencia, Cantidad, Total).
- Ver **compras de Televisores por cliente**.
- Ver **clientes con compras > 10.000.000**.

---

## ✅ Archivos incluidos (5)

1. `index.php` – Aplicación Slim (rutas, vistas y estilos).
2. `composer.json` – Dependencias del proyecto.
3. `composer.lock` – Bloqueo de versiones de Composer.
4. `schema.sql` – Esquema y datos de ejemplo para SQLite.
5. `README.md` – Instrucciones.

---

## 🧰 Requisitos

- Windows 11 (probado; también funciona en macOS/Linux).
- PHP ≥ 8.0 (CLI).
- Composer instalado.
- Extensión `pdo_sqlite` habilitada en PHP.

### Verificación rápida (PowerShell)

powershell
php -v
php -m | findstr /i sqlite
composer -V


#### Ejecutar (PowerShell)
ejecutar el progrma
php -S localhost:8080 index.php