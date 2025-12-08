# Chatbot con IA para Canvas LMS

Una aplicación externa (External Tool) para Canvas LMS que proporciona un chatbot inteligente impulsado por IA para ayudar a estudiantes e instructores con consultas sobre el curso.

## 🎯 Características

- **Chatbot con IA**: Utiliza OpenAI GPT para responder preguntas sobre el curso
- **Integración LTI 1.3**: Se integra de forma segura con Canvas LMS
- **Contexto del Curso**: Los instructores pueden cargar contenido del curso para mejorar las respuestas
- **Historial de Conversaciones**: Mantiene el historial de chat para cada estudiante
- **Interfaz Moderna**: UI construida con React, TypeScript y Tailwind CSS
- **Tiempo Real**: Respuestas instantáneas del chatbot

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: React 19 + TypeScript + Inertia.js
- **Estilos**: Tailwind CSS 4
- **IA**: OpenAI GPT-4o-mini (configurable)
- **Base de Datos**: SQLite (configurable a MySQL/PostgreSQL)
- **LTI**: Integración LTI 1.3 con Canvas LMS

## 📋 Requisitos

- PHP 8.2 o superior
- Composer
- Node.js 18 o superior
- npm o pnpm
- Cuenta de OpenAI con API Key
- Canvas LMS con permisos de administrador para instalar herramientas externas

## 🚀 Instalación

### 1. Clonar el repositorio e instalar dependencias

```bash
git clone <repository-url>
cd lti-test

# Instalar dependencias PHP
composer install

# Instalar dependencias JavaScript
npm install
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` y configura:

```env
APP_NAME="Chatbot con IA"
APP_URL=https://tu-dominio.com

# Base de datos
DB_CONNECTION=sqlite
# O usa MySQL/PostgreSQL si lo prefieres

# OpenAI
OPENAI_API_KEY=sk-your-api-key-here
OPENAI_ORGANIZATION=org-your-org-id (opcional)
OPENAI_MODEL=gpt-4o-mini

# Canvas LMS (opcional, para integraciones avanzadas)
CANVAS_API_URL=https://your-canvas-instance.instructure.com
CANVAS_API_TOKEN=your-canvas-api-token
```

### 3. Ejecutar migraciones

```bash
php artisan migrate
```

### 4. Compilar assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

### 5. Iniciar servidor

```bash
# Desarrollo
php artisan serve

# O usa Docker
docker build -t lti-chatbot .
docker run -p 8000:80 lti-chatbot
```

## 🔧 Configuración en Canvas LMS

### Método 1: Usando JSON (LTI 1.3 - Recomendado)

1. En Canvas, ve a **Admin** → **Developer Keys**
2. Haz clic en **+ Developer Key** → **+ LTI Key**
3. Configura:
   - **Key Name**: Chatbot con IA
   - **Redirect URIs**: `https://tu-dominio.com/lti/launch`
   - **Method**: Paste JSON
   - **LTI 1.3 Settings**: Pega el contenido de `lti-config.json`
4. Reemplaza `tu-dominio.com` con tu dominio real
5. Guarda y activa la clave
6. Ve a **Settings** → **Apps** → **View App Configurations**
7. Haz clic en **+ App** y selecciona la clave que creaste

### Método 2: Usando XML (LTI 1.1)

1. En Canvas, ve a **Settings** → **Apps**
2. Haz clic en **+ App**
3. Selecciona **Configuration Type**: Paste XML
4. Pega el contenido de `lti-config.xml`
5. Reemplaza `tu-dominio.com` con tu dominio real
6. **Consumer Key**: Genera una clave única
7. **Shared Secret**: Genera un secreto único
8. Instala la aplicación

### Actualizar configuración en el código

Para LTI 1.3, necesitarás generar un par de claves JWK:

```bash
# Generar clave privada
openssl genrsa -out private.key 2048

# Generar clave pública
openssl rsa -in private.key -pubout -out public.key

# Convertir a JWK (puedes usar herramientas online como https://irrte.ch/jwt-js-decode/pem2jwk.html)
```

Actualiza `lti-config.json` con tu clave pública JWK.

## 💡 Uso

### Para Estudiantes

1. Accede a tu curso en Canvas
2. Haz clic en "Chatbot con IA" en la navegación del curso
3. Escribe tu pregunta en el campo de texto
4. El chatbot responderá basándose en el contexto del curso

### Para Instructores

Los instructores pueden cargar contexto del curso para mejorar las respuestas del chatbot:

```bash
# Usando la API
curl -X POST https://tu-dominio.com/chatbot/context \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": "12345",
    "context_type": "syllabus",
    "content": "Contenido del syllabus aquí...",
    "metadata": {
      "title": "Syllabus del curso",
      "date": "2025-01-01"
    }
  }'
```

O implementa una interfaz de administración (próximamente).

## 🏗️ Estructura del Proyecto

```
lti-test/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatbotController.php  # Lógica del chatbot
│   │   └── LtiController.php      # Manejo de LTI launch
│   └── Models/
│       ├── Conversation.php       # Modelo de conversaciones
│       ├── Message.php            # Modelo de mensajes
│       └── CourseContext.php      # Modelo de contexto
├── database/
│   └── migrations/
│       └── *_create_chatbot_tables.php
├── resources/
│   └── js/
│       └── pages/
│           └── chatbot.tsx        # Componente React principal
├── routes/
│   └── web.php                    # Rutas de la aplicación
├── lti-config.json               # Configuración LTI 1.3
├── lti-config.xml                # Configuración LTI 1.1
└── README.md
```

## 🔐 Seguridad

- Las claves de API deben mantenerse seguras en el archivo `.env`
- Nunca expongas las claves en el código fuente
- Usa HTTPS en producción
- Valida y sanitiza todas las entradas de usuario
- Implementa autenticación LTI adecuada

## 🐛 Resolución de Problemas

### El chatbot no responde

1. Verifica que `OPENAI_API_KEY` esté correctamente configurada
2. Revisa los logs: `tail -f storage/logs/laravel.log`
3. Verifica tu saldo de OpenAI

### Error de LTI Launch

1. Verifica que las URLs en la configuración coincidan con tu dominio
2. Asegúrate de que Canvas pueda acceder a tu servidor
3. Revisa los logs de Canvas y de tu aplicación

### Error de base de datos

1. Ejecuta las migraciones: `php artisan migrate`
2. Verifica la conexión a la base de datos en `.env`

## 📚 Recursos Adicionales

- [Canvas LTI Documentation](https://canvas.instructure.com/doc/api/file.tools_intro.html)
- [LTI 1.3 Specification](https://www.imsglobal.org/spec/lti/v1p3/)
- [OpenAI API Documentation](https://platform.openai.com/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)

## 🤝 Contribución

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT.

## 👥 Autores

Tu nombre - [@tu-usuario](https://github.com/tu-usuario)

## 🙏 Agradecimientos

- Canvas LMS por su excelente documentación LTI
- OpenAI por proporcionar APIs de IA accesibles
- La comunidad de Laravel por el framework robusto
