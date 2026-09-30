<?php

namespace Tests\Unit;

use App\Services\Importadores\CMDBMapperService;
use Tests\TestCase;

/**
 * Tests para CMDBMapperService — específicamente la detección de responsabilidad
 * y la normalización de identificadores.
 *
 * Estos tests no requieren base de datos.
 */
class CMDBMapperServiceTest extends TestCase
{
    private function createInitializedMapper(array $row): CMDBMapperService
    {
        $mapper = new CMDBMapperService();
        // Simular initialize con columnas mínimas requeridas.
        // Para testear isResponsibilityRow solo necesitamos las columnas
        // relevantes, pero initialize requiere suficientes columnas para
        // no lanzar FORMAT_UNKNOWN.
        $mockRow = array_merge([
            'serial' => 'TEST123',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'nombres_y_apellidos' => 'TEST USER',
            'cedula' => '12345678',
        ], $row);

        $mapper->initialize($mockRow);
        return $mapper;
    }

    /**
     * PRUEBA 1: razon_del_estado = "ASIGNACIÓN NORMAL" → NO es responsabilidad
     */
    public function test_asignacion_normal_is_not_responsibility(): void
    {
        $mapper = $this->createInitializedMapper([
            'razon_del_estado' => 'ASIGNACIÓN NORMAL',
        ]);

        $row = [
            'serial' => 'TEST123',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'razon_del_estado' => 'ASIGNACIÓN NORMAL',
            'nombres_y_apellidos' => 'JAVIER LEONARDO MESA TRUJILLO',
            'cedula' => '79912904',
        ];

        $this->assertFalse($mapper->isResponsibilityRow($row));
    }

    /**
     * PRUEBA 2: razon_del_estado = "ASIGNACIÓN RESPONSABLE" → SÍ es responsabilidad
     */
    public function test_asignacion_responsable_is_responsibility(): void
    {
        $mapper = $this->createInitializedMapper([
            'razon_del_estado' => 'ASIGNACIÓN RESPONSABLE',
        ]);

        $row = [
            'serial' => 'TEST456',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'razon_del_estado' => 'ASIGNACIÓN RESPONSABLE',
            'nombres_y_apellidos' => 'OTRO USUARIO',
            'cedula' => '11111111',
        ];

        $this->assertTrue($mapper->isResponsibilityRow($row));
    }

    /**
     * PRUEBA 3: Sin razon_del_estado → NO es responsabilidad
     */
    public function test_no_razon_estado_is_not_responsibility(): void
    {
        $mapper = $this->createInitializedMapper([]);

        $row = [
            'serial' => 'TEST789',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'nombres_y_apellidos' => 'OTRO USUARIO',
            'cedula' => '22222222',
        ];

        $this->assertFalse($mapper->isResponsibilityRow($row));
    }

    /**
     * PRUEBA 4: razon_del_estado = "ASIGNADO" → NO es responsabilidad
     */
    public function test_asignado_is_not_responsibility(): void
    {
        $mapper = $this->createInitializedMapper([
            'razon_del_estado' => 'ASIGNADO',
        ]);

        $row = [
            'serial' => 'TESTABC',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'razon_del_estado' => 'ASIGNADO',
            'nombres_y_apellidos' => 'TEST USER',
            'cedula' => '33333333',
        ];

        $this->assertFalse($mapper->isResponsibilityRow($row));
    }

    /**
     * PRUEBA 5: Valores parciales NO deben causar falso positivo.
     * "RESPONSABLE ADMINISTRATIVO" NO debe ser detectado como responsabilidad.
     */
    public function test_responsable_administrativo_is_not_responsibility(): void
    {
        $mapper = $this->createInitializedMapper([
            'razon_del_estado' => 'RESPONSABLE ADMINISTRATIVO',
        ]);

        $row = [
            'serial' => 'TESTDEF',
            'marca' => 'Dell',
            'modelo' => 'Latitude',
            'tipo' => 'EQUIPO PORTATIL',
            'estado_operativo' => 'Asignado',
            'razon_del_estado' => 'RESPONSABLE ADMINISTRATIVO',
            'nombres_y_apellidos' => 'TEST USER',
            'cedula' => '44444444',
        ];

        $this->assertFalse($mapper->isResponsibilityRow($row));
    }

    /**
     * PRUEBA 7: Normalización de identificaciones equivalentes.
     */
    public function test_normalize_identifier_equivalences(): void
    {
        $normalized1 = CMDBMapperService::normalizeIdentifier('79912904', true);
        $normalized2 = CMDBMapperService::normalizeIdentifier('79912904.0', true);
        $normalized3 = CMDBMapperService::normalizeIdentifier('79.912.904', true);

        $this->assertEquals('79912904', $normalized1);
        $this->assertEquals('79912904', $normalized2);
        $this->assertEquals('79912904', $normalized3);

        // Todas deben ser iguales
        $this->assertEquals($normalized1, $normalized2);
        $this->assertEquals($normalized1, $normalized3);
    }

    /**
     * PRUEBA 8: Identificación con espacios y guiones.
     */
    public function test_normalize_identifier_with_spaces_and_dashes(): void
    {
        $normalized = CMDBMapperService::normalizeIdentifier('79 912-904', true);
        $this->assertEquals('79912904', $normalized);
    }

    /**
     * PRUEBA 9: Identificación null.
     */
    public function test_normalize_identifier_null(): void
    {
        $this->assertNull(CMDBMapperService::normalizeIdentifier(null));
        $this->assertNull(CMDBMapperService::normalizeIdentifier(''));
        $this->assertNull(CMDBMapperService::normalizeIdentifier('  '));
    }

    /**
     * PRUEBA 10: Encabezados equivalentes todos mapean al mismo campo.
     */
    public function test_heading_equivalences(): void
    {
        // "funcionario_asignado" debería mapear a nombre_usuario
        $mapper1 = $this->createInitializedMapper([
            'funcionario_asignado' => 'TEST USER',
        ]);
        $this->assertTrue($mapper1->has('nombre_usuario'));

        // "nombres_y_apellidos" debería mapear a nombre_usuario
        $mapper2 = $this->createInitializedMapper([
            'nombres_y_apellidos' => 'TEST USER',
        ]);
        $this->assertTrue($mapper2->has('nombre_usuario'));
    }
}
