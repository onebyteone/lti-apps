<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi App Canvas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-6">

    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-blue-800 mb-4">
            Sistema Externo de Pedagogía
        </h1>
        
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
            <p class="font-bold">Mensaje del sistema:</p>
            <p>{{ $message }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
            <div>
                <span class="font-semibold">ID Canvas:</span> {{ $data['user_id'] }}
            </div>
            <div>
                <span class="font-semibold">Email:</span> {{ $data['email'] }}
            </div>
            <div>
                <span class="font-semibold">Rol:</span> {{ $data['role'] }}
            </div>
        </div>

        <hr class="my-6">
        
        <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Iniciar Actividad (Simulada)
        </button>
    </div>

</body>
</html>