<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Datos - App Mecánicos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 30px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning h3 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .warning p {
            margin: 0;
            color: #856404;
        }
        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        button {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .btn-success {
            background: #27ae60;
            color: white;
        }
        .btn-success:hover {
            background: #229954;
        }
        .btn-info {
            background: #3498db;
            color: white;
        }
        .btn-info:hover {
            background: #2980b9;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            display: none;
        }
        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Reset de Datos</h1>
        
        <div class="warning">
            <h3>⚠️ Advertencia</h3>
            <p>Esta herramienta te permite resetear los datos de la aplicación. Ten cuidado con las acciones que realices.</p>
        </div>

        <div class="button-group">
            <button class="btn-danger" onclick="clearAllData()">
                🗑️ Limpiar Todo
            </button>
            <button class="btn-success" onclick="reloadTestData()">
                📥 Recargar Datos
            </button>
            <button class="btn-info" onclick="showStorageInfo()">
                ℹ️ Ver Estado
            </button>
        </div>

        <div id="status" class="status"></div>

        <div class="back-link">
            <a href="../bienvenida/index.html">← Volver al Dashboard</a>
        </div>
    </div>

    <script>
        function showStatus(message, type = 'success') {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
            status.style.display = 'block';
            
            setTimeout(() => {
                status.style.display = 'none';
            }, 5000);
        }

        function clearAllData() {
            if (confirm('¿Estás seguro de que quieres limpiar TODOS los datos? Esta acción no se puede deshacer.')) {
                try {
                    localStorage.clear();
                    showStatus('✅ Todos los datos han sido eliminados del localStorage', 'success');
                } catch (error) {
                    showStatus('❌ Error al limpiar los datos: ' + error.message, 'error');
                }
            }
        }

        async function reloadTestData() {
            try {
                const response = await fetch('test-data.json');
                if (!response.ok) {
                    throw new Error('No se pudo cargar test-data.json');
                }
                
                const testData = await response.json();
                
                // Guardar cada sección en localStorage
                localStorage.setItem('ordenes', JSON.stringify(testData.ordenes || []));
                localStorage.setItem('productos', JSON.stringify(testData.productos || []));
                localStorage.setItem('conversaciones', JSON.stringify(testData.conversaciones || []));
                localStorage.setItem('mecanicos', JSON.stringify(testData.mecanicos || []));
                localStorage.setItem('movimientos', JSON.stringify(testData.movimientos || []));
                localStorage.setItem('consumos', JSON.stringify(testData.consumos || []));
                
                // Configurar perfil por defecto
                if (testData.mecanicos && testData.mecanicos.length > 0) {
                    localStorage.setItem('perfil', JSON.stringify(testData.mecanicos[0]));
                }
                
                showStatus('✅ Datos de prueba recargados correctamente', 'success');
            } catch (error) {
                showStatus('❌ Error al recargar datos: ' + error.message, 'error');
            }
        }

        function showStorageInfo() {
            const keys = Object.keys(localStorage);
            let info = '📊 Estado del localStorage:\n\n';
            
            if (keys.length === 0) {
                info += '❌ No hay datos en localStorage';
            } else {
                keys.forEach(key => {
                    try {
                        const data = JSON.parse(localStorage.getItem(key));
                        if (Array.isArray(data)) {
                            info += `📁 ${key}: ${data.length} elementos\n`;
                        } else if (typeof data === 'object') {
                            info += `📄 ${key}: objeto\n`;
                        } else {
                            info += `📝 ${key}: ${data}\n`;
                        }
                    } catch {
                        info += `📝 ${key}: texto\n`;
                    }
                });
            }
            
            alert(info);
        }

        // Mostrar estado actual al cargar la página
        window.addEventListener('load', () => {
            const keys = Object.keys(localStorage);
            if (keys.length === 0) {
                showStatus('ℹ️ No hay datos en localStorage. Usa "Recargar Datos" para cargar datos de prueba.', 'error');
            } else {
                showStatus(`ℹ️ Hay ${keys.length} elementos en localStorage.`, 'success');
            }
        });
    </script>
</body>
</html>
