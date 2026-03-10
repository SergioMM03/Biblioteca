# Matriz de Pruebas API

## Convenciones
- Roles soportados: `bibliotecario`, `docente`, `estudiante`.
- Las rutas protegidas requieren `Bearer token` emitido por Sanctum.
- `Cobertura` indica si el escenario ya cuenta con prueba automatizada en `tests/Feature/ApiMatrixTest.php`.

## 1. Autenticacion

| ID | Endpoint | Metodo | Escenario | Rol | Precondicion | Resultado esperado | Cobertura |
|---|---|---|---|---|---|---|---|
| AUTH-01 | `/api/v1/login` | POST | Iniciar sesion correctamente | Publico | Credenciales validas | `200`, devuelve `access_token`, `token_type`, `user` | Automatizada |
| AUTH-02 | `/api/v1/login` | POST | Rechazar credenciales invalidas | Publico | Email o password incorrectos | `422`, mensaje `Invalid credentials` | Pendiente |
| AUTH-03 | `/api/v1/logout` | POST | Cerrar sesion | Autenticado | Token valido | `200`, elimina tokens, mensaje de exito | Automatizada |
| AUTH-04 | `/api/v1/profile` | GET | Obtener perfil autenticado | Autenticado | Token valido | `200`, devuelve usuario autenticado | Automatizada |
| AUTH-05 | `/api/v1/profile` | GET | Rechazar acceso sin token | Publico | Sin token | `401` | Automatizada |

## 2. Libros

| ID | Endpoint | Metodo | Escenario | Rol | Precondicion | Resultado esperado | Cobertura |
|---|---|---|---|---|---|---|---|
| BOOK-01 | `/api/v1/books` | GET | Listar libros | Todos los roles autenticados | Existen libros registrados | `200`, listado paginado o coleccion de libros | Automatizada |
| BOOK-02 | `/api/v1/books/{book}` | GET | Ver detalle de libro | Todos los roles autenticados | El libro existe | `200`, devuelve detalle del libro | Automatizada |
| BOOK-03 | `/api/v1/books` | POST | Crear libro | `bibliotecario` | Payload valido | `201`, libro creado | Automatizada |
| BOOK-04 | `/api/v1/books` | POST | Rechazar creacion sin permisos | `docente` / `estudiante` | Payload valido | `403` | Automatizada |
| BOOK-05 | `/api/v1/books/{book}` | PUT | Actualizar libro | `bibliotecario` | Libro existente, payload valido | `200`, libro actualizado | Automatizada |
| BOOK-06 | `/api/v1/books/{book}` | PUT | Rechazar actualizacion sin permisos | `docente` / `estudiante` | Libro existente | `403` | Automatizada |
| BOOK-07 | `/api/v1/books/{book}` | DELETE | Eliminar libro | `bibliotecario` | Libro existente | `204`, libro eliminado | Automatizada |
| BOOK-08 | `/api/v1/books/{book}` | DELETE | Rechazar eliminacion sin permisos | `docente` / `estudiante` | Libro existente | `403` | Automatizada |

## 3. Prestamos

| ID | Endpoint | Metodo | Escenario | Rol | Precondicion | Resultado esperado | Cobertura |
|---|---|---|---|---|---|---|---|
| LOAN-01 | `/api/v1/loans` | GET | Consultar historial | Todos los roles autenticados | Existen prestamos registrados | `200`, historial paginado con libro relacionado | Automatizada |
| LOAN-02 | `/api/v1/loans` | POST | Prestar libro | `docente` / `estudiante` | Libro disponible | `201`, crea prestamo y reduce disponibilidad | Automatizada |
| LOAN-03 | `/api/v1/loans` | POST | Rechazar prestamo por rol | `bibliotecario` | Libro disponible | `403` | Automatizada |
| LOAN-04 | `/api/v1/loans` | POST | Rechazar prestamo sin existencias | `docente` / `estudiante` | Libro no disponible | `422`, mensaje `Book is not available` | Automatizada |
| LOAN-05 | `/api/v1/loans/{loan}/return` | POST | Devolver libro | `docente` / `estudiante` | Prestamo activo | `200`, marca devolucion y aumenta disponibilidad | Automatizada |
| LOAN-06 | `/api/v1/loans/{loan}/return` | POST | Rechazar devolucion por rol | `bibliotecario` | Prestamo activo | `403` | Automatizada |
| LOAN-07 | `/api/v1/loans/{loan}/return` | POST | Rechazar doble devolucion | `docente` / `estudiante` | Prestamo ya devuelto | `422`, mensaje `Loan already returned` | Automatizada |

## Resumen por endpoint solicitado

| Modulo | Endpoint | ID principal |
|---|---|---|
| Autenticacion | Iniciar sesion | `AUTH-01` |
| Autenticacion | Cerrar sesion | `AUTH-03` |
| Autenticacion | Perfil | `AUTH-04` |
| Libros | Listar libros | `BOOK-01` |
| Libros | Detalle libro | `BOOK-02` |
| Libros | Crear libro | `BOOK-03` |
| Libros | Actualizar libro | `BOOK-05` |
| Libros | Eliminar libro | `BOOK-07` |
| Prestamos | Prestar | `LOAN-02` |
| Prestamos | Devolver | `LOAN-05` |
| Prestamos | Historial | `LOAN-01` |
