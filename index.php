<?php
require 'src/lib/db_connection.php';
require 'vendor/autoload.php';
// require 'src/lib/PHPExcel.php'; // Asegúrate de incluir PHPExcel

// Función para obtener todos los equipos
function getEquipos($pdo)
{
    $stmt = $pdo->query('SELECT e.*, c.nombre as categoria_nombre FROM equipos e LEFT JOIN categorias c ON e.categoria_id = c.id');
    return $stmt->fetchAll();
}

// Función para obtener todos los usuarios
function getUsuarios($pdo)
{
    $stmt = $pdo->query('SELECT * FROM usuarios');
    return $stmt->fetchAll();
}

// Función para obtener todas las categorías
function getCategorias($pdo)
{
    $stmt = $pdo->query('SELECT * FROM categorias');
    return $stmt->fetchAll();
}

// Función para obtener las asignaciones
function getAsignaciones($pdo)
{
    $sql = "SELECT a.*, e.nombre_equipo, u.nombre as usuario_nombre 
            FROM asignaciones a 
            JOIN equipos e ON a.equipo_id = e.id 
            JOIN usuarios u ON a.usuario_id = u.id";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Procesar el formulario para agregar equipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_equipo'])) {
    $categoria_id = isset($_POST['categoria_id']) ? $_POST['categoria_id'] : '';
    $auranet = isset($_POST['auranet']) ? $_POST['auranet'] : '';
    $nombre_equipo = isset($_POST['nombre_equipo']) ? $_POST['nombre_equipo'] : '';
    $marca = isset($_POST['marca']) ? $_POST['marca'] : '';
    $modelo = isset($_POST['modelo']) ? $_POST['modelo'] : '';
    $serie = isset($_POST['serie']) ? $_POST['serie'] : '';
    $procesador = isset($_POST['procesador']) ? $_POST['procesador'] : '';
    $disco = isset($_POST['disco']) ? $_POST['disco'] : '';
    $ram = isset($_POST['ram']) ? $_POST['ram'] : '';
    $fecha_compra = isset($_POST['fecha_compra']) ? $_POST['fecha_compra'] : '';
    $costo = isset($_POST['costo']) ? $_POST['costo'] : '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : '';

    $sql = "INSERT INTO equipos (auranet, nombre_equipo, marca, modelo, serie, procesador, disco, ram, fecha_compra, costo, estado, categoria_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$auranet, $nombre_equipo, $marca, $modelo, $serie, $procesador, $disco, $ram, $fecha_compra, $costo, $estado, $categoria_id]);
}


// Procesar el formulario para agregar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_usuario'])) {
    $nombre = $_POST['nombre'];
    $cargo = $_POST['cargo'];
    $departamento = $_POST['departamento'];

    $sql = "INSERT INTO usuarios (nombre, cargo, departamento) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nombre, $cargo, $departamento]);
}

// Procesar el formulario para asignar equipo a usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_equipo'])) {
    $equipo_id = $_POST['equipo_id'];
    $usuario_id = $_POST['usuario_id'];
    $fecha_asignacion = $_POST['fecha_asignacion'];

    $sql = "INSERT INTO asignaciones (equipo_id, usuario_id, fecha_asignacion) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$equipo_id, $usuario_id, $fecha_asignacion]);
}

// Generar archivo Excel para una asignación específica
if (isset($_GET['exportar_excel'])) {
    require_once 'path/to/PHPExcel.php'; // Asegúrate de incluir la ruta correcta para PHPExcel

    $asignacion_id = $_GET['exportar_excel'];
    $sql = "SELECT a.*, e.nombre_equipo, e.procesador, e.ram, e.marca, e.modelo, e.serie, e.costo, e.estado, u.nombre as usuario_nombre
            FROM asignaciones a
            JOIN equipos e ON a.equipo_id = e.id
            JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asignacion_id]);
    $asignacion = $stmt->fetch();

    if ($asignacion) {
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Asignación');

        // Encabezados
        $sheet->setCellValue('A1', 'Campo');
        $sheet->setCellValue('B1', 'Valor');

        // Datos específicos
        $sheet->setCellValue('A2', 'Nombre del Usuario');
        $sheet->setCellValue('B2', $asignacion['usuario_nombre']);
        $sheet->setCellValue('A3', 'Modelo del Equipo');
        $sheet->setCellValue('B3', $asignacion['modelo']);
        $sheet->setCellValue('A4', 'Nombre del Equipo');
        $sheet->setCellValue('B4', $asignacion['nombre_equipo']);
        $sheet->setCellValue('A5', 'Procesador');
        $sheet->setCellValue('B5', $asignacion['procesador']);
        $sheet->setCellValue('A6', 'Memoria');
        $sheet->setCellValue('B6', $asignacion['ram']);
        $sheet->setCellValue('A7', 'Marca');
        $sheet->setCellValue('B7', $asignacion['marca']);
        $sheet->setCellValue('A8', 'Serie');
        $sheet->setCellValue('B8', $asignacion['serie']);
        $sheet->setCellValue('A9', 'Costo');
        $sheet->setCellValue('B9', $asignacion['costo']);
        $sheet->setCellValue('A10', 'Estado');
        $sheet->setCellValue('B10', $asignacion['estado']);
        $sheet->setCellValue('A11', 'Fecha de Asignación');
        $sheet->setCellValue('B11', $asignacion['fecha_asignacion']);

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = 'Asignacion_' . $asignacion_id . '.xlsx';
        $objWriter->save($filename);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}

