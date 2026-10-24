<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CatalogosCmdbSeeder — opciones de las listas desplegables de los dos
 * archivos fuente (Fase 0, contrato docs/cmdb_contrato_columnas.md):
 *
 *  - docs/Formatos/CMDB USUARIO FINAL.xlsx  → hoja "Listas De Validación Equipos"
 *  - docs/Formatos/Novedad activo.xlsx      → hoja "Hoja3" (FE-BS-F-0069)
 *
 * Idempotente y NO destructivo (Lote B3): upsert por codigo (catálogos) y
 * grupo+valor (diccionarios). Las ediciones hechas desde el módulo Catálogos
 * se RESPETAN (no se desactivan valores agregados por el usuario); sólo se
 * retiran los códigos legacy equivocados que sembró la migración original
 * (estados 'disponible'/'prestamo'/'reparacion'/'asignacion_responsable'/
 * 'procesar_baja' y razones 'temporal'/'obsoleto'), que no existen en el
 * catálogo real del archivo.
 */
class CatalogosCmdbSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Estados operativos oficiales del CMDB: [codigo, nombre, descripcion]. */
    private array $estados = [
        ['almacenado', 'ALMACENADO', 'TODO ACTIVO QUE SE ENCUENTRA RESERVADO EN UNA BODEGA.'],
        ['transito', 'TRÁNSITO', 'TODO ACTIVO QUE SE ENCUENTRA EN CIRCULACIÓN.'],
        ['en_operacion', 'EN OPERACIÓN', 'TODO ACTIVO QUE SE ENCUENTRA EN USO POR PARTE DE UN COLABORADOR.'],
        ['baja', 'BAJA', 'TODO ACTIVO QUE SE ENCUENTRA EN PROCESO PARA DISPOCISIÓN FINAL.'],
        ['hurto', 'HURTO', 'TODO ACTIVO HURTADO.'],
        ['pendiente_estado', 'PENDIENTE ESTADO', 'ACTIVO CON ESTADO PENDIENTE DE VERIFICACIÓN POR EL ANALISTA TIC.'],
    ];

    /** Razones del estado oficiales del CMDB: [codigo, nombre, descripcion]. */
    private array $razones = [
        ['disponible', 'DISPONIBLE', 'TODO ACTIVO QUE SE ENCUENTRE DISPONIBLE NUEVO O USADO PARA ASIGNACIÓN A USUARIO FINAL.'],
        ['custodia', 'CUSTODIA', 'TODO ACTIVO QUE INGRESE A BODEGA ESTARÁ ALMACENADO 15 DÍAS HÁBILES, DESPUÉS DE ESTE TIEMPO SE REALIZARÁ EL CAMBIO DE ESTADO A DISPONIBLE PARA SU ASIGNACIÓN SEGÚN SE REQUIERA.'],
        ['alistamiento_soporte', 'ALISTAMIENTO / SOPORTE', 'TODOS AQUELLOS ACTIVOS QUE SE ENCUENTREN EN PROCESO DE ALISTAMIENTO, MANTENIMIENTO, SOPORTE O GARANTÍA'],
        ['renovacion', 'RENOVACIÓN', 'TODO ACTIVO NUEVO QUE INGRESA A BODEGA CON LA FINALIDAD DE REALIZAR LA ACTUALIZACIÓN DE LOS MISMOS.'],
        ['procesar_baja', 'PROCESAR BAJA', 'TODO ACTIVO QUE SE ENCUENTRA EN PROCESO DE BAJA.'],
        ['despacho', 'DESPACHO', 'TODO ACTIVO QUE SE ENCUENTRA EN MOVIMIENTO O TRASLADO DESDE LA OFICINA BOGOTÁ PARA LAS SEDES, COMITES E INSPECCIONES A NIVEL NACIONAL.'],
        ['retorno', 'RETORNO', 'TODO ACTIVO QUE SE ENCUENTRA EN MOVIMIENTO O TRASLADO DESDE SEDES,COMITÉS Ó INSPECCIONES A NIVEL NACIONAL QUE SEAN ENVIADOS A LA OFICINA BOGOTÁ.'],
        ['asignacion_responsable', 'ASIGNACIÓN RESPONSABLE', 'TODO ACTIVO QUE SE ENCUENTRE SIENDO UTILIZADO POR USUARIO CUYO CARGO CORRESPONDAN A (APRENDICES O TERCEROS), SERÁN ASIGNADOS A UN USUARIO RESPONSABLE.'],
        ['prestamo', 'PRÉSTAMO', 'TODO ACTIVO QUE SE ENCUENTRE ASIGNADO EN CALIDAD DE PRÉSTAMO POR UN TIEMPO NO MAYOR A 15 DÍAS CALENDARIO.'],
        ['hurto_perdida', 'HURTO / PERDIDA', 'TODO ACTIVO QUE SE ENCUENTRE EXTRAVIADO / HURTADO, EL CUAL DEBE CONTAR CON SU SOPORTE CORRESPONDIENTE (DENUNCIA).'],
        ['devuelto_a_proveedor', 'DEVUELTO A PROVEEDOR', 'TODO ACTIVO QUE SE ENCUENTRA EN PROCESO DE DEVOLUCIÓN AL PROVEEDOR POR CONCEPTOS DE RENTA Y/O LEASING'],
        ['donacion', 'DONACIÓN', 'EQUIPO DADO EN DONACIÓN POR PARTE DE FNC O ALM'],
        ['baja', 'BAJA', 'TODO ACTIVO AL CUAL SE LE REALIZA BAJA POR OBSOLESCENCIA O DAÑO.'],
    ];

    /** Estados de uso oficiales (cols 28-29 de las listas): [valor, descripcion]. */
    private array $estadoUso = [
        ['FUNCIONES PROPIAS', 'EQUIPOS ASIGNADOS DE MANERA EXCLUSIVA AL PERSONAL, OPERATIVO O DIRECTIVO PARA SUS TAREAS CORPORATIVAS DIARIAS. USO INDIVIDUAL Y TITULAR.'],
        ['SALA DE SISTEMAS / LABORATORIO', 'EQUIPOS EN ESPACIOS COMUNES DEDICADOS A CAPACITACIÓN, EDUCACIÓN O INVESTIGACIÓN. ACCESO ABIERTO A USUARIOS BAJO UN ENTORNO CONTROLADO.'],
        ['PRUEBAS', 'EQUIPOS RESERVADOS PARA ENSAYAR SOFTWARE, CONFIGURACIONES DE RED O ACTUALIZACIONES DE HARDWARE/OS. NO CONTIENEN INFORMACIÓN CRÍTICA.'],
        ['MULTIUSUARIO', 'DISPOSITIVOS CONFIGURADOS PARA SER OPERADOS POR MÚLTIPLES PERSONAS EN TURNOS O MOMENTOS DISTINTOS, USUALMENTE CON CUENTAS GENÉRICAS.'],
        ['TEMPORAL / PRÉSTAMO', 'EQUIPOS "COMODÍN" DE CONTINGENCIA. SE ASIGNAN DE FORMA PROVISIONAL POR SOPORTE TÉCNICO O PARA EVENTOS Y CONSULTORES EXTERNOS.'],
        ['OBSOLETO / ALMACENADO', 'EQUIPOS FUERA DE SERVICIO ACTIVO POR ANTIGÜEDAD, FALLAS IRREPARABLES O GUARDADOS EN BODEGA ESPERANDO SER ASIGNADOS, RECICLAJE O BAJA DEFINITIVA.'],
    ];

    /** Tipo equipo — lista desplegable oficial del archivo. */
    private array $tipo_equipo = [
        'EQUIPO ESCRITORIO',
        'EQUIPO PORTATIL',
        'EQUIPO TODO EN UNO',
        'TABLETA',
        'ESCANER',
        'MONITOR',
        'IMPRESORA TERMICA',
        'IMPRESORA MULTIFUNCIONAL',
        'IMPRESORA MONOFUNCIONAL',
    ];

    /** Empresa propietario — lista desplegable oficial del archivo. */
    private array $empresa_propietario = [
        'ALMACAFÉ',
        'FEDERACIÓN',
        'BANCOLOMBIA',
        'CAFECERT',
        'FONDO NACIONAL',
    ];

    /** Dependencia — lista desplegable oficial del archivo. */
    private array $dependencia = [
        'AGENCIA CUCUTA',
        'AGENCIA PASTO',
        'AGENCIA POPAYAN',
        'BUENCAFÉ',
        'CENICAFÉ',
        'COMITÉ ANTIOQUIA',
        'COMITÉ BOYACÁ',
        'COMITÉ CALDAS',
        'COMITÉ CAUCA',
        'COMITÉ CESAR GUAJIRA',
        'COMITÉ CUNDINAMARCA',
        'COMITÉ HUILA',
        'COMITÉ MAGDALENA',
        'COMITÉ NARIÑO',
        'COMITÉ NTE SANTANDER',
        'COMITÉ QUINDIO',
        'COMITÉ RISARALDA',
        'COMITÉ SANTANDER',
        'COMITÉ TOLIMA',
        'COMITÉ VALLE',
        'INSPECCION CAFETERA BUENAVENTURA',
        'INSPECCION CAFETERA CARTAGENA',
        'INSPECCION CAFETERA SANTA MARTA',
        'OFICINA BUENAVENTURA',
        'OFICINA CARTAGENA',
        'OFICINAS COORDINADORAS Y OTROS TERRITORIOS',
        'PLANTA TORREFACTORA',
        'SEDE BOGOTÁ',
        'SUCURSAL ARMENIA',
        'SUCURSAL BOGOTA (SOACHA)',
        'SUCURSAL BUCARAMANGA',
        'SUCURSAL BUGA',
        'SUCURSAL CELTA',
        'SUCURSAL IBAGUE',
        'SUCURSAL MANIZALES',
        'SUCURSAL MEDELLIN',
        'SUCURSAL NEIVA',
        'SUCURSAL PEREIRA',
        'SUCURSAL SANTA MARTA',
    ];

    /** Fuente recurso — lista desplegable oficial del archivo. */
    private array $fuente_recurso = [
        'FON1',
        'FON2',
        'FEPC',
        'ALMACAFÉ',
    ];

    /** Empresa funcionario — lista desplegable oficial del archivo. */
    private array $empresa_funcionario = [
        'ALMACAFÉ',
        'FEDERACIÓN',
        'TIVIT',
        'SAMTEL',
        'TECNOFILE',
    ];

    /** Administrado — lista desplegable oficial del archivo. */
    private array $administrado = [
        'ADMINISTRADO',
        'CONTROLADO',
    ];

    /** Procesador — lista desplegable oficial del archivo. */
    private array $procesador = [
        '11TH GEN INTEL(R) CORE(TM) I3-1115G4 @ 3.00GHZ',
        '11TH GEN INTEL(R) CORE(TM) I5-1135G7 @ 2.40GHZ',
        '11TH GEN INTEL(R) CORE(TM) I5-11400 @ 2.60GHZ',
        '11TH GEN INTEL(R) CORE(TM) I5-11400T @ 1.30GHZ',
        'No Aplica',
        '11TH GEN INTEL(R) CORE(TM) I5-1145G7 @ 2.60GHZ',
        '11TH GEN INTEL(R) CORE(TM) I5-1155G7 @ 2.50GHZ',
        '11TH GEN INTEL(R) CORE(TM) I7-11370H @ 3.30GHZ',
        '11TH GEN INTEL(R) CORE(TM) I7-1165G7 @ 2.80GHZ',
        '11TH GEN INTEL(R) CORE(TM) I7-11700 @ 2.50GHZ',
        '11TH GEN INTEL(R) CORE(TM) I7-11700T @ 1.40GHZ',
        '11TH GEN INTEL(R) CORE(TM) I7-11800H @ 2.30GHZ',
        '12TH GEN INTEL(R) CORE(TM) I5-1235U',
        '12TH GEN INTEL(R) CORE(TM) I5-1240P',
        '12TH GEN INTEL(R) CORE(TM) I5-12450H',
        '12TH GEN INTEL(R) CORE(TM) I5-12500',
        '12TH GEN INTEL(R) CORE(TM) I7-1250U',
        '12TH GEN INTEL(R) CORE(TM) I7-1255U',
        '12TH GEN INTEL(R) CORE(TM) I7-1260P',
        '12TH GEN INTEL(R) CORE(TM) I7-1260U',
        '12TH GEN INTEL(R) CORE(TM) I7-1265U',
        '12TH GEN INTEL(R) CORE(TM) I7-12700',
        '12TH GEN INTEL(R) CORE(TM) I7-12700H',
        '12TH GEN INTEL(R) CORE(TM) I7-12700T',
        '12TH GEN INTEL(R) CORE(TM) I7-1280P',
        '13TH GEN INTEL(R) CORE(TM) I5-1335U',
        '13TH GEN INTEL(R) CORE(TM) I5-13400T',
        '13TH GEN INTEL(R) CORE(TM) I5-13420H',
        '13TH GEN INTEL(R) CORE(TM) I5-13500',
        '13TH GEN INTEL(R) CORE(TM) I5-13500T',
        '13TH GEN INTEL(R) CORE(TM) I7-1355U',
        '13TH GEN INTEL(R) CORE(TM) I7-1360P',
        '13TH GEN INTEL(R) CORE(TM) I7-1365U',
        '13TH GEN INTEL(R) CORE(TM) I7-13700',
        'AMD A10-8700P RADEON R6, 10 COMPUTE CORES 4C+6G',
        'AMD A6-9210 RADEON R4, 5 COMPUTE CORES 2C+3G',
        'AMD A9-9425 RADEON R5, 5 COMPUTE CORES 2C+3G',
        'AMD ATHLON(TM) X2 DUAL CORE PROCESSOR 3250E',
        'AMD E1-1500 APU WITH RADEON(TM) HD GRAPHICS',
        'AMD E2-7110 APU WITH AMD RADEON R2 GRAPHICS',
        'AMD RYZEN 3 2200U WITH RADEON VEGA MOBILE GFX',
        'AMD RYZEN 3 3200U WITH RADEON VEGA MOBILE GFX',
        'AMD RYZEN 3 5300U WITH RADEON GRAPHICS',
        'AMD RYZEN 5 3450U WITH RADEON VEGA MOBILE GFX',
        'AMD RYZEN 5 3500U WITH RADEON VEGA MOBILE GFX',
        'AMD RYZEN 5 5500U WITH RADEON GRAPHICS',
        'AMD RYZEN 5 5600H WITH RADEON GRAPHICS',
        'AMD RYZEN 5 7520U WITH RADEON GRAPHICS',
        'AMD RYZEN 5 PRO 7530U WITH RADEON GRAPHICS',
        'AMD RYZEN 7 5700G WITH RADEON GRAPHICS',
        'AMD RYZEN 7 5700U WITH RADEON GRAPHICS',
        'AMD RYZEN 7 7735U WITH RADEON GRAPHICS',
        'AMD RYZEN 7 PRO 5750G WITH RADEON GRAPHICS',
        'AMD RYZEN 7 PRO 7730U WITH RADEON GRAPHICS',
        'AMD RYZEN 7 PRO 7840HS W/ RADEON 780M GRAPHICS',
        'AMD RYZEN 7 PRO 8840U W/ RADEON 780M GRAPHICS',
        'AMD RYZEN 9 5900HX WITH RADEON GRAPHICS',
        'AMD RYZEN THREADRIPPER PRO 3945WX 12-CORES',
        'APPLE M1',
        'APPLE M1 PRO',
        'APPLE M2',
        'APPLE M3',
        'INTEL(R) ATOM(TM) X5-Z8550 CPU @ 1.44GHZ',
        'INTEL(R) CELERON(R) CPU G3900 @ 2.80GHZ',
        'INTEL(R) CELERON(R) CPU N3060 @ 1.60GHZ',
        'INTEL(R) CELERON(R) J4005 CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I3 CPU 540 @ 3.07GHZ',
        'INTEL(R) CORE(TM) I3 CPU M 350 @ 2.27GHZ',
        'INTEL(R) CORE(TM) I3-1005G1 CPU @ 1.20GHZ',
        'INTEL(R) CORE(TM) I3-10105 CPU @ 3.70GHZ',
        'INTEL(R) CORE(TM) I3-2100 CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I3-2120 CPU @ 3.30GHZ',
        'INTEL(R) CORE(TM) I3-2310M CPU @ 2.10GHZ',
        'INTEL(R) CORE(TM) I3-2350M CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I3-3110M CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I3-3217U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I3-3220 CPU @ 3.30GHZ',
        'INTEL(R) CORE(TM) I3-3240T CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I3-3245 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I3-4005U CPU @ 1.70GHZ',
        'INTEL(R) CORE(TM) I3-4030U CPU @ 1.90GHZ',
        'INTEL(R) CORE(TM) I3-4150T CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I3-4160 CPU @ 3.60GHZ',
        'INTEL(R) CORE(TM) I3-4170 CPU @ 3.70GHZ',
        'INTEL(R) CORE(TM) I3-5005U CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I3-6006U CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I3-6100 CPU @ 3.70GHZ',
        'INTEL(R) CORE(TM) I3-6100T CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I3-6100U CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I3-7020U CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I3-7100 CPU @ 3.90GHZ',
        'INTEL(R) CORE(TM) I3-8100 CPU @ 3.60GHZ',
        'INTEL(R) CORE(TM) I3-8100T CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I5 CPU 650 @ 3.20GHZ',
        'INTEL(R) CORE(TM) I5 CPU M 460 @ 2.53GHZ',
        'INTEL(R) CORE(TM) I5-10210U CPU @ 1.60GHZ',
        'INTEL(R) CORE(TM) I5-10310U CPU @ 1.70GHZ',
        'INTEL(R) CORE(TM) I5-1035G1 CPU @ 1.00GHZ',
        'INTEL(R) CORE(TM) I5-10400 CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-10400T CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I5-10500 CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I5-10500T CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I5-10505 CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I5-10600 CPU @ 3.30GHZ',
        'INTEL(R) CORE(TM) I5-2400 CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I5-2400S CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-2410M CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I5-2430M CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I5-2450M CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-3210M CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-3230M CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I5-3320M CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I5-3337U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I5-3340M CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I5-3450S CPU @ 2.80GHZ',
        'INTEL(R) CORE(TM) I5-3470 CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I5-3470S CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-3470T CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-3550 CPU @ 3.30GHZ',
        'INTEL(R) CORE(TM) I5-3570 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I5-4200M CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-4200U CPU @ 1.60GHZ',
        'INTEL(R) CORE(TM) I5-4210U CPU @ 1.70GHZ',
        'INTEL(R) CORE(TM) I5-4300U CPU @ 1.90GHZ',
        'INTEL(R) CORE(TM) I5-4310U CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I5-4430 CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I5-4430S CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I5-4460S CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-4460T CPU @ 1.90GHZ',
        'INTEL(R) CORE(TM) I5-4570 CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I5-4570S CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-4590 CPU @ 3.30GHZ',
        'INTEL(R) CORE(TM) I5-4590S CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I5-5200U CPU @ 2.20GHZ',
        'INTEL(R) CORE(TM) I5-5300U CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I5-6200U CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) I5-6300U CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I5-6400 CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I5-6400T CPU @ 2.20GHZ',
        'INTEL(R) CORE(TM) I5-6500 CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I5-6500T CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-6600T CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I5-7200U CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I5-7300U CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I5-7400 CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I5-7400T CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I5-7500 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I5-7500T CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I5-8250U CPU @ 1.60GHZ',
        'INTEL(R) CORE(TM) I5-8257U CPU @ 1.40GHZ',
        'INTEL(R) CORE(TM) I5-8265U CPU @ 1.60GHZ',
        'INTEL(R) CORE(TM) I5-8350U CPU @ 1.70GHZ',
        'INTEL(R) CORE(TM) I5-8365U CPU @ 1.60GHZ',
        'INTEL(R) CORE(TM) I5-8400 CPU @ 2.80GHZ',
        'INTEL(R) CORE(TM) I5-8400T CPU @ 1.70GHZ',
        'INTEL(R) CORE(TM) I5-8500 CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I5-8500T CPU @ 2.10GHZ',
        'INTEL(R) CORE(TM) I5-8600 CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I5-9400 CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I5-9500 CPU @ 3.00GHZ',
        'INTEL(R) CORE(TM) I5-9500T CPU @ 2.20GHZ',
        'INTEL(R) CORE(TM) I7 CPU Q 740 @ 1.73GHZ',
        'INTEL(R) CORE(TM) I7-10510U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I7-10610U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I7-1065G7 CPU @ 1.30GHZ',
        'INTEL(R) CORE(TM) I7-10700 CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I7-10700K CPU @ 3.80GHZ',
        'INTEL(R) CORE(TM) I7-10700T CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I7-10750H CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I7-10810U CPU @ 1.10GHZ',
        'INTEL(R) CORE(TM) I7-14650HX',
        'INTEL(R) CORE(TM) I7-14700T',
        'INTEL(R) CORE(TM) I7-2600 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I7-3630QM CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I7-3667U CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I7-3770 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I7-4510U CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I7-4550U CPU @ 1.50GHZ',
        'INTEL(R) CORE(TM) I7-4600U CPU @ 2.10GHZ',
        'INTEL(R) CORE(TM) I7-4702MQ CPU @ 2.20GHZ',
        'INTEL(R) CORE(TM) I7-4765T CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I7-4770 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I7-4770S CPU @ 3.10GHZ',
        'INTEL(R) CORE(TM) I7-4790 CPU @ 3.60GHZ',
        'INTEL(R) CORE(TM) I7-4790S CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I7-5500U CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I7-5600U CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I7-6500U CPU @ 2.50GHZ',
        'INTEL(R) CORE(TM) I7-6600U CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I7-6700 CPU @ 3.40GHZ',
        'INTEL(R) CORE(TM) I7-6700T CPU @ 2.80GHZ',
        'INTEL(R) CORE(TM) I7-7500U CPU @ 2.70GHZ',
        'INTEL(R) CORE(TM) I7-7600U CPU @ 2.80GHZ',
        'INTEL(R) CORE(TM) I7-7700 CPU @ 3.60GHZ',
        'INTEL(R) CORE(TM) I7-7700HQ CPU @ 2.80GHZ',
        'INTEL(R) CORE(TM) I7-7700T CPU @ 2.90GHZ',
        'INTEL(R) CORE(TM) I7-8550U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I7-8565U CPU @ 1.80GHZ',
        'INTEL(R) CORE(TM) I7-8650U CPU @ 1.90GHZ',
        'INTEL(R) CORE(TM) I7-8665U CPU @ 1.90GHZ',
        'INTEL(R) CORE(TM) I7-8700 CPU @ 3.20GHZ',
        'INTEL(R) CORE(TM) I7-8700T CPU @ 2.40GHZ',
        'INTEL(R) CORE(TM) I7-9700K CPU @ 3.60GHZ',
        'INTEL(R) CORE(TM) I7-9700T CPU @ 2.00GHZ',
        'INTEL(R) CORE(TM) I7-9750H CPU @ 2.60GHZ',
        'INTEL(R) CORE(TM) I9-9880H CPU @ 2.30GHZ',
        'INTEL(R) CORE(TM) ULTRA 5 125U',
        'INTEL(R) CORE(TM) ULTRA 7 155H',
        'INTEL(R) CORE(TM) ULTRA 7 155U',
        'INTEL(R) CORE(TM) ULTRA 7 165U',
        'INTEL(R) CORE(TM) ULTRA 9 185H',
        'INTEL(R) CORE(TM)2 DUO CPU E7500 @ 2.93GHZ',
        'INTEL(R) CORE(TM)2 DUO CPU E8400 @ 3.00GHZ',
        'INTEL(R) CORE(TM)2 DUO CPU E8600 @ 3.33GHZ',
        'INTEL(R) CORE(TM)2 DUO CPU T6570 @ 2.10GHZ',
        'INTEL(R) CORE(TM)2 QUAD CPU Q8400 @ 2.66GHZ',
        'INTEL(R) XEON(R) BRONZE 3106 CPU @ 1.70GHZ',
        'INTEL(R) XEON(R) CPU E3-1225 V5 @ 3.30GHZ',
        'INTEL(R) XEON(R) CPU E3-1225 V6 @ 3.30GHZ',
        'INTEL(R) XEON(R) CPU E3-1226 V3 @ 3.30GHZ',
        'INTEL(R) XEON(R) CPU E3-1240 V3 @ 3.40GHZ',
        'INTEL(R) XEON(R) CPU E3-1245 V2 @ 3.40GHZ',
        'INTEL(R) XEON(R) CPU E3-1270 V5 @ 3.60GHZ',
        'INTEL(R) XEON(R) CPU E31220 @ 3.10GHZ',
        'INTEL(R) XEON(R) CPU E31225 @ 3.10GHZ',
        'INTEL(R) XEON(R) CPU E5-1620 V3 @ 3.50GHZ',
        'INTEL(R) XEON(R) CPU E5-1620 V4 @ 3.50GHZ',
        'INTEL(R) XEON(R) CPU E5-2407 0 @ 2.20GHZ',
        'INTEL(R) XEON(R) CPU E5-2440 V2 @ 1.90GHZ',
        'INTEL(R) XEON(R) CPU E5-2603 V3 @ 1.60GHZ',
        'INTEL(R) XEON(R) CPU E5-2609 V3 @ 1.90GHZ',
        'INTEL(R) XEON(R) CPU E5-2620 0 @ 2.00GHZ',
        'INTEL(R) XEON(R) CPU E5-2620 V2 @ 2.10GHZ',
        'INTEL(R) XEON(R) CPU E5-2650 0 @ 2.00GHZ',
        'INTEL(R) XEON(R) CPU E5-2650 V4 @ 2.20GHZ',
        'INTEL(R) XEON(R) CPU E5-2665 0 @ 2.40GHZ',
        'INTEL(R) XEON(R) CPU E5-2667 V4 @ 3.20GHZ',
        'INTEL(R) XEON(R) CPU E5-2697 V3 @ 2.60GHZ',
        'INTEL(R) XEON(R) CPU E5504 @ 2.00GHZ',
        'INTEL(R) XEON(R) E-2124G CPU @ 3.40GHZ',
        'INTEL(R) XEON(R) E-2224 CPU @ 3.40GHZ',
        'INTEL(R) XEON(R) E-2224G CPU @ 3.50GHZ',
        'INTEL(R) XEON(R) GOLD 5315Y CPU @ 3.20GHZ',
        'INTEL(R) XEON(R) GOLD 5317 CPU @ 3.00GHZ',
        'INTEL(R) XEON(R) GOLD 6140 CPU @ 2.30GHZ',
        'INTEL(R) XEON(R) SILVER 4112 CPU @ 2.60GHZ',
        'INTEL(R) XEON(R) SILVER 4114 CPU @ 2.20GHZ',
        'INTEL(R) XEON(R) SILVER 4208 CPU @ 2.10GHZ',
        'INTEL(R) XEON(R) SILVER 4210R CPU @ 2.40GHZ',
        'INTEL(R) XEON(R) W-1270 CPU @ 3.40GHZ',
        'PENTIUM(R) DUAL-CORE CPU E5400 @ 2.70GHZ',
        'PENTIUM(R) DUAL-CORE CPU E5500 @ 2.80GHZ',
        'Exynos 9810 de ocho núcleos a 2 GHz + 1,7 GHz',
        'Qualcomm Snapdragon™ SM6375',
        'Intel(R) Core(TM) Ultra 5 235T (R)',
        'Intel(R) Core(TM) Ultra 5 235U vPro (R)',
    ];

    /** Memoria ram — lista desplegable oficial del archivo. */
    private array $memoria_ram = [
        '12GB',
        '16GB',
        '1GB',
        '20GB',
        'NO APLICA',
        '24GB',
        '2GB',
        '32GB',
        '4GB',
        '512 MB',
        '6GB',
        '8GB',
        'PENDIENTE',
    ];

    /** Disco duro — lista desplegable oficial del archivo. */
    private array $disco_duro = [
        '120GB',
        '128GB',
        '16GB',
        '180GB',
        'NO APLICA',
        '1TB',
        '1TB + 500GB',
        '240GB',
        '250GB',
        '256GB',
        '300GB',
        '320GB',
        '4 TB',
        '480GB',
        '500GB',
        '512GB',
        '5TB',
        '600GB',
        '720GB',
        '750GB',
        '64GB',
        'SSD 256GB',
        'PENDIENTE',
    ];

    /** Sistema operativo — lista desplegable oficial del archivo. */
    private array $sistema_operativo = [
        'ANDROID',
        'IOS',
        'LINUX',
        'MACOS BIG SUR',
        'NO APLICA',
        'MACOS CATALINA',
        'MACOS MONTEREY',
        'MACOS SEQUOIA',
        'MACOS SONOMA',
        'MACOS VENTURA',
        'PENDIENTE',
        'WINDOWS 10 PRO',
        'WINDOWS 11 PRO',
        'WINDOWS 7',
        'WINDOWS 8',
        'WINDOWS 8.1',
        'WINDOWS VISTA',
        'WINDOWS XP',
        'WINDOWS 11 HOME',
        'WINDOWS 10 HOME',
    ];

    /** Marca — lista desplegable oficial del archivo. */
    private array $marca = [
        'Lenovo',
        'Dell Inc',
        'Apple Inc',
        'HP',
        'Kyocera',
        'Epson',
        'Ricoh',
        'Samsung',
        'Data Card',
        'Kodak',
        'Jaltech',
        'Zebra',
        'TSC',
        'Microsoft',
        'Asus',
        'Razer',
        'MSI',
    ];

    /** Tipo novedad — lista desplegable oficial del archivo. */
    private array $tipo_novedad = [
        'Inventario Físico',
        'Traslado',
        'Cambio de responsable',
        'Retiro definitivo',
        'Préstamo',
        'Reparación',
    ];

    /** Empresa acta — lista desplegable oficial del archivo. */
    private array $empresa_acta = [
        'Federacafé',
        'Fondo Nacional',
        'Almacafé',
        'Procafecol',
        'Otro',
    ];

    public function run(): void
    {
        $now = now();

        // ── catalogo_estados / catalogo_razones: catálogo real del CMDB ──
        foreach ($this->estados as [$codigo, $nombre, $descripcion]) {
            DB::table('catalogo_estados')->updateOrInsert(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'descripcion' => $descripcion, 'updated_at' => $now]
            );
        }
        // Sólo se retiran los códigos legacy equivocados (la migración original
        // sembró 11 estados que no corresponden al catálogo real del archivo).
        DB::table('catalogo_estados')
            ->whereIn('codigo', ['disponible', 'prestamo', 'reparacion', 'asignacion_responsable', 'procesar_baja'])
            ->delete();

        foreach ($this->razones as [$codigo, $nombre, $descripcion]) {
            DB::table('catalogo_razones')->updateOrInsert(
                ['codigo' => $codigo],
                ['nombre' => $nombre, 'descripcion' => $descripcion, 'updated_at' => $now]
            );
        }
        DB::table('catalogo_razones')
            ->whereIn('codigo', ['temporal', 'obsoleto']) // razones legacy fuera del catálogo real
            ->delete();

        // ── diccionarios (grupo, valor) — resto de listas desplegables ──
        $grupos = [
            'estado_uso'          => array_column($this->estadoUso, 0),
            'tipo_equipo'         => $this->tipo_equipo,
            'empresa_propietario' => $this->empresa_propietario,
            'dependencia'         => $this->dependencia,
            'fuente_recurso'      => $this->fuente_recurso,
            'empresa_funcionario' => $this->empresa_funcionario,
            'administrado'        => $this->administrado,
            'procesador'          => $this->procesador,
            'memoria_ram'         => $this->memoria_ram,
            'disco_duro'          => $this->disco_duro,
            'sistema_operativo'   => $this->sistema_operativo,
            'marca'               => $this->marca,
            'tipo_novedad'        => $this->tipo_novedad,
            'empresa_acta'        => $this->empresa_acta,
        ];

        foreach ($grupos as $grupo => $valores) {
            foreach ($valores as $valor) {
                DB::table('diccionarios')->updateOrInsert(
                    ['grupo' => $grupo, 'valor' => $valor],
                    ['activo' => true, 'updated_at' => $now]
                );
            }
            // NO destructivo: los valores agregados/editados por el usuario
            // desde el módulo Catálogos se conservan (la importación valida
            // contra el grupo completo, no contra la lista del archivo).
        }

        $nEstados = count($this->estados);
        $nRazones = count($this->razones);
        $nDicc    = array_sum(array_map('count', $grupos));
        $this->command->info("✔ Catálogos CMDB: {$nEstados} estados, {$nRazones} razones, {$nDicc} valores en diccionarios.");
    }
}
