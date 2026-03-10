# Matriz de Pruebas API

## Convenciones
- Roles: `bibliotecario`, `docente`, `estudiante`.
- Todas las rutas protegidas requieren `Bearer token` de Sanctum.

## 1) Autenticacion

| ID | Endpoint | Metodo | Rol | Precondicion | Resultado esperado |
|---|---|---|---|---|---|
| AUTH-01 | `/api/v1/login` | POST | Publico | Credenciales validas | `200`, devuelve `access_token`, `token_type`, `user` |
| AUTH-02 | `/api/v1/login` | POST | Publico | Credenciales invalidas | `422`, mensaje `Invalid credentials` |
| AUTH-03 | `/api/v1/logout` | POST | Autenticado | Token valido | `200`, elimina tokens, mensaje de exito |
| AUTH-04 | `/api/v1/profile` | GET | Autenticado | Token valido | `200`, devuelve usuario autenticado |
| AUTH-05 | `/api/v1/profile` | GET | Publico | Sin token | `401` |

## 2) Libros

| ID | Endpoint | Metodo | Rol | Precondicion | Resultado esperado |
|---|---|---|---|---|---|
| BOOK-01 | `/api/v1/books` | GET | Todos los roles | Token valido | `200`, listado paginado |
| BOOK-02 | `/api/v1/books/{book}` | GET | Todos los roles | Libro existe | `200`, detalle del libro |
| BOOK-03 | `/api/v1/books` | POST | `bibliotecario` | Payload valido | `201`, libro creado |
| BOOK-04 | `/api/v1/books` | POST | `docente`/`estudiante` | Payload valido | `403` |
| BOOK-05 | `/api/v1/books/{book}` | PUT | `bibliotecario` | Libro existe, payload valido | `200`, libro actualizado |
| BOOK-06 | `/api/v1/books/{book}` | PUT | `docente`/`estudiante` | Libro existe | `403` |
| BOOK-07 | `/api/v1/books/{book}` | DELETE | `bibliotecario` | Libro existe | `204`, libro eliminado |
| BOOK-08 | `/api/v1/books/{book}` | DELETE | `docente`/`estudiante` | Libro existe | `403` |

## 3) Prestamos

| ID | Endpoint | Metodo | Rol | Precondicion | Resultado esperado |
|---|---|---|---|---|---|
| LOAN-01 | `/api/v1/loans` | GET | Todos los roles | Token valido | `200`, historial paginado |
| LOAN-02 | `/api/v1/loans` | POST | `docente`/`estudiante` | Libro disponible | `201`, crea prestamo y resta disponibilidad |
| LOAN-03 | `/api/v1/loans` | POST | `bibliotecario` | Libro disponible | `403` |
| LOAN-04 | `/api/v1/loans` | POST | `docente`/`estudiante` | Libro no disponible | `422`, mensaje `Book is not available` |
| LOAN-05 | `/api/v1/loans/{loan}/return` | POST | `docente`/`estudiante` | Prestamo activo | `200`, marca devolucion y suma disponibilidad |
| LOAN-06 | `/api/v1/loans/{loan}/return` | POST | `bibliotecario` | Prestamo activo | `403` |
| LOAN-07 | `/api/v1/loans/{loan}/return` | POST | `docente`/`estudiante` | Prestamo ya devuelto | `422`, mensaje `Loan already returned` |
