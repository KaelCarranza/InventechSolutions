<?php
if (PHP_SAPI !== "cli") {
    http_response_code(404);
    exit();
}

require_once __DIR__ . "/../controllers/bitacora/registrarActividad.php";
require_once __DIR__ . "/../controllers/inventario/movimientos.php";
require_once __DIR__ . "/../controllers/pedidos/registrarPedido.php";
require_once __DIR__ . "/../controllers/pedidos/cambiarEstadoPedido.php";
require_once __DIR__ . "/../controllers/proformas/registrarProforma.php";

$archivosCreados = [];
try {
    $conexion = new PDO(
        "mysql:host=localhost;dbname=tienda_inventech_demo;charset=utf8mb4",
        getenv("INVENTECH_DEMO_DB_USER") ?: "root",
        getenv("INVENTECH_DEMO_DB_PASSWORD") ?: "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $tablas = ["users", "categorias", "productos", "pedidos", "detallepedido",
        "movimientosinventario", "proformas", "detalleproforma", "bitacora"];
    foreach ($tablas as $tabla) {
        if ((int) $conexion->query("SELECT COUNT(*) FROM $tabla")->fetchColumn() !== 0) {
            throw new RuntimeException("La instalación de demostración debe estar vacía. No se sustituyeron datos.");
        }
    }

    $conexion->beginTransaction();
    $hoy = new DateTimeImmutable($conexion->query("SELECT CURRENT_DATE()")->fetchColumn());
    $fechaInicial = $hoy->modify("-50 days")->format("Y-m-d") . " 08:00:00";
    $primeroMes = $hoy->modify("first day of this month");
    $ayer = $hoy->modify("-1 day")->format("Y-m-d");
    $inicioMes = $primeroMes->format("Y-m-d");
    $mesAnterior = $primeroMes->modify("-12 days")->format("Y-m-d");
    $fechaHoy = $hoy->format("Y-m-d");
    $fechaProxima = $hoy->modify("+12 days")->format("Y-m-d");

    $fecharActividad = $conexion->prepare("UPDATE bitacora SET fechaRegistro = ? WHERE idBitacora = ?");
    $actividad = static function (string $modulo, string $accion, int $id, string $descripcion, string $fecha)
        use ($conexion, $fecharActividad): void {
        registrarActividad($conexion, $modulo, $accion, $id, $descripcion);
        $fecharActividad->execute([$fecha, $conexion->lastInsertId()]);
    };

    $guardar = $conexion->prepare("INSERT INTO users(email,password,role,loginStatus,registerDate) VALUES (?,?,?,?,?)");
    $cuentas = [
        ["admin@inventech.test", "DemoAdmin!2026", "Administrador", "Habilitado"],
        ["operaciones@inventech.test", "DemoOperaciones!2026", "Administrador", "Habilitado"],
        ["cliente@inventech.test", "DemoCliente!2026", "Cliente", "Habilitado"],
        ["sofia@inventech.test", "DemoSofia!2026", "Cliente", "Habilitado"],
        ["diego@inventech.test", "DemoDiego!2026", "Cliente", "Habilitado"],
        ["inactivo@inventech.test", "DemoInactivo!2026", "Cliente", "Deshabilitado"]
    ];
    $usuarios = [];
    foreach ($cuentas as [$email, $password, $rol, $estado]) {
        $guardar->execute([$email, password_hash($password, PASSWORD_DEFAULT), $rol, $estado, $fechaInicial]);
        $id = (int) $conexion->lastInsertId();
        if (!$usuarios) {
            $_SESSION["userId"] = $id;
        } else {
            $actividad("Usuarios", "Registro", $id, "Cuenta registrada en la demostración.", $fechaInicial);
        }
        $usuarios[] = $id;
    }

    $categorias = [];
    $guardar = $conexion->prepare("INSERT INTO categorias(Nombre,Descripcion) VALUES (?,?)");
    foreach ([
        ["Procesadores", "Procesadores para equipos de escritorio."],
        ["Memoria y almacenamiento", "Memorias y unidades de estado sólido."],
        ["Tarjetas y placas", "Placas base y tarjetas gráficas."],
        ["Periféricos", "Accesorios para oficina y juego."]
    ] as [$nombre, $descripcion]) {
        $guardar->execute([$nombre, $descripcion]);
        $id = (int) $conexion->lastInsertId();
        $categorias[] = $id;
        $actividad("Categorías", "Registro", $id, "Categoría registrada.", $fechaInicial);
    }

    $catalogo = [
        [0, "CPU-R7-8000", "AMD Ryzen 7 Serie 8000", "139900.00", "Procesador de escritorio con gráficos Radeon integrados.", "06d05e95e247f1fddc40f66de33b341b.jpg", 14, 1],
        [0, "CPU-CORE-U7", "Intel Core Ultra 7", "189900.00", "Procesador desbloqueado para equipos de escritorio.", "3d2d11dd958bba410dadc4e925f4dcfb.png", 5, 1],
        [1, "RAM-FURY-DDR5", "Kingston FURY Impact DDR5", "54900.00", "Memoria DDR5 para equipos compatibles con SO-DIMM.", "c893d975cc602ec44d0d95cde5703fb6.webp", 20, 1],
        [2, "MB-MSI-B550M", "MSI B550M PRO-VDH WIFI", "69900.00", "Placa base micro-ATX con Wi-Fi.", "e66b928a40ea56fae0ebf820430993e5.png", 4, 1],
        [2, "GPU-RTX4060", "Gigabyte GeForce RTX 4060 Eagle", "169900.00", "Tarjeta gráfica con memoria de 8 GB.", "fcc5581b8d176c8c0cd8afc4f113336c.jpg", 7, 1],
        [1, "SSD-NVME-1TB", "SSD NVMe de 1 TB", "39900.00", "Unidad M.2 para almacenamiento de escritorio o portátil.", null, 0, 1],
        [3, "ACC-TECLADO-USB", "Teclado compacto USB", "15900.00", "Teclado compacto de un lote descontinuado.", null, 2, 0],
        [3, "ACC-HUB-USBC", "Hub USB-C de 4 puertos", "19900.00", "Adaptador compacto para conectar periféricos.", null, 1, 1]
    ];
    $productos = [];
    $guardar = $conexion->prepare("
        INSERT INTO productos(idCategoria,SKU,Producto,Precio,Descripcion,Imagen,Activo,fechaRegistro)
        VALUES (?,?,?,?,?,?,?,?)
    ");
    $fecharMovimiento = $conexion->prepare("UPDATE movimientosinventario SET fechaMovimiento = ? WHERE idMovimiento = ?");
    foreach ($catalogo as [$categoria, $sku, $nombre, $precio, $descripcion, $imagen, $stock, $activo]) {
        if ($imagen !== null) {
            $directorio = __DIR__ . "/../assets/img/productos/";
            $copia = "demo_" . $imagen;
            if (!is_file($directorio . $copia)) {
                if (!copy($directorio . $imagen, $directorio . $copia)) {
                    throw new RuntimeException("No se pudo preparar una foto de demostración.");
                }
                $archivosCreados[] = $directorio . $copia;
            }
            $imagen = $copia;
        }
        $guardar->execute([$categorias[$categoria], $sku, $nombre, $precio, $descripcion, $imagen, $activo, $fechaInicial]);
        $id = (int) $conexion->lastInsertId();
        $productos[] = $id;
        $actividad("Productos", "Registro", $id, "Producto registrado.", $fechaInicial);
        if ($stock > 0) {
            $movimiento = aplicarMovimientoInventario(
                $conexion, $id, $usuarios[0], "Entrada", $stock, "Inventario inicial de demostración.", bin2hex(random_bytes(32))
            );
            $fecharMovimiento->execute([$fechaInicial, $movimiento]);
            $actividad("Inventario", "Entrada", $movimiento, "Entrada manual de existencias.", $fechaInicial);
        }
    }

    $movimiento = aplicarMovimientoInventario(
        $conexion, $productos[3], $usuarios[1], "Salida", 1, "Ajuste por revisión de inventario.", bin2hex(random_bytes(32))
    );
    $fechaRevision = $hoy->modify("-30 days")->format("Y-m-d") . " 11:00:00";
    $fecharMovimiento->execute([$fechaRevision, $movimiento]);
    $_SESSION["userId"] = $usuarios[1];
    $actividad("Inventario", "Salida", $movimiento, "Salida manual de existencias.", $fechaRevision);

    $transiciones = ["Pendiente" => ["Pagado", "Cancelado"], "Pagado" => ["Enviado", "Cancelado"], "Enviado" => ["Entregado"]];
    $fecharPedido = $conexion->prepare("UPDATE pedidos SET fechaPedido = ? WHERE idPedido = ?");
    $fecharPedidoMov = $conexion->prepare("
        UPDATE movimientosinventario SET fechaMovimiento = ? WHERE idPedido = ? AND Tipo = ?
    ");
    $escenarios = [
        [2, [2, 0], [2, 1], ["Pagado", "Enviado", "Entregado"], $mesAnterior, "Tarjeta"],
        [3, [4], [1], ["Pagado", "Enviado"], $inicioMes, "Efectivo"],
        [4, [1, 2], [1, 1], ["Pagado"], $fechaHoy, "Tarjeta"],
        [2, [0], [2], ["Pagado", "Cancelado"], $fechaHoy, "Efectivo"],
        [3, [5, 2], [1, 2], [], $fechaHoy, "Tarjeta"],
        [4, [3], [1], ["Cancelado"], $ayer, "Efectivo"],
        [3, [7], [1], ["Pagado", "Enviado", "Entregado"], $ayer, "Tarjeta"],
        [2, [2], [2], ["Pagado"], $fechaHoy, "Efectivo"]
    ];
    foreach ($escenarios as [$cliente, $indices, $cantidades, $estados, $dia, $metodo]) {
        $id = registrarPedido($conexion, [
            "userId" => $usuarios[$cliente],
            "idProducto" => array_map(fn($indice) => $productos[$indice], $indices),
            "cantidad" => $cantidades,
            "metodoPago" => $metodo
        ], ["Efectivo", "Tarjeta"]);
        $fecharPedido->execute([$dia . " 10:00:00", $id]);
        $actividad("Pedidos", "Registro", $id, "Pedido registrado como Pendiente.", $dia . " 10:00:00");
        foreach ($estados as $i => $nuevo) {
            $anterior = cambiarEstadoPedido($conexion, $id, $usuarios[1], $nuevo, $transiciones);
            $fecha = $dia . " 10:0" . ($i + 1) . ":00";
            if ($nuevo === "Pagado") {
                $fecharPedidoMov->execute([$fecha, $id, "Salida"]);
            } elseif ($nuevo === "Cancelado" && $anterior === "Pagado") {
                $fecharPedidoMov->execute([$fecha, $id, "Entrada"]);
            }
            $actividad("Pedidos", $nuevo === "Cancelado" ? "Cancelación" : "Cambio de estado", $id,
                "Estado del pedido: $anterior → $nuevo.", $fecha);
        }
    }

    $_SESSION["userId"] = $usuarios[0];
    $guardarFechas = $conexion->prepare("UPDATE proformas SET fechaEmision = ?, fechaVencimiento = ? WHERE idProforma = ?");
    $cancelar = $conexion->prepare("UPDATE proformas SET Estado = 'Cancelada' WHERE idProforma = ?");
    foreach ([
        ["Valeria Mora", "valeria@inventech.test", "", "Vigente"],
        ["Sofía Castro", "sofia@inventech.test", $usuarios[3], "Vigente"],
        ["Diego Salas", "diego@inventech.test", $usuarios[4], "Vencida"],
        ["Gabriela Solís", "gabriela@inventech.test", "", "Cancelada"]
    ] as [$nombre, $email, $cliente, $estado]) {
        $id = registrarProforma($conexion, [
            "nombre" => $nombre, "correo" => $email, "userId" => $cliente, "fechaVencimiento" => $fechaProxima,
            "tasaImpuesto" => "13", "idProducto" => [$productos[0], $productos[2]], "cantidad" => [1, 2]
        ], bin2hex(random_bytes(32)));
        $fecha = $fechaHoy . " 14:00:00";
        if ($estado === "Vencida") {
            $fecha = $hoy->modify("-15 days")->format("Y-m-d") . " 14:00:00";
            $guardarFechas->execute([$fecha, $ayer, $id]);
        }
        $actividad("Proformas", "Registro", $id, "Proforma registrada.", $fecha);
        if ($estado === "Cancelada") {
            $cancelar->execute([$id]);
            $actividad("Proformas", "Cancelación", $id, "Proforma cancelada.", $fechaHoy . " 14:05:00");
        }
    }

    $conexion->commit();
    echo "Demostración instalada en tienda_inventech_demo.", PHP_EOL;
} catch (Throwable $e) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    foreach ($archivosCreados as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
    fwrite(STDERR, "No se instaló la demostración: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