// Eliminar asignación
if (isset($_GET['eliminar_asignacion'])) {
    $asignacion_id = $_GET['eliminar_asignacion'];
    $sql = "DELETE FROM asignaciones WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asignacion_id]);

    // Redirigir después de eliminar
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$equipos = getEquipos($pdo);
$usuarios = getUsuarios($pdo);
$categorias = getCategorias($pdo);
$asignaciones = getAsignaciones($pdo);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="src/css/styles.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="equipos-tab" data-toggle="tab" href="#equipos" role="tab">Registro de Equipos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="usuarios-tab" data-toggle="tab" href="#usuarios" role="tab">Registro de Usuarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="asignaciones-tab" data-toggle="tab" href="#asignaciones" role="tab">Asignaciones</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="equipos" role="tabpanel" aria-labelledby="equipos-tab">
                <h2 class="mt-3 mb-4">Agregar Nuevo Equipo</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tipo">Tipo de Equipo</label>
                            <select class="form-control" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="auranet">Auranet</label>
                            <input type="text" class="form-control" id="auranet" name="auranet" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="nombre_equipo">Nombre del Equipo</label>
                        <input type="text" class="form-control" id="nombre_equipo" name="nombre_equipo" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="marca">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="modelo">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="serie">Serie</label>
                        <input type="text" class="form-control" id="serie" name="serie">
                    </div>
                    <div class="form-group">
                        <label for="procesador">Procesador</label>
                        <input type="text" class="form-control" id="procesador" name="procesador">
                    </div>
                    <div class="form-group">
                        <label for="disco">Disco</label>
                        <input type="text" class="form-control" id="disco" name="disco">
                    </div>
                    <div class="form-group">
                        <label for="ram">RAM</label>
                        <input type="text" class="form-control" id="ram" name="ram">
                    </div>
                    <div class="form-group">
                        <label for="fecha_compra">Fecha de Compra</label>
                        <input type="date" class="form-control" id="fecha_compra" name="fecha_compra">
                    </div>
                    <div class="form-group">
                        <label for="costo">Costo</label>
                        <input type="number" step="0.01" class="form-control" id="costo" name="costo">
                    </div>
                    <div class="form-group">

                        <label for="equipo_id">Equipo</label>
                        <select class="form-control" id="equipo_id" name="equipo_id">
                            <option value="">Seleccione un equipo</option>
                            <option value="">Disponible</option>
                            <option value="">Asignado</option>
                            <option value="">Robado</option>
                            <option value="">Devolver</option>
                        </select>

                        <label for="estado">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado">
                    </div>
                    <button type="submit" name="agregar_equipo" class="btn btn-primary">Agregar Equipo</button>
                </form>
            </div>

            <div class="tab-pane fade" id="usuarios" role="tabpanel" aria-labelledby="usuarios-tab">
                <h2 class="mt-3 mb-4">Agregar Nuevo Usuario</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="cargo">Cargo</label>
                        <input type="text" class="form-control" id="cargo" name="cargo">
                    </div>
                    <div class="form-group">
                        <label for="departamento">Departamento</label>
                        <input type="text" class="form-control" id="departamento" name="departamento">
                    </div>
                    <button type="submit" name="agregar_usuario" class="btn btn-primary">Agregar Usuario</button>
                </form>
            </div>

            <div class="tab-pane fade" id="asignaciones" role="tabpanel" aria-labelledby="asignaciones-tab">
                <h2 class="mt-3 mb-4">Asignar Equipo a Usuario</h2>
                <form method="post">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="equipo_id">Equipo</label>
                            <select class="form-control" id="equipo_id" name="equipo_id">
                                <option value="">Seleccione un equipo</option>
                                <?php foreach ($equipos as $equipo): ?>
                                    <option value="<?php echo $equipo['id']; ?>"><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="usuario_id">Usuario</label>
                            <select class="form-control" id="usuario_id" name="usuario_id">
                                <option value="">Seleccione un usuario</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="fecha_asignacion">Fecha de Asignación</label>
                        <input type="date" class="form-control" id="fecha_asignacion" name="fecha_asignacion" required>
                    </div>
                    <button type="submit" name="asignar_equipo" class="btn btn-primary">Asignar Equipo</button>
                </form>
            </div>

        </div>
        <h2 class="mt-5">Lista de Equipos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Nombre</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($equipos as $equipo): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($equipo['categoria_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['nombre_equipo']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['marca']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['modelo']); ?></td>
                        <td><?php echo htmlspecialchars($equipo['estado']); ?></td>
                        <td>
                            <a href="editar_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="eliminar_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este equipo?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2 class="mt-5">Asignaciones de Equipos a Usuarios</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Equipo</th>
                    <th>Usuario</th>
                    <th>Fecha de Asignación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($asignaciones as $asignacion): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($asignacion['nombre_equipo']); ?></td>
                        <td><?php echo htmlspecialchars($asignacion['usuario_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($asignacion['fecha_asignacion']); ?></td>
                        <td>
                            <a href="?exportar_excel=<?php echo $asignacion['id']; ?>" class="btn btn-success">Exportar</a>
                            <a href="?eliminar_asignacion=<?php echo $asignacion['id']; ?>" class="btn btn-danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="src/js/scripts.js"></script>
</body>

</html>