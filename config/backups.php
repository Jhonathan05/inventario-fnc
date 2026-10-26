<?php

// Backups de la base de datos del inventario.
//
// El volcado se genera 100 % en PHP (ifsnop/mysqldump-php, sin depender del
// binario mysqldump del contenedor) y se guarda en un disco PRIVADO bajo
// storage/app: nunca se sirve por URL pública, sólo a través del
// controlador con permiso `configuracion.editar`.

return [

    // Disco de almacenamiento (local = storage/app, fuera de public/).
    'disco' => env('BACKUPS_DISCO', 'local'),

    // Subdirectorio dentro del disco.
    'directorio' => env('BACKUPS_DIRECTORIO', 'backups'),

    // Prefijo del nombre de archivo: {prefijo}_backup_{Y_m_d_His}.sql.gz
    'prefijo' => env('BACKUPS_PREFIJO', 'inventario-fnc'),

    // Retención: cuántos backups se conservan (los más recientes).
    'retencion' => (int) env('BACKUPS_RETENCION', 15),

    // Backup programado diario (02:00) vía `backups:crear`.
    'programado' => env('BACKUPS_PROGRAMADO', false),

    // Tamaño máximo del volcado que se puede subir para restaurar (KB).
    // Alineado con los 64M de nginx (client_max_body_size) y PHP
    // (upload_max_filesize / post_max_size) del despliegue.
    'max_subida_kb' => (int) env('BACKUPS_MAX_SUBIDA_KB', 65536),

];
