import { test, expect } from '@playwright/test';
import { login, gotoTab } from './helpers/app.js';

/**
 * Tier FHD (>= 1920x1080).
 *
 * La suite principal corre a 1280x720 y en dispositivos móviles; este archivo
 * comprueba las dos caras del contrato del tier:
 *   1. A 1920x1080 las columnas/bloques `.fnc-fhd-only` se ven.
 *   2. A 1440x900 siguen en el DOM pero ocultos (display: none), de modo que
 *      el layout anterior no cambia.
 */
const MODULOS = [
    { nombre: 'Equipos', ruta: '/equipos', selector: 'th.fnc-fhd-only', texto: 'Ubicación' },
    { nombre: 'Funcionarios', ruta: '/funcionarios', selector: 'th.fnc-fhd-only', texto: 'Empresa' },
    { nombre: 'Asignaciones', ruta: '/asignaciones', selector: 'th.fnc-fhd-only', texto: 'Acta' },
    { nombre: 'Préstamos', ruta: '/prestamos', selector: 'th.fnc-fhd-only', texto: 'Duración' },
    { nombre: 'Licencias', ruta: '/licencias', selector: 'th.fnc-fhd-only', texto: 'Seriales' },
    { nombre: 'Asignaciones de licencias', ruta: '/licencia-asignaciones', selector: 'th.fnc-fhd-only', texto: 'Serial asignado' },
    { nombre: 'Tickets', ruta: '/tickets', selector: 'th.fnc-fhd-only', texto: 'Equipo vinculado' },
    { nombre: 'Reparaciones', ruta: '/historial-tecnico', selector: 'th.fnc-fhd-only', texto: 'Adjuntos' },
    { nombre: 'Historial administrativo', ruta: '/historial-administrativo', selector: 'th.fnc-fhd-only', texto: 'Campo modificado' },
    { nombre: 'Complementos', ruta: '/equipos/complementos-global', selector: 'th.fnc-fhd-only', texto: 'Requiere serial' },
    { nombre: 'Tipos de recurso', ruta: '/tipo-recursos', selector: 'th.fnc-fhd-only', texto: 'Distribución' },
    { nombre: 'Actas firmadas', ruta: '/actas-firmadas', selector: 'th.fnc-fhd-only', texto: 'Versiones' },
    { nombre: 'Checklists', ruta: '/checklists', selector: 'th.fnc-fhd-only', texto: 'Cruce AV' },
    { nombre: 'Usuarios', ruta: '/usuarios', selector: 'th.fnc-fhd-only', texto: 'Correo verificado' },
    { nombre: 'Roles', ruta: '/roles', selector: 'th.fnc-fhd-only', texto: 'Permisos' },
    { nombre: 'Campos personalizados', ruta: '/campos-personalizados', selector: 'th.fnc-fhd-only', texto: 'Grilla' },
    { nombre: 'Reportes', ruta: '/reportes', selector: '.fnc-fhd-only', texto: 'activos en inventario' },
    { nombre: 'Panel de control', ruta: '/dashboard', selector: 'th.fnc-fhd-only', texto: 'Garantía' },
];

test.describe('Tier FHD', () => {
    test.use({ viewport: { width: 1920, height: 1080 } });

    test('muestra la información adicional a 1920x1080', async ({ page }) => {
        test.setTimeout(180_000);
        const tab = await login(page);

        for (const modulo of MODULOS) {
            await gotoTab(page, modulo.ruta, tab);

            const marcador = page
                .locator(modulo.selector, { hasText: modulo.texto })
                .first();

            await expect(
                marcador,
                `${modulo.nombre}: "${modulo.texto}" debe ser visible a 1920x1080`,
            ).toBeVisible({ timeout: 15_000 });
        }
    });

    test('oculta la información adicional a 1440x900', async ({ page }) => {
        test.setTimeout(180_000);
        const tab = await login(page);

        for (const modulo of MODULOS) {
            await gotoTab(page, modulo.ruta, tab);

            const marcador = page
                .locator(modulo.selector, { hasText: modulo.texto })
                .first();

            await expect(
                marcador,
                `${modulo.nombre}: "${modulo.texto}" debe seguir en el DOM a 1440x900`,
            ).toHaveCount(1);

            await page.setViewportSize({ width: 1440, height: 900 });

            await expect(
                marcador,
                `${modulo.nombre}: "${modulo.texto}" no debe verse a 1440x900`,
            ).toBeHidden();

            await page.setViewportSize({ width: 1920, height: 1080 });
        }
    });
});
