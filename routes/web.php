<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\Login\LoginController;
use App\Http\Controllers\Controles\ControlController;
use App\Http\Controllers\Backend\Roles\RolesController;
use App\Http\Controllers\Backend\Roles\PermisoController;
use App\Http\Controllers\Backend\Perfil\PerfilController;
use App\Http\Controllers\Backend\Configuracion\ConfiguracionController;
use App\Http\Controllers\Backend\Registro\RegistroController;



use App\Http\Controllers\Backend\Dashboard\DashboardController;


// --- LOGIN ---

Route::get('/', [LoginController::class,'index'])->name('login');

Route::post('admin/login', [LoginController::class, 'login']);
Route::post('admin/logout', [LoginController::class, 'logout'])->name('admin.logout');

// --- CONTROL WEB ---

Route::get('/panel', [ControlController::class,'indexRedireccionamiento'])->name('admin.panel');

// --- ROLES ---

Route::get('/admin/roles/index', [RolesController::class,'index'])->name('admin.roles.index');
Route::get('/admin/roles/tabla', [RolesController::class,'tablaRoles']);
Route::get('/admin/roles/lista/permisos/{id}', [RolesController::class,'vistaPermisos']);
Route::get('/admin/roles/permisos/tabla/{id}', [RolesController::class,'tablaRolesPermisos']);
Route::post('/admin/roles/permiso/borrar', [RolesController::class, 'borrarPermiso']);
Route::post('/admin/roles/permiso/agregar', [RolesController::class, 'agregarPermiso']);
Route::get('/admin/roles/permisos/lista', [RolesController::class,'listaTodosPermisos']);
Route::get('/admin/roles/permisos-todos/tabla', [RolesController::class,'tablaTodosPermisos']);
Route::post('/admin/roles/borrar-global', [RolesController::class, 'borrarRolGlobal']);

// --- PERMISOS A USUARIOS ---

Route::get('/admin/permisos/index', [PermisoController::class,'index'])->name('admin.permisos.index');
Route::get('/admin/permisos/tabla', [PermisoController::class,'tablaUsuarios']);
Route::post('/admin/permisos/nuevo-usuario', [PermisoController::class, 'nuevoUsuario']);
Route::post('/admin/permisos/info-usuario', [PermisoController::class, 'infoUsuario']);
Route::post('/admin/permisos/editar-usuario', [PermisoController::class, 'editarUsuario']);
Route::post('/admin/permisos/nuevo-rol', [PermisoController::class, 'nuevoRol']);
Route::post('/admin/permisos/extra-nuevo', [PermisoController::class, 'nuevoPermisoExtra']);
Route::post('/admin/permisos/extra-borrar', [PermisoController::class, 'borrarPermisoGlobal']);

// --- PERFIL DE USUARIO ---
Route::get('/admin/editar-perfil/index', [PerfilController::class,'indexEditarPerfil'])->name('admin.perfil');
Route::post('/admin/editar-perfil/actualizar', [PerfilController::class, 'editarUsuario']);

// --- SIN PERMISOS VISTA 403 ---
Route::get('sin-permisos', [ControlController::class,'indexSinPermiso'])->name('no.permisos.index');



// --- LIBRO ---
Route::get('/admin/libros/index', [ConfiguracionController::class,'indexLibros'])->name('admin.libros.index');
Route::get('/admin/libros/tabla', [ConfiguracionController::class,'tablaLibros']);
Route::post('/admin/libros/nuevo', [ConfiguracionController::class, 'nuevoLibro']);
Route::post('/admin/libros/informacion', [ConfiguracionController::class, 'informacionLibro']);
Route::post('/admin/libros/editar', [ConfiguracionController::class, 'editarLibro']);


// --- REGISTRO ---
Route::get('/admin/registro/index', [RegistroController::class,'indexRegistro'])->name('admin.registro.index');
Route::post('/admin/registro/buscar/correlativo', [RegistroController::class, 'buscarCorrelativo']);
Route::post('/admin/registro/nuevo', [RegistroController::class, 'nuevoRegistro']);

// --- TABLAS DE REGISTROS ---
Route::get('/admin/librosdetalle/index', [RegistroController::class,'indexLibrosDetalle'])->name('admin.libros.detalle.index');
Route::get('/admin/librosdetalle/tabla/{id}', [RegistroController::class,'tablaLibrosDetalle']);
// - Info Fallecido
Route::post('/admin/librosdetalle/info/fallecido', [RegistroController::class, 'infoFallecido']);
// - Borrar fallecido
Route::post('/admin/librosdetalle/borrar/fallecido', [RegistroController::class, 'borrarFallecido']);
// - actualizar
Route::post('/admin/librosdetalle/actualizar/datos', [RegistroController::class, 'actualizarDatos']);
// - Nuevo registro a uno existente
Route::post('/admin/librosdetalle/extra/fallecido', [RegistroController::class, 'agregarExtraFallecido']);


// --- COBROS ---
Route::get('/admin/cobros/index/{id}', [RegistroController::class,'indexCobros']);
Route::get('/admin/cobros/tabla/{id}', [RegistroController::class,'tablaCobros']);
Route::post('/admin/cobros/borrar', [RegistroController::class, 'borrarCobro']);
Route::post('/admin/cobros/nuevoRegistro', [RegistroController::class, 'nuevoRegistroCobro']);



// --- BUSCADOR ---
Route::get('/admin/buscador/index', [DashboardController::class,'vistaBuscador'])->name('admin.buscador.index');
Route::post('/admin/buscar/persona',  [DashboardController::class,'buscadorPersona']);

// - PERSONA BUSCADA
Route::get('/admin/buscador/persona/{id}', [DashboardController::class,'vistaPersonaBuscada']);
Route::get('/admin/buscador/persona/tabla/{id}', [DashboardController::class,'tablaPersonaBuscada']);

