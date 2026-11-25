# Instalación, demostración e integración de InvenTech Solutions

El panel administrativo incluye Inicio, Usuarios, Categorías, Productos, Inventario, Pedidos, Proformas, Reportes y Bitácora. El registro público, el inicio de sesión y el cierre de sesión permiten probar los roles Administrador y Cliente. El catálogo público, el carrito y el checkout corresponden a la siguiente integración del equipo.

## Instalar en XAMPP

1. Coloca el proyecto en `htdocs/InventechSolutions`. Inicia Apache y MySQL. Conserva el nombre de la carpeta local o ajusta `BASE_URL` en `config/config.php`.
2. En phpMyAdmin crea una base vacía con `utf8mb4`, selecciónala e importa `database/setup.sql`. El script crea nueve tablas y once claves foráneas. Los scripts individuales de `database/` sirven para actualizar instalaciones anteriores.
3. Configura el host, el nombre de la base y la cuenta de conexión en `config/db.php`. Usa una cuenta local con permisos sobre esa base. Importa el DDL con una cuenta de administración de MariaDB. Mantén las credenciales reales y los respaldos fuera de los archivos compartidos.
4. Habilita PDO MySQL, `mbstring`, `fileinfo` y sesiones PHP. Comprueba que Apache pueda escribir en el directorio de sesiones y en `assets/img/productos`. Bootstrap 5.3.3 y Font Awesome se cargan desde los recursos externos que ya usa el proyecto.

La instalación limpia se verificó en PHP 8.2.12 y MariaDB 10.4.32. El instalador crea la estructura vacía y no requiere archivos temporales.

### Primera cuenta administradora

1. Abre [Registro local](http://localhost/InventechSolutions/auth/registro.php) y crea una cuenta con correo propio y contraseña única. La aplicación guarda un hash y asigna `Cliente` y `Habilitado`.
2. Desde phpMyAdmin local, entra a la base que acabas de instalar, localiza esa cuenta exacta en `users` y cambia únicamente su `role` a `Administrador`. Conserva `loginStatus = Habilitado`.
3. Inicia sesión y verifica que abra el [dashboard](http://localhost/InventechSolutions/admin/index.php). Mantén Apache y phpMyAdmin limitados al entorno local durante la instalación.

## Base de demostración

1. Crea **`tienda_inventech_demo`** vacía en phpMyAdmin e importa `database/setup.sql` ahí.
2. Desde la raíz del proyecto ejecuta `C:\xampp\php\php.exe database/demo.php`. El script solo puede ejecutarse por CLI, apunta exclusivamente a `tienda_inventech_demo` y se detiene si encuentra datos. Si tu instalación usa una cuenta de base distinta, configura `INVENTECH_DEMO_DB_USER` y `INVENTECH_DEMO_DB_PASSWORD` solo en tu terminal local.
3. Para la exposición, cambia temporalmente `$bd` en `config/db.php` a `tienda_inventech_demo` o usa una copia del proyecto con esa conexión. Cierra sesión antes de cambiar de base y vuelve a entrar con una cuenta de demostración. Al terminar, cierra sesión y restaura `$bd = "tienda_inventech_v2"` en la instalación principal.
4. Comparte `database/demo.php` y las imágenes originales. El instalador genera copias con prefijo `demo_` para que editar un producto en la demostración no borre una imagen original. Puedes omitir esas copias al compartir el proyecto, porque se regeneran al instalar en una base demo vacía.

### Credenciales exclusivamente de demostración

| Cuenta | Contraseña | Uso |
|---|---|---|
| `admin@inventech.test` | `DemoAdmin!2026` | Administrador habilitado |
| `operaciones@inventech.test` | `DemoOperaciones!2026` | Segundo administrador habilitado |
| `cliente@inventech.test` | `DemoCliente!2026` | Cliente habilitado |
| `inactivo@inventech.test` | `DemoInactivo!2026` | Cliente deshabilitado |

Son cuentas ficticias para un entorno local. La base incluye cuatro categorías, ocho productos, ocho pedidos, cuatro proformas, movimientos de stock y actividades administrativas. Las fechas se calculan a partir del día de instalación para que el dashboard y los filtros tengan ejemplos. La tasa de las proformas del instalador es un dato de demostración.

## Autenticación y seguridad

- El registro y el login validan el formulario en el servidor y usan tokens CSRF. El registro público asigna `Cliente` desde el servidor.
- El Administrador entra directamente a Inicio administrativo. El Cliente entra a la portada. La integración pública puede añadir destinos internos a una lista explícita cuando existan las rutas correspondientes.
- Las páginas protegidas consultan la cuenta actual. Los cambios administrativos de cuenta registrados en Bitácora invalidan sesiones anteriores, incluso si se restablece el rol antes del siguiente acceso.
- El cierre de sesión se envía por POST con CSRF, destruye la sesión y elimina la cookie. La cookie es HttpOnly y SameSite Lax, y usa Secure cuando Apache indica HTTPS. Si se usa un proxy HTTPS, configúralo para que PHP reciba correctamente esa indicación.
- Antes de publicar, considera limitar intentos de login por cuenta y origen. El cambio, la validación del despliegue HTTPS y la autorización de descargas públicas del documento de proforma quedan como tareas de integración.

## Integrar la tienda pública

| Flujo | Punto de integración |
|---|---|
| Catálogo | Consulta productos activos y categorías de la base común. Los enlaces públicos y el catálogo se conectarán desde el equipo responsable. |
| Carrito y checkout | Define la sesión del carrito y la autorización del cliente. Consulta productos, precios y existencias en el servidor. Un carrito o pedido pendiente no reserva stock. |
| Pedidos | Reutiliza `registrarPedido()` con el cliente validado en el servidor. Guarda encabezado y detalle en la misma transacción y conserva los importes históricos. `cambiarEstadoPedido()` valida las transiciones y coordina los movimientos. |
| Inventario | Usa `aplicarMovimientoInventario()` desde la misma transacción que confirma o cancela el pedido. La reposición solo corresponde a salidas registradas. Evita escribir `productos.Cantidad` desde el catálogo o el carrito. |
| Proformas | Reutiliza `registrarProforma()` con o sin usuario asociado. Acuerda en el equipo la política pública de tasa y vigencia y aplícala en el servidor. La entrega del documento requiere un mecanismo público de acceso propio. |
| Reportes y Bitácora | Reportes es de solo lectura. Bitácora registra actividades administrativas con el actor de la sesión validada, dentro de la transacción de la operación. |

## Pruebas y archivos para la entrega

- Entrega el código de `admin/`, `auth/`, `config/`, `controllers/`, `middlewares/`, `includes/`, `pages/`, `css/`, `js/`, los recursos originales de `assets/`, los scripts de `database/`, `index.php`, el favicon y esta guía. El catálogo administrativo, los CRUD existentes, la navegación y los estilos compartidos siguen en sus archivos originales.
- Excluye respaldos con registros reales, sesiones PHP, registros de errores, archivos temporales de navegador, imágenes generadas para pruebas y cualquier configuración que contenga credenciales reales. El instalador de demo contiene únicamente cuentas ficticias.
- Las pruebas de escritura se realizaron en bases separadas. Incluyeron registro, roles, CSRF, reenvíos, revocación, cierre, protección del administrador, imagen de producto, stock, pago, cancelación, proforma, Reportes y Bitácora. Verifica los mismos recorridos en el XAMPP de cada compañero antes de la exposición.
