<?php
// ============================================================================
// BACKEND: PROCESAMIENTO DE PETICIONES (WEB SERVICES & BASE DE DATOS)
// ============================================================================

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "servicio"; // Tu base de datos correcta

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Para obtener las categorías // GET categorías
if (isset($_GET['action']) && $_GET['action'] === 'get_categorias') {
    header('Content-Type: application/json; charset=UTF-8');
    
    $categorias = [
        ['id' => 1, 'title' => 'Electricidad', 'icon' => 'fa-bolt'],
        ['id' => 2, 'title' => 'Plomeria', 'icon' => 'fa-wrench'],
        ['id' => 3, 'title' => 'Pintura', 'icon' => 'fa-paint-roller'],
        ['id' => 4, 'title' => 'Carpinteria', 'icon' => 'fa-hammer']
    ];
    
    echo json_encode($categorias);
    exit;
}

// Para recibir el formulario CON LA FOTO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=UTF-8');
    
    session_start();
    // Simulamos que el usuario 99 está logueado
    $idUsuarioLogueado = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 99; 

    // Al usar FormData, los datos de texto llegan en $_POST en lugar de php://input
    $categoria   = isset($_POST['categoria']) ? $_POST['categoria'] : '';
    $descripcion = isset($_POST['descripcion']) ? $_POST['descripcion'] : '';
    $direccion   = isset($_POST['direccion']) ? $_POST['direccion'] : '';
    $fecha       = isset($_POST['fecha']) ? $_POST['fecha'] : '';
    $hora        = isset($_POST['hora']) ? $_POST['hora'] : '';
    $metodoPago  = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : '';
    $montoBase   = 200.00; // Tarifa fija universal por diagnóstico

    // ==========================================
    // PROCESAMIENTO DE LA FOTO
    // ==========================================
    $ruta_foto = null; // Por defecto nulo si no suben nada
    
    // Verificamos si llegó un archivo y no hubo errores
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $directorioSubida = 'uploads/';
        
        // Si no existe la carpeta 'uploads', la creamos
        if (!is_dir($directorioSubida)) {
            mkdir($directorioSubida, 0777, true);
        }

        // Generamos un nombre único (ej. 1684392019_mifoto.jpg)
        $nombreArchivo = time() . '_' . basename($_FILES['foto']['name']);
        $rutaDestino = $directorioSubida . $nombreArchivo;

        // Movemos el archivo temporal a nuestra carpeta final
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $ruta_foto = $rutaDestino; // Guardamos la ruta para la base de datos
        }
    }

    // ==========================================
    // INSERCIÓN A BASE DE DATOS
    // ==========================================
    // Agregamos ruta_foto a la consulta SQL
    $stmt = $conn->prepare("INSERT INTO solicitudes_servicio (id_usuario, categoria, descripcion, ruta_foto, direccion, fecha_servicio, hora_servicio, metodo_pago, monto_inspeccion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssd", $idUsuarioLogueado, $categoria, $descripcion, $ruta_foto, $direccion, $fecha, $hora, $metodoPago, $montoBase);
    
    $stmt->execute();
    $idInsertado = $stmt->insert_id;
    $stmt->close();
    
    $respuesta = [
        'status' => 'success',
        'id' => $idInsertado,
        'id_usuario_utilizado' => $idUsuarioLogueado, 
        'categoria' => $categoria,
        'fecha' => $fecha,
        'foto_subida' => $ruta_foto ? true : false,
        'mensaje' => 'Reserva guardada correctamente.'
    ];

    echo json_encode($respuesta);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fixi · Solicitar Servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet" />
  
  <style>
    /* ============================================================
       TEMA DETROIT INDUSTRIAL 
       ============================================================ */
    :root {
      --metal-blue: #1e5f8e;
      --metal-blue-dark: #154a6e;
      --metal-blue-light: #3a7ca8;
      --black: #121212;
      --black-light: #1f1f1f;
      --gray-metal: #2c2c2c;
      --gray-metal-light: #3c3c3c;
      --white: #f0f0f0;
      --font-title: 'Oswald', sans-serif;
      --font-body: 'Roboto', sans-serif;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { height: 100%; background-color: #0d0d0d; color: #e0e0e0; font-family: var(--font-body); font-weight: 300; line-height: 1.6; }
    body { display: flex; flex-direction: column; min-height: 100vh; }
    h1, h2, h3, h4, h5, .navbar-brand, .btn, .card-title, .step-title, .font-oswald { font-family: var(--font-title); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600; }
    
    .navbar { background: linear-gradient(180deg, #1a1a1a 0%, #0d0d0d 100%); border-bottom: 2px solid var(--metal-blue); padding: 0.5rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.8); flex-shrink: 0; }
    .navbar .navbar-brand { font-size: 1.8rem; font-weight: 700; color: var(--white); text-shadow: 0 0 10px rgba(30, 95, 142, 0.5); letter-spacing: 3px; }
    .navbar .navbar-brand span { color: var(--metal-blue); }
    .navbar .nav-link { color: #cccccc !important; font-family: var(--font-title); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; margin: 0 0.5rem; transition: 0.3s; border-bottom: 2px solid transparent; }
    .navbar .nav-link:hover { color: var(--white) !important; border-bottom-color: var(--metal-blue); transform: translateY(-2px); }

    .btn-metal { background: linear-gradient(145deg, var(--metal-blue), var(--metal-blue-dark)); border: 1px solid #3a7ca8; color: var(--white); font-family: var(--font-title); text-transform: uppercase; letter-spacing: 1.5px; padding: 0.5rem 1.8rem; border-radius: 0; transition: all 0.25s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.5); font-weight: 600; }
    .btn-metal:hover { background: linear-gradient(145deg, var(--metal-blue-light), var(--metal-blue)); border-color: #8ab3d0; color: #fff; transform: scale(1.02); box-shadow: 0 0 20px rgba(30, 95, 142, 0.6); }
    .btn-metal-sm { padding: 0.3rem 1.2rem; font-size: 0.8rem; }
    .btn-outline-metal { background: transparent; border: 2px solid var(--metal-blue); color: var(--white); font-family: var(--font-title); text-transform: uppercase; letter-spacing: 1.5px; padding: 0.3rem 1.2rem; border-radius: 0; transition: 0.3s; font-size: 0.8rem; }
    .btn-outline-metal:hover { background: var(--metal-blue); color: #fff; box-shadow: 0 0 20px rgba(30, 95, 142, 0.4); }

    .main-container { flex: 1; display: flex; align-items: stretch; padding: 1rem 0; }
    .app-card { background: #1a1a1a; border: 1px solid #2a3a47; border-radius: 0; box-shadow: 0 8px 24px rgba(0,0,0,0.7); border-top: 4px solid var(--metal-blue); height: 100%; display: flex; flex-direction: column; }
    .app-card-body { flex: 1; display: flex; flex-direction: column; padding: 1.5rem 2rem; }
    .app-card h2 { color: var(--white); font-size: 1.4rem; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #2a3a47; }
    .app-card h2 i { color: var(--metal-blue); }
    .app-card h5 { color: #aaaaaa; font-size: 0.85rem; letter-spacing: 1px; margin-bottom: 0.4rem; }

    .pantalla { display: flex; flex-direction: column; flex: 1; }
    .pantalla.hidden { display: none !important; }

    .process-bar { display: flex; align-items: center; margin-bottom: 0.75rem; flex-shrink: 0; }
    .step-item { display: flex; flex-direction: column; align-items: center; flex: 1; }
    .step-circle { width: 28px; height: 28px; border-radius: 50%; background: #1f1f1f; border: 2px solid #2a3a47; color: #888; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; font-family: var(--font-title); }
    .step-circle.active, .step-circle.completed { background: var(--metal-blue); border-color: var(--metal-blue); color: #fff; box-shadow: 0 0 20px rgba(30, 95, 142, 0.3); }
    .step-label { font-size: 9px; margin-top: 2px; color: #666; text-align: center; font-family: var(--font-title); letter-spacing: 0.5px; text-transform: uppercase; }
    .step-label.active { color: var(--metal-blue); font-weight: bold; }
    .step-line { flex: 1; height: 2px; background: #2a3a47; margin-bottom: 14px; }
    .step-line.completed { background: var(--metal-blue); }

    .category-card { background: #1f1f1f; border: 1px solid #2a3a47; padding: 12px 6px; text-align: center; cursor: pointer; transition: all 0.3s ease; border-radius: 0; }
    .category-card:hover { border-color: var(--metal-blue); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(30, 95, 142, 0.15); }
    .category-card.active { border-color: var(--metal-blue); background: #1a1a1a; box-shadow: 0 0 20px rgba(30, 95, 142, 0.1); }
    .category-card i { font-size: 1.5rem; color: var(--metal-blue); margin-bottom: 4px; display: block; }
    .category-card .category-text { color: #cccccc; font-size: 11px; font-weight: 500; font-family: var(--font-title); letter-spacing: 0.5px; text-transform: uppercase; }
    .category-card.active .category-text { color: var(--white); }

    .form-control, .form-select { background: #0d0d0d; border: 1px solid #2a3a47; border-radius: 0; color: #fff; padding: 0.4rem 0.75rem; font-size: 0.85rem; }
    .form-control:focus, .form-select:focus { background: #0d0d0d; border-color: var(--metal-blue); box-shadow: 0 0 0 0.2rem rgba(30, 95, 142, 0.2); color: #fff; }
    .form-control::placeholder { color: #555; }
    textarea.form-control { background: #0d0d0d; color: #fff; min-height: 50px; resize: vertical; }

    .tarjeta-form { background: #0d0d0d; border: 1px solid #2a3a47; padding: 0.75rem 1rem; margin-top: 0.5rem; display: none; }
    .tarjeta-form.visible { display: block; }
    .tarjeta-form .row { margin: 0 -0.25rem; }
    .tarjeta-form .col-6, .tarjeta-form .col-4 { padding: 0 0.25rem; }
    .tarjeta-form label { color: #888; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; font-family: var(--font-title); margin-bottom: 0.1rem; }
    .tarjeta-form .form-control { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
    .tarjeta-form .form-control::placeholder { font-size: 0.7rem; }

    .direccion-wrapper { display: flex; align-items: center; gap: 0.5rem; background: #0d0d0d; border: 1px solid #2a3a47; padding: 0.2rem 0.5rem; }
    .direccion-wrapper i { color: var(--metal-blue); font-size: 1.1rem; flex-shrink: 0; }
    .direccion-wrapper input { border: none; background: transparent; color: #fff; padding: 0.4rem 0; font-size: 0.85rem; width: 100%; outline: none; }
    .direccion-wrapper input::placeholder { color: #555; }

    .add-photo-box { border: 2px dashed var(--metal-blue); background: #0d0d0d; padding: 12px; text-align: center; cursor: pointer; transition: 0.3s; }
    .add-photo-box:hover { border-color: var(--metal-blue-light); background: #1a1a1a; }
    .add-photo-box i { color: var(--metal-blue); font-size: 1.2rem; }
    .add-photo-box span { color: #888; font-size: 0.75rem; }

    .date-selector-trigger { display: flex; align-items: center; justify-content: space-between; background: #0d0d0d; border: 1px solid #2a3a47; padding: 0.4rem 0.75rem; cursor: pointer; transition: 0.3s; }
    .date-selector-trigger:hover { border-color: var(--metal-blue); }
    .date-selector-trigger span { color: #cccccc !important; font-size: 0.85rem; }
    .calendar-wrapper { background: #0d0d0d; border: 1px solid #2a3a47; padding: 10px; margin-top: 6px; display: none; }
    .calendar-wrapper.open { display: block; }
    .calendar-wrapper .text-muted { color: #888 !important; }
    .calendar-wrapper strong { color: var(--white); font-size: 0.9rem; }
    .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); text-align: center; font-weight: bold; font-size: 10px; margin-bottom: 6px; color: #666; }
    .days-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 3px; }
    .day-cell { padding: 4px 0; text-align: center; font-size: 12px; cursor: pointer; color: #cccccc; transition: 0.2s; }
    .day-cell:hover:not(.empty) { background: #1f1f1f; }
    .day-cell.selected { background: var(--metal-blue); color: white; }
    .day-cell.empty { pointer-events: none; color: transparent; }

    .hora-chip { background: #0d0d0d; border: 1px solid #2a3a47; padding: 3px 10px; font-size: 11px; cursor: pointer; margin-right: 4px; margin-bottom: 4px; display: inline-block; color: #cccccc; transition: 0.3s; border-radius: 0; }
    .hora-chip:hover { border-color: var(--metal-blue); }
    .hora-chip.active { background: var(--metal-blue); color: #fff; border-color: var(--metal-blue); }

    .resumen-card { background: #0d0d0d; border: 1px solid #2a3a47; padding: 0.75rem 1rem; border-radius: 0; flex-shrink: 0; }
    .resumen-card .icon-box { width: 40px; height: 40px; background: #1a1a1a; border: 1px solid #2a3a47; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .resumen-card .icon-box i { color: var(--metal-blue); font-size: 1.2rem; }
    .resumen-card h5 { color: var(--white); font-size: 0.95rem; margin-bottom: 0; }
    .resumen-card .desc { color: #888; font-size: 0.75rem; margin-bottom: 0; }
    .resumen-card .fila-info { display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; font-size: 0.75rem; color: #888; }
    .resumen-card .fila-info strong { color: #aaaaaa; font-family: var(--font-title); letter-spacing: 0.5px; font-size: 0.7rem; }
    .resumen-card .fila-info .valor { color: var(--white); }

    .list-group-item { background: #0d0d0d; border: 1px solid #2a3a47; color: #cccccc; border-radius: 0 !important; padding: 0.4rem 0.75rem; font-size: 0.85rem; cursor: pointer; transition: 0.3s; }
    .list-group-item:hover { border-color: var(--metal-blue); }
    .list-group-item .form-check-input { background-color: #0d0d0d; border-color: #2a3a47; margin-right: 0.5rem; width: 14px; height: 14px; }
    .list-group-item .form-check-input:checked { background-color: var(--metal-blue); border-color: var(--metal-blue); }
    .list-group-item .text-muted { color: #666 !important; font-size: 0.7rem; }
    .list-group-item .badge-pago { background: var(--metal-blue-dark); color: #fff; font-size: 0.6rem; padding: 0.2rem 0.5rem; font-family: var(--font-title); letter-spacing: 0.5px; }

    .desglose-box { background: #0d0d0d; border: 1px solid #2a3a47; padding: 0.5rem 0.75rem; }
    .desglose-box .fila { display: flex; justify-content: space-between; font-size: 0.8rem; color: #888; padding: 0.15rem 0; }
    .desglose-box .fila .valor { color: var(--white); }
    .desglose-box .total { border-top: 1px solid #2a3a47; padding-top: 0.3rem; margin-top: 0.3rem; font-size: 1rem; font-family: var(--font-title); color: var(--white); }

    .modal-content { background: #1a1a1a; border: 1px solid var(--metal-blue); border-radius: 0; box-shadow: 0 0 50px rgba(0,0,0,0.9); }
    .modal-header { border-bottom: 1px solid #2a3a47; background: #0d0d0d; }
    .modal-header .modal-title { font-family: var(--font-title); text-transform: uppercase; letter-spacing: 2px; color: var(--white); }
    .modal-header .btn-close { filter: invert(1) brightness(2); }
    .modal-body { background: #0d0d0d; }
    .modal-body .form-control { background: #0d0d0d; border: 1px solid #2a3a47; border-radius: 0; color: #fff; padding: 0.5rem 0.75rem; }
    .modal-body .form-control:focus { background: #0d0d0d; border-color: var(--metal-blue); box-shadow: 0 0 0 0.25rem rgba(30, 95, 142, 0.25); color: #fff; }
    .modal-body .form-control::placeholder { color: #555; }
    .modal-footer { border-top: 1px solid #2a3a47; background: #0d0d0d; }

    footer { background: #0a0a0a; border-top: 2px solid var(--metal-blue); padding: 0.8rem 0; color: #666; flex-shrink: 0; margin-top: auto; }
    footer .footer-brand { font-family: var(--font-title); font-size: 1.4rem; color: var(--white); letter-spacing: 2px; }
    footer .footer-brand span { color: var(--metal-blue); }
    footer .text-secondary { color: #666 !important; font-size: 0.75rem !important; }
    footer a { color: #888; text-decoration: none; transition: 0.3s; font-size: 0.75rem; }
    footer a:hover { color: var(--metal-blue); text-decoration: underline; }

    .bg-light { background: #1a1a1a !important; }
    .bg-white { background: #0d0d0d !important; border-color: #2a3a47 !important; }
    .text-dark { color: var(--white) !important; }
    .text-muted { color: #666 !important; }
    .border { border-color: #2a3a47 !important; }
    .border-top { border-top-color: #2a3a47 !important; }
    .border-bottom { border-bottom-color: #2a3a47 !important; }
    .bg-dark { background: #0d0d0d !important; }
    .text-white { color: var(--white) !important; }
    .hidden { display: none !important; }
    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
    .flex-1 { flex: 1; }
    .mt-auto { margin-top: auto; }
    .mb-auto { margin-bottom: auto; }

    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #0d0d0d; }
    ::-webkit-scrollbar-thumb { background: var(--metal-blue); border-radius: 0; }
    ::-webkit-scrollbar-thumb:hover { background: var(--metal-blue-light); }

    @media (max-width: 576px) {
      .app-card-body { padding: 1rem; }
      .app-card h2 { font-size: 1.1rem; }
      .category-card i { font-size: 1.2rem; }
      .category-card .category-text { font-size: 9px; }
      .resumen-card .fila-info { flex-direction: column; gap: 0.2rem; }
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <i class="fas fa-tools me-2" style="color: var(--metal-blue);"></i>Fix<span>i</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarFixi" 
              aria-controls="navbarFixi" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarFixi">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item"><a class="nav-link" href="#servicios">Servicios</a></li>
          <li class="nav-item"><a class="nav-link" href="#como-funciona">Cómo funciona</a></li>
          <li class="nav-item"><a class="nav-link" href="#por-que">Por qué Fixi</a></li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="modal" data-bs-target="#loginModal" style="cursor: pointer;">
              <i class="fas fa-user-circle me-1"></i> Iniciar sesión
            </a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-metal btn-metal-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
              <i class="fas fa-sign-in-alt me-1"></i> Ingresar
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="container main-container">
    <div class="row w-100 mx-0">
      <div class="col-12">
        
        <div class="card app-card">
          <div class="app-card-body">
            
            <div id="pantallaSeleccion" class="pantalla">
              
              <div class="process-bar">
                <div class="step-item">
                  <div class="step-circle active">1</div>
                  <span class="step-label active">Detalles</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item">
                  <div class="step-circle">2</div>
                  <span class="step-label">Confirmar</span>
                </div>
              </div>

              <h2><i class="fas fa-file-invoice me-2"></i>Configura tu Solicitud</h2>

              <h5>1. Selecciona la Categoría</h5>
              <div class="row g-1 mb-2" id="categoriasContainer">
              </div>

              <h5>2. Describe el problema</h5>
              <textarea class="form-control mb-2 text-area" placeholder="Ej. Instalación de interruptor y revisión del tablero..." rows="2"></textarea>

              <h5>3. Adjuntar Imágenes</h5>
              <div class="add-photo-box mb-2" id="cajaFoto" onclick="document.getElementById('fotoInput').click()">
                <i class="fas fa-cloud-upload-alt d-block" id="iconoFoto"></i>
                <span class="small text-uppercase" id="textoFoto">Subir fotos del daño</span>
              </div>
              <input type="file" id="fotoInput" name="foto" accept="image/*" style="display: none;">

              <h5>4. Dirección</h5>
              <div class="direccion-wrapper mb-2">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" id="direccionInput" placeholder="Calle, número, colonia, ciudad, CP" value="">
              </div>

              <h5>5. Programar Fecha y Hora</h5>
              <div class="date-section mb-2">
                <div class="date-selector-trigger" id="dateTrigger">
                  <div>
                    <i class="fas fa-calendar-alt me-2" style="color:var(--metal-blue);"></i>
                    <span id="fechaSeleccionada" style="color:#cccccc !important;">Seleccionar ventana de tiempo...</span>
                  </div>
                  <i class="fas fa-chevron-down" style="color:#666;" id="chevronIcon"></i>
                </div>

                <div class="calendar-wrapper" id="calendarWrapper">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong id="monthYear">Mes Año</strong>
                    <div>
                      <button class="btn btn-outline-metal btn-metal-sm" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                      <button class="btn btn-outline-metal btn-metal-sm" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
                    </div>
                  </div>
                  <div class="weekdays">
                    <span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span>
                  </div>
                  <div class="days-grid mb-2" id="daysGrid"></div>

                  <div class="border-top pt-2" style="border-top-color:#2a3a47 !important;">
                    <h6 style="color:#aaaaaa; font-family:var(--font-title); letter-spacing:0.5px; font-size:0.75rem;">
                      <i class="far fa-clock me-1" style="color:var(--metal-blue);"></i>Horarios:
                    </h6>
                    <div id="horasContainer">
                      <span class="hora-chip" data-hora="9:00 AM">9:00</span>
                      <span class="hora-chip active" data-hora="10:00 AM">10:00</span>
                      <span class="hora-chip" data-hora="11:00 AM">11:00</span>
                      <span class="hora-chip" data-hora="1:00 PM">1:00</span>
                      <span class="hora-chip" data-hora="4:00 PM">4:00</span>
                    </div>
                  </div>
                </div>
              </div>

              <button class="btn btn-metal w-100 py-2 mt-auto text-uppercase" id="btnContinuar">
                Proceder a la Confirmación <i class="fas fa-arrow-right ms-2"></i>
              </button>
            </div>

            <div id="pantallaConfirmacion" class="pantalla hidden">
              
              <div class="d-flex justify-content-between align-items-center mb-2">
                <button class="btn btn-outline-metal btn-metal-sm" id="btnVolverSeleccion">
                  <i class="fas fa-chevron-left me-1"></i> Modificar
                </button>
              </div>

              <div class="process-bar">
                <div class="step-item">
                  <div class="step-circle completed">1</div>
                  <span class="step-label" style="color:var(--metal-blue);">Detalles</span>
                </div>
                <div class="step-line completed"></div>
                <div class="step-item">
                  <div class="step-circle active">2</div>
                  <span class="step-label active">Confirmar</span>
                </div>
              </div>

              <h2><i class="fas fa-check-circle me-2" style="color:var(--metal-blue);"></i>Resumen y Reserva</h2>

              <div class="resumen-card mb-2">
                <div class="d-flex align-items-start gap-2">
                  <div class="icon-box">
                    <i class="fas fa-hammer" id="resumenIcono"></i>
                  </div>
                  <div class="flex-1">
                    <h5 id="resumenCategoria">CARPINTERIA</h5>
                    <p class="desc" id="resumenDescripcion">No se ingresaron detalles u observaciones de campo.</p>
                  </div>
                </div>
                <div class="fila-info mt-2">
                  <div>
                    <strong><i class="fas fa-clock me-1" style="color:var(--metal-blue);"></i> Cita Programada:</strong>
                    <span class="valor" id="fechaHoraResumen">Fecha — Hora</span>
                  </div>
                  <div>
                    <strong><i class="fas fa-map-marked-alt me-1" style="color:var(--metal-blue);"></i> Dirección:</strong>
                    <span class="valor" id="resumenDireccion">No especificada</span>
                  </div>
                </div>
              </div>

              <h5>Método de Pago para la Inspección</h5>
              <div class="list-group mb-2">
                <label class="list-group-item d-flex justify-content-between align-items-center" id="opcionTarjeta">
                  <div>
                    <input class="form-check-input" type="radio" name="pagoRadio" value="tarjeta" checked>
                    <i class="far fa-credit-card me-2" style="color:var(--metal-blue);"></i> Tarjeta Visa / Mastercard
                  </div>
                  <span class="badge-pago">Nuevo</span>
                </label>
                <label class="list-group-item d-flex justify-content-between align-items-center" id="opcionEfectivo">
                  <div>
                    <input class="form-check-input" type="radio" name="pagoRadio" value="efectivo">
                    <i class="fas fa-money-bill-wave me-2" style="color:#4caf50;"></i> Pago en Efectivo al concluir diagnóstico
                  </div>
                </label>
              </div>

              <div class="tarjeta-form visible" id="tarjetaForm">
                <div class="row g-1 mb-2">
                  <div class="col-12">
                    <label>Número de Tarjeta</label>
                    <input type="text" class="form-control" placeholder="1234 5678 9012 3456" id="cardNumber" maxlength="19">
                  </div>
                </div>
                <div class="row g-1 mb-2">
                  <div class="col-6">
                    <label>Fecha de Expiración</label>
                    <input type="text" class="form-control" placeholder="MM/AA" id="cardExpiry" maxlength="5">
                  </div>
                  <div class="col-6">
                    <label>CVV</label>
                    <input type="password" class="form-control" placeholder="•••" id="cardCvv" maxlength="4">
                  </div>
                </div>
                <div class="row g-1">
                  <div class="col-12">
                    <label>Nombre del Titular</label>
                    <input type="text" class="form-control" placeholder="Como aparece en la tarjeta" id="cardName">
                  </div>
                </div>
              </div>

              <h5>Desglose por Visita de Inspección</h5>
              <div class="desglose-box mb-2">
                <div class="fila"><span>Visita de diagnóstico técnico</span><span class="valor">$200.00</span></div>
                <div class="fila total d-flex justify-content-between">
                  <span>Total Inspección</span>
                  <span id="totalPagar">$200.00</span>
                </div>
              </div>

              <p class="text-warning small text-center px-2 mb-2" style="font-size:0.7rem; font-family: var(--font-title);">
                * El costo de la reparación final se cotizará directamente con el técnico durante la revisión en casa.
              </p>

              <button class="btn btn-metal w-100 py-2 text-uppercase mt-auto" id="confirmarBtn">
                <i class="fas fa-lock me-2"></i> Generar Orden 
                <span id="btnTotal" class="ms-2 badge" style="background:var(--metal-blue-dark);">$200.00</span>
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel"><i class="fas fa-sign-in-alt me-2" style="color:var(--metal-blue);"></i>Iniciar sesión</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="loginForm">
            <div class="mb-2">
              <label for="emailInput" class="form-label" style="color:#aaaaaa; font-family:var(--font-title); letter-spacing:0.5px; font-size:0.8rem;">Correo electrónico</label>
              <input type="email" class="form-control" id="emailInput" placeholder="tu@email.com" required>
            </div>
            <div class="mb-2">
              <label for="passwordInput" class="form-label" style="color:#aaaaaa; font-family:var(--font-title); letter-spacing:0.5px; font-size:0.8rem;">Contraseña</label>
              <input type="password" class="form-control" id="passwordInput" placeholder="********" required>
            </div>
            <div class="mb-2 text-end">
              <a href="#" style="color:#666; font-size:0.75rem;">¿Olvidaste tu contraseña?</a>
            </div>
            <button type="submit" class="btn btn-metal w-100 py-2">
              <i class="fas fa-arrow-right me-2"></i>Iniciar sesión
            </button>
          </form>
          <div class="mt-2 text-center">
            <small style="color:#666; font-size:0.7rem;">¿No tienes cuenta? <a href="#" style="color:var(--metal-blue); text-decoration:none;">Regístrate</a></small>
          </div>
          <div class="mt-1 text-center">
            <small style="color:#555; font-size:0.6rem;">Al continuar, aceptas nuestros <a href="#" style="color:var(--metal-blue); text-decoration:none;">Términos</a> y <a href="#" style="color:var(--metal-blue); text-decoration:none;">Privacidad</a>.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-metal btn-metal-sm" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-4 mb-1 mb-md-0">
          <div class="footer-brand">Fix<span>i</span></div>
          <p class="text-secondary" style="margin:0;">Servicios para tu hogar</p>
        </div>
        <div class="col-md-4 text-center mb-1 mb-md-0">
          <a href="#" class="mx-2">Términos</a>
          <a href="#" class="mx-2">Privacidad</a>
        </div>
        <div class="col-md-4 text-md-end">
          <span class="text-secondary">
            <i class="far fa-copyright"></i> 2026 Fixi · <i class="fas fa-heart" style="color: var(--metal-blue);"></i>
          </span>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function() {
      const API_BASE_URL = window.location.pathname; 

      const mapaIconos = {
          'Electricidad': 'fa-bolt',
          'Plomeria': 'fa-wrench',
          'Pintura': 'fa-paint-roller',
          'Carpinteria': 'fa-hammer'
      };

      // Control de pantallas
      const seleccion = document.getElementById('pantallaSeleccion');
      const confirmacion = document.getElementById('pantallaConfirmacion');
      const btnContinuar = document.getElementById('btnContinuar');
      const btnVolver = document.getElementById('btnVolverSeleccion');

      function mostrarPantalla(pantalla) {
        if (pantalla === 'seleccion') {
          seleccion.classList.remove('hidden');
          confirmacion.classList.add('hidden');
          window.scrollTo(0,0);
        } else {
          seleccion.classList.add('hidden');
          confirmacion.classList.remove('hidden');
          actualizarResumenConfirmacion();
          window.scrollTo(0,0);
        }
      }

      btnContinuar.addEventListener('click', () => mostrarPantalla('confirmacion'));
      btnVolver.addEventListener('click', () => mostrarPantalla('seleccion'));

      // Mostrar/Ocultar formulario de tarjeta
      const opcionTarjeta = document.getElementById('opcionTarjeta');
      const opcionEfectivo = document.getElementById('opcionEfectivo');
      const tarjetaForm = document.getElementById('tarjetaForm');

      opcionTarjeta.addEventListener('click', function() {
        document.querySelector('input[name="pagoRadio"][value="tarjeta"]').checked = true;
        tarjetaForm.classList.add('visible');
        tarjetaForm.classList.remove('hidden');
      });

      opcionEfectivo.addEventListener('click', function() {
        document.querySelector('input[name="pagoRadio"][value="efectivo"]').checked = true;
        tarjetaForm.classList.remove('visible');
        tarjetaForm.classList.add('hidden');
      });

      // Calendario
      const dateTrigger = document.getElementById('dateTrigger');
      const calendarWrapper = document.getElementById('calendarWrapper');
      const fechaSeleccionadaEl = document.getElementById('fechaSeleccionada');
      const monthYearEl = document.getElementById('monthYear');
      const daysGrid = document.getElementById('daysGrid');
      const prevMonthBtn = document.getElementById('prevMonth');
      const nextMonthBtn = document.getElementById('nextMonth');

      let currentDate = new Date();
      let selectedDate = new Date();
      selectedDate.setDate(selectedDate.getDate() + 1); 
      let selectedHora = '10:00 AM';
      let calendarOpen = false;

      function formatFechaCorta(date) {
        const dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        return `${dias[date.getDay()]}, ${date.getDate()} de ${meses[date.getMonth()]}`;
      }

      function formatFechaCompleta(date) {
        const dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        return `${dias[date.getDay()]}, ${date.getDate()} de ${meses[date.getMonth()]}`;
      }

      function actualizarTextoFecha() {
        fechaSeleccionadaEl.textContent = `${formatFechaCorta(selectedDate)} @ ${selectedHora}`;
        fechaSeleccionadaEl.style.color = '#cccccc';
      }

      function actualizarResumenConfirmacion() {
        const fechaStr = formatFechaCompleta(selectedDate);
        document.getElementById('fechaHoraResumen').textContent = `${fechaStr} — ${selectedHora}`;
        
        const categoryCard = document.querySelector('.category-card.active');
        const categoriaActiva = categoryCard ? categoryCard.getAttribute('data-categoria') : 'Electricidad';
        const descripcionProblema = document.querySelector('.text-area').value.trim();
        const direccion = document.getElementById('direccionInput').value.trim() || 'No especificada';
        
        document.getElementById('resumenCategoria').textContent = categoriaActiva.toUpperCase();
        document.getElementById('resumenDescripcion').textContent = descripcionProblema || 'No se ingresaron detalles u observaciones de campo.';
        document.getElementById('resumenDireccion').textContent = direccion;
        
        const claseIcono = mapaIconos[categoriaActiva] || 'fa-tools';
        document.getElementById('resumenIcono').className = `fas ${claseIcono}`;
      }

      function renderCalendar(year, month) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startDayOfWeek = firstDay.getDay();
        let startOffset = (startDayOfWeek === 0) ? 6 : startDayOfWeek - 1;

        const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        monthYearEl.textContent = `${monthNames[month]} ${year}`;

        let html = '';
        for (let i = 0; i < startOffset; i++) {
          html += `<div class="day-cell empty"></div>`;
        }

        for (let day = 1; day <= daysInMonth; day++) {
          const dateObj = new Date(year, month, day);
          const isSelected = (dateObj.getFullYear() === selectedDate.getFullYear() &&
                              dateObj.getMonth() === selectedDate.getMonth() &&
                              dateObj.getDate() === selectedDate.getDate());
          let cls = 'day-cell';
          if (isSelected) cls += ' selected';

          html += `<div class="${cls}" data-day="${day}" data-month="${month}" data-year="${year}">${day}</div>`;
        }
        daysGrid.innerHTML = html;

        document.querySelectorAll('.day-cell:not(.empty)').forEach(el => {
          el.addEventListener('click', function() {
            selectedDate = new Date(this.dataset.year, this.dataset.month, this.dataset.day);
            renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
            actualizarTextoFecha();
            calendarWrapper.classList.remove('open');
            calendarOpen = false;
          });
        });
      }

      dateTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        calendarOpen = !calendarOpen;
        calendarWrapper.classList.toggle('open', calendarOpen);
      });

      prevMonthBtn.addEventListener('click', (e) => { e.stopPropagation(); currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(currentDate.getFullYear(), currentDate.getMonth()); });
      nextMonthBtn.addEventListener('click', (e) => { e.stopPropagation(); currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(currentDate.getFullYear(), currentDate.getMonth()); });

      const horaChips = document.querySelectorAll('.hora-chip');
      horaChips.forEach(chip => {
        chip.addEventListener('click', function() {
          horaChips.forEach(c => c.classList.remove('active'));
          this.classList.add('active');
          selectedHora = this.dataset.hora;
          actualizarTextoFecha();
        });
      });

      // Cargar categorías
      async function cargarCategoriasWebService() {
        try {
          const response = await fetch(`${API_BASE_URL}?action=get_categorias`);
          if (!response.ok) throw new Error('Error al conectar');
          const data = await response.json();
          
          const container = document.getElementById('categoriasContainer');
          container.innerHTML = '';

          data.forEach((item, index) => {
            const col = document.createElement('div');
            col.className = 'col-3';
            col.innerHTML = `
              <div class="category-card ${index === 0 ? 'active' : ''}" data-categoria="${item.title}">
                <i class="fas ${item.icon}"></i>
                <span class="category-text">${item.title}</span>
              </div>
            `;
            
            col.querySelector('.category-card').addEventListener('click', function() {
              document.querySelectorAll('.category-card').forEach(c => c.classList.remove('active'));
              this.classList.add('active');
            });
            container.appendChild(col);
          });
        } catch (error) {
          console.error('Error cargando categorias por API:', error);
        }
      }

      // Validaciones estéticas
      const cardNumber = document.getElementById('cardNumber');
      cardNumber.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        let formatted = '';
        for (let i = 0; i < value.length && i < 16; i++) {
          if (i > 0 && i % 4 === 0) formatted += ' ';
          formatted += value[i];
        }
        this.value = formatted;
      });

      const cardExpiry = document.getElementById('cardExpiry');
      cardExpiry.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
          this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
        } else {
          this.value = value;
        }
      });

      const cardCvv = document.getElementById('cardCvv');
      cardCvv.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').substring(0, 4);
      });

      // FOTO
      const fotoInput = document.getElementById('fotoInput');
      const textoFoto = document.getElementById('textoFoto');
      const iconoFoto = document.getElementById('iconoFoto');

      fotoInput.addEventListener('change', function(e) {
        if (this.files && this.files.length > 0) {
          const nombreArchivo = this.files[0].name;
          textoFoto.textContent = nombreArchivo;
          textoFoto.style.color = '#4caf50';
          iconoFoto.className = 'fas fa-check-circle d-block';
          iconoFoto.style.color = '#4caf50';
        } else {
          textoFoto.textContent = 'Subir fotos del daño';
          textoFoto.style.color = '#888';
          iconoFoto.className = 'fas fa-cloud-upload-alt d-block';
          iconoFoto.style.color = 'var(--metal-blue)';
        }
      });

      // ENVIAR ORDEN CON FOTO (USANDO FORMDATA)
      document.getElementById('confirmarBtn').addEventListener('click', async function() {
        const activeCategoryCard = document.querySelector('.category-card.active');
        const categoria = activeCategoryCard ? activeCategoryCard.getAttribute('data-categoria') : 'Electricidad';
        const descripcion = document.querySelector('.text-area').value;
        const direccion = document.getElementById('direccionInput').value;
        const activePagoRadio = document.querySelector('input[name="pagoRadio"]:checked');
        const metodoPago = activePagoRadio ? activePagoRadio.value : 'tarjeta';

        if (metodoPago === 'tarjeta') {
          const numero = document.getElementById('cardNumber').value.replace(/\s/g, '');
          const expiracion = document.getElementById('cardExpiry').value;
          const cvv = document.getElementById('cardCvv').value;
          const nombre = document.getElementById('cardName').value.trim();

          if (numero.length < 16) {
            alert('Por favor, ingresa un número de tarjeta válido (16 dígitos).');
            return;
          }
          if (expiracion.length < 5) {
            alert('Por favor, ingresa la fecha de expiración (MM/AA).');
            return;
          }
          if (cvv.length < 3) {
            alert('Por favor, ingresa el CVV (3 o 4 dígitos).');
            return;
          }
          if (nombre.length < 2) {
            alert('Por favor, ingresa el nombre del titular de la tarjeta.');
            return;
          }
        }

        // Creamos el paquete FormData para enviar texto Y archivos // POST solicitud
        const formData = new FormData();
        formData.append('categoria', categoria);
        formData.append('descripcion', descripcion);
        formData.append('direccion', direccion);
        formData.append('fecha', formatFechaCompleta(selectedDate));
        formData.append('hora', selectedHora);
        formData.append('metodo_pago', metodoPago);

        // Si el usuario seleccionó una foto, la metemos al paquete
        if (fotoInput.files && fotoInput.files.length > 0) {
          formData.append('foto', fotoInput.files[0]);
        }

        try {
          this.disabled = true;
          this.innerText = 'Subiendo...';

          // Con FormData NO ponemos 'Content-Type', el navegador lo hace solo
          const response = await fetch(API_BASE_URL, {
            method: 'POST',
            body: formData
          });

          const resultado = await response.json();
          alert(`¡Orden de Inspección Creada!\n\nID de Orden: FIXI-${resultado.id}\nCategoría: ${resultado.categoria}\nDirección: ${direccion || 'No especificada'}\nForma de Pago: ${metodoPago === 'tarjeta' ? 'Tarjeta' : 'Efectivo'}\nFoto guardada en servidor: ${resultado.foto_subida ? 'SÍ' : 'NO'}`);
          
        } catch (e) {
          alert('Error al procesar el envío de la foto y los datos.');
        } finally {
          this.disabled = false;
          this.innerHTML = `<i class="fas fa-lock me-2"></i> Generar Orden <span id="btnTotal" class="ms-2 badge" style="background:var(--metal-blue-dark);">${document.getElementById('totalPagar').textContent}</span>`;
        }
      });

      // Login
      document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('¡Bienvenido a Fixi!');
        bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
        document.getElementById('emailInput').value = '';
        document.getElementById('passwordInput').value = '';
      });

      // Init
      renderCalendar(currentDate.getFullYear(), currentDate.getMonth());
      actualizarTextoFecha();
      cargarCategoriasWebService();
    })();
  </script>
</body>
</html>