# 4V GYM API

API REST para gestión de actividades y reservas del gimnasio 4V GYM.

## Tecnologías

- **PHP** 8.2+
- **Symfony** 7.3
- **Doctrine ORM** 3.0
- **MariaDB** 10.4

## Instalación

```bash
# Clonar repositorio
git clone https://github.com/SrAlvarado/Examen2EvDw.git
cd examen2evDW

# Instalar dependencias
composer install

# Configurar base de datos en .env.local
DATABASE_URL="mysql://root:@127.0.0.1:3306/gym4v?serverVersion=10.4.32-MariaDB&charset=utf8mb4"

# Crear base de datos y ejecutar migraciones
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Cargar datos de prueba
php bin/console doctrine:fixtures:load
```

## Ejecutar

```bash
php -S localhost:8000 -t public
```

## Endpoints

### GET /activities

Lista actividades con filtros opcionales.

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `onlyfree` | boolean | Solo actividades con plazas (default: true) |
| `type` | string | Filtrar por tipo: BodyPump, Spinning, Core |
| `page` | int | Página (default: 1) |
| `page_size` | int | Elementos por página (default: 10) |
| `sort` | string | Ordenar por: date |
| `order` | string | asc o desc (default: desc) |

**Ejemplo:**
```
GET http://localhost:8000/activities?type=Spinning&onlyfree=true
```

---

### GET /clients/{id}

Obtiene información de un cliente.

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `with_bookings` | boolean | Incluir reservas activas |
| `with_statistics` | boolean | Incluir estadísticas por año |

**Ejemplo:**
```
GET http://localhost:8000/clients/4?with_bookings=true&with_statistics=true
```

---

### POST /bookings

Crea una nueva reserva.

**Body:**
```json
{
  "activity_id": 7,
  "client_id": 4
}
```

**Validaciones:**
- Actividad y cliente deben existir
- Actividad debe tener plazas disponibles
- Cliente no puede tener reserva duplicada
- Usuarios `standard`: máximo 2 reservas por semana (lunes-domingo)

---

## Datos de Prueba

Después de ejecutar fixtures:

**Clientes:**
| ID | Nombre | Tipo |
|----|--------|------|
| 4 | Miguel Goyena | premium |
| 5 | Ana García | standard |
| 6 | Carlos López | standard |

**Actividades:**
| ID | Tipo | Max | Fecha |
|----|------|-----|-------|
| 7 | BodyPump | 25 | +1 día |
| 8 | Spinning | 20 | +2 días |
| 9 | Core | 15 | +3 días |
| 10 | BodyPump | 2 | +4 días (llena) |

---

## Testing con cURL

```bash
# Listar actividades
curl http://localhost:8000/activities

# Filtrar por tipo
curl "http://localhost:8000/activities?type=Spinning"

# Cliente con estadísticas
curl "http://localhost:8000/clients/4?with_statistics=true"

# Crear reserva (Windows - usar archivo)
echo {"activity_id": 9, "client_id": 4} > booking.json
curl -X POST -H "Content-Type: application/json" -d @booking.json http://localhost:8000/bookings
```

## Testing Automatizado (PHPUnit)

El proyecto incluye una suite de tests automatizados para validar la lógica y los endpoints.

```bash
# Ejecutar todos los tests
php bin/phpunit
```


---

## Códigos de Error

| Código | Descripción |
|--------|-------------|
| 21 | activity_id requerido |
| 22 | client_id requerido |
| 31 | Actividad no encontrada |
| 32 | Cliente no encontrado |
| 41 | Actividad llena |
| 42 | Reserva duplicada |
| 43 | Límite semanal excedido (standard) |
| 44 | Cliente no encontrado |

---

## Estructura del Proyecto

```
src/
├── Controller/
│   ├── ActivityController.php
│   ├── BookingController.php
│   └── ClientController.php
├── Entity/
│   ├── Activity.php
│   ├── Booking.php
│   ├── Client.php
│   └── Song.php
├── Repository/
│   ├── ActivityRepository.php
│   ├── BookingRepository.php
│   ├── ClientRepository.php
│   └── SongRepository.php
└── DataFixtures/
    └── AppFixtures.php
```

## Autor

Markel Alvarado - 2º DAM
