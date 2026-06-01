# sig-backend — Laravel 10 API REST

Backend de Fuerza Territorial. Gestiona autenticación JWT, capas GIS, tabla dinámica, usuarios por rol, dashboard de semáforo e integración con el microservicio WhatsApp.

## Stack

| Componente | Tecnología |
|------------|-----------|
| Framework  | Laravel 10 |
| Auth       | tymon/jwt-auth (Bearer token) |
| Base de datos | MySQL 8.0 |
| Almacenamiento | Laravel Storage (local / S3) |
| GIS        | GeoJSON nativo en columnas `LONGTEXT` |

## Configuración

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan storage:link
php artisan serve
# API en http://localhost:8000/api
```

Variables importantes en `.env`:

```env
DB_DATABASE=sig
DB_USERNAME=root
DB_PASSWORD=
WHATSAPP_SERVICE_URL=http://localhost:3001
FILESYSTEM_DISK=local
JWT_SECRET=   # generado por jwt:secret
```

## Esquema de base de datos (tablas clave)

| Tabla | Descripción |
|-------|-------------|
| `users` | Usuarios con rol, cve_ent, cve_mun, cve_seccion, national_admin_id, semaforo_config |
| `layers` | Capas GIS — GeoJSON + metadatos + campo `goal` (meta de captura) |
| `layer_assignments` | Pivot: qué capas puede ver cada national_admin |
| `table_configs` | Configuraciones de tabla dinámica (lista de campos JSON) |
| `table_data` | Registros capturados vía web o app móvil |
| `geo_states / geo_municipalities / geo_sections` | Geometrías INEGI importadas |

## Sistema de roles

```
admin                → acceso total
national_admin       → ve solo capas asignadas en layer_assignments
estatal_admin        → scoped a cve_ent
municipal_admin      → scoped a cve_mun
seccion_admin        → scoped a cve_seccion; crea field_workforce
field_workforce      → accede solo a /table (config + data); usa app móvil
```

Cada rol solo puede crear el rol directamente inferior (`CREATABLE_ROLE` en `UserController`). La meta de sección (`goal`) se lee desde propiedades GeoJSON y se persiste en la tabla `layers`.

## Endpoints principales

### Auth
| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/login` | Acepta `username` o `email` + `password`; retorna `access_token` |
| GET  | `/api/me` | Valida token activo |
| POST | `/api/logout` | Invalida token |

### Tabla dinámica (web + app móvil)
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET  | `/api/table/configs` | Lista configuraciones accesibles al usuario |
| GET  | `/api/table/config/{id}` | Config completa con definición de campos |
| POST | `/api/table/{id}/data` | Envía registro `{ data:{}, lat, lng }` |
| POST | `/api/table/{id}/upload-media` | Sube foto/video → retorna `{ url }` |

### Usuarios
| Método | Ruta | Roles |
|--------|------|-------|
| GET    | `/api/users` | admin…seccion_admin |
| POST   | `/api/users` | admin…seccion_admin |
| PUT    | `/api/users/{id}` | admin…seccion_admin |
| DELETE | `/api/users/{id}` | admin…seccion_admin |
| GET    | `/api/users/national-admins` | admin |

### Capas y asignaciones
| Método | Ruta | Roles |
|--------|------|-------|
| GET    | `/api/layers/{id}/assignments` | admin |
| POST   | `/api/layers/{id}/assignments` | admin |
| DELETE | `/api/layers/{id}/assignments/{userId}` | admin |

### Dashboard y semáforo
| Método | Ruta | Descripción |
|--------|------|-------------|
| GET  | `/api/dashboard/stats` | Semáforo por rol — incluye thresholds configurados |
| GET  | `/api/dashboard/rankings` | Rankings de captura por sección |
| PUT  | `/api/dashboard/semaforo-config` | Ajusta % amarillo/verde (solo national_admin) |

### WhatsApp
| Método | Ruta | Roles |
|--------|------|-------|
| GET    | `/api/whatsapp/status` | admin, municipal_admin |
| POST   | `/api/whatsapp/sessions` | admin, municipal_admin |
| DELETE | `/api/whatsapp/sessions/{id}` | admin, municipal_admin |
| POST   | `/api/whatsapp/campaign/{config}` | admin, municipal_admin |

## Tests

```bash
php artisan test
```
