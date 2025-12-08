# ✅ Checklist de Configuración - Chatbot con IA para Canvas LMS

## 📋 Pre-requisitos

- [ ] PHP 8.2+ instalado
- [ ] Composer instalado
- [ ] Node.js 18+ instalado
- [ ] Cuenta de OpenAI con API Key
- [ ] Acceso a Canvas LMS como administrador (para instalar la herramienta)

## 🔧 Configuración Inicial

### 1. Instalación de Dependencias

- [ ] Ejecutar `composer install`
- [ ] Ejecutar `npm install`

### 2. Configuración de Entorno

- [ ] Copiar `.env.example` a `.env`
- [ ] Generar application key: `php artisan key:generate`
- [ ] Configurar `OPENAI_API_KEY` en `.env`
- [ ] Configurar `OPENAI_MODEL` (por defecto: gpt-4o-mini)
- [ ] Configurar `APP_URL` con tu dominio

### 3. Base de Datos

- [ ] Crear base de datos (SQLite por defecto, o MySQL/PostgreSQL)
- [ ] Configurar conexión en `.env`
- [ ] Ejecutar migraciones: `php artisan migrate`

### 4. Assets Frontend

- [ ] Compilar assets: `npm run build` (o `npm run dev` para desarrollo)

## 🧪 Pruebas Locales

- [ ] Iniciar servidor: `php artisan serve`
- [ ] Iniciar Vite (en otra terminal): `npm run dev`
- [ ] Crear ruta de prueba según `TESTING.md`
- [ ] Visitar `http://localhost:8000/test-chatbot`
- [ ] Verificar que el chat carga correctamente
- [ ] Cargar contexto de prueba con cURL o Postman
- [ ] Enviar mensaje de prueba
- [ ] Verificar respuesta del chatbot

## 🔗 Integración con Canvas

### Configuración LTI

- [ ] Actualizar URLs en `lti-config.json` o `lti-config.xml`
- [ ] Reemplazar `tu-dominio.com` con tu dominio real
- [ ] Si usas ngrok para pruebas, actualizar con URL de ngrok

### En Canvas LMS (Método JSON - LTI 1.3)

- [ ] Ir a Admin → Developer Keys
- [ ] Crear nueva LTI Key
- [ ] Pegar contenido de `lti-config.json`
- [ ] Generar y configurar JWK (ver README)
- [ ] Guardar y activar la clave
- [ ] Ir a Settings → Apps
- [ ] Instalar app usando Client ID

### En Canvas LMS (Método XML - LTI 1.1)

- [ ] Ir a Settings → Apps
- [ ] Añadir nueva app
- [ ] Seleccionar "Paste XML"
- [ ] Pegar contenido de `lti-config.xml`
- [ ] Generar Consumer Key y Shared Secret
- [ ] Guardar configuración

### Verificación

- [ ] Abrir un curso en Canvas
- [ ] Verificar que "Chatbot con IA" aparece en navegación
- [ ] Hacer clic en la herramienta
- [ ] Verificar que carga correctamente
- [ ] Enviar mensaje de prueba

## 🎯 Cargar Contexto del Curso

### Manualmente (via API)

- [ ] Preparar contenido del curso (syllabus, anuncios, etc.)
- [ ] Usar endpoint `/chatbot/context` con cURL o Postman
- [ ] Verificar que se guardó: `php artisan tinker → CourseContext::all()`

### Automáticamente (Canvas API)

- [ ] Configurar `CANVAS_API_URL` y `CANVAS_API_TOKEN` en `.env`
- [ ] Implementar script para importar contenido (futuro)

## 🚀 Despliegue a Producción

### Pre-despliegue

- [ ] Cambiar `APP_ENV=production` en `.env`
- [ ] Cambiar `APP_DEBUG=false` en `.env`
- [ ] Configurar base de datos de producción
- [ ] Obtener certificado SSL (Let's Encrypt)

### Servidor

- [ ] Configurar servidor web (Nginx/Apache)
- [ ] Configurar PHP-FPM
- [ ] Subir código al servidor
- [ ] Instalar dependencias: `composer install --optimize-autoloader --no-dev`
- [ ] Compilar assets: `npm run build`
- [ ] Ejecutar migraciones: `php artisan migrate --force`
- [ ] Optimizar: `php artisan config:cache && php artisan route:cache`

### Configuración Avanzada

- [ ] Configurar Supervisor para workers (ver DEPLOYMENT.md)
- [ ] Configurar Cron para schedule
- [ ] Configurar Redis para cache/sessions (opcional)
- [ ] Configurar backups automáticos
- [ ] Configurar monitoreo (Sentry, New Relic, etc.)

### Actualizar Canvas

- [ ] Actualizar URLs en configuración LTI con dominio de producción
- [ ] Reinstalar/actualizar la herramienta en Canvas
- [ ] Probar en curso de prueba primero

## 📊 Post-Despliegue

### Verificación

- [ ] Verificar que HTTPS funciona
- [ ] Probar LTI launch desde Canvas
- [ ] Enviar mensaje de prueba
- [ ] Verificar logs: `tail -f storage/logs/laravel.log`
- [ ] Verificar uso de OpenAI API
- [ ] Monitorear costos

### Optimización

- [ ] Configurar rate limiting
- [ ] Implementar cache de respuestas comunes
- [ ] Optimizar consultas a base de datos
- [ ] Implementar CDN para assets (opcional)

### Documentación para Usuarios

- [ ] Crear guía para estudiantes
- [ ] Crear guía para instructores
- [ ] Documentar cómo cargar contexto
- [ ] Compartir mejores prácticas

## 🆘 Solución de Problemas

Si encuentras problemas:

1. [ ] Revisar `TESTING.md` para problemas comunes
2. [ ] Revisar `DEPLOYMENT.md` para problemas de producción
3. [ ] Verificar logs: `storage/logs/laravel.log`
4. [ ] Verificar configuración: `php artisan config:show openai`
5. [ ] Verificar migraciones: `php artisan migrate:status`
6. [ ] Limpiar cache: `php artisan cache:clear && php artisan config:clear`

## 📈 Mejoras Futuras (Opcional)

- [ ] Panel de administración para instructores
- [ ] Importación automática de contenido desde Canvas
- [ ] Análisis de preguntas frecuentes
- [ ] Soporte multilenguaje
- [ ] Integración con Canvas Assignments
- [ ] Sistema de feedback/rating de respuestas
- [ ] Dashboard de métricas y uso
- [ ] Moderación de contenido
- [ ] Memoria de conversación mejorada
- [ ] Soporte para archivos/imágenes

## ✅ Estado del Proyecto

- [x] Backend implementado (Laravel + OpenAI)
- [x] Frontend implementado (React + TypeScript)
- [x] Base de datos diseñada y migrada
- [x] Integración LTI configurada
- [x] Documentación completa
- [ ] Pruebas unitarias
- [ ] Pruebas de integración
- [ ] CI/CD pipeline
- [ ] Panel de administración
- [ ] Importación automática de Canvas

---

**Próximo paso:** Seguir las instrucciones en `TESTING.md` para probar localmente.
