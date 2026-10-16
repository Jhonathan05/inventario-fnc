# Guía de Arquitectura Visual y Estratégica: Dashboard ITAM

**Proyecto:** Aplicativo de Inventario y Gestión de Activos Tecnológicos  
**Enfoque:** Monitoreo Estratégico (ITAM 2026) y Diseño de Interfaz (macOS Style)  
**Stack Tecnológico Compatible:** Laravel (Blade SSR), Alpine.js, Tailwind CSS

---

## Parte 1: Estrategia y Métricas del Dashboard ITAM (Estándares 2026)

La gestión de activos de TI (ITAM) requiere transformar datos operativos en inteligencia de negocio. El panel de control debe estructurarse bajo normativas globales, integrando ciberseguridad, finanzas y sostenibilidad.

### 1.1 Fundamentos Normativos (ISO 19770, ESG e ITIL 4)
*   **ISO/IEC 19770-1 y Sostenibilidad (ESG):** La arquitectura debe soportar auditorías financieras y, según las enmiendas de 2024-2025, medir el impacto ambiental. El dashboard debe incluir métricas de eficiencia energética, reducción de *e-waste* y trazabilidad en la disposición final de equipos.
*   **ITIL 4:** Orientación hacia las "corrientes de valor". Un hardware inoperativo no es solo un equipo dañado, es una interrupción en la productividad.

### 1.2 Indicadores Clave de Rendimiento (KPIs)
El panel principal (diseñado para perfiles administrativos y gerenciales) debe priorizar los siguientes indicadores:

| Métrica ITAM | Implicación Estratégica (Business Value) |
| :--- | :--- |
| **Precisión del Inventario Físico** | Identifica discrepancias entre CMDB y la realidad. Objetivo global: > 95%. |
| **Tasa de Descubrimiento / Zero Trust** | Cruce entre equipos registrados y equipos detectados en red. Clave para detectar *Shadow IT* y asegurar políticas de Confianza Cero (Zero Trust). |
| **Cumplimiento de Licencias** | Previene riesgos legales. Muestra la relación entre instalaciones detectadas y cupos comerciales adquiridos. |
| **Brecha de Inactividad (Ghost Inventory)** | Equipos marcados como "Asignados" pero sin actividad de red en 30-45 días. Disparador para recuperar hardware o cancelar suscripciones SaaS. |

### 1.3 FinOps y Costo Total de Propiedad (TCO)
La convergencia entre ITAM y FinOps (operaciones financieras) exige que el panel muestre la economía completa:
*   **Cálculo de TCO:** `Inversión Inicial + Mantenimiento + Operación - Valor Residual`.
*   **Depreciación Contable:** Módulos que automaticen la depreciación en línea recta para periféricos/portátiles y saldo decreciente para servidores.
*   **Renovaciones y MTBF:** Integración de IA predictiva basada en historiales de tickets para prever cuándo un hardware (MTBF) comenzará a fallar, alertando sobre renovaciones contractuales con 30/60/90 días de anticipación.

---

## Parte 2: Arquitectura de Diseño UX/UI (Estilo macOS)

Para reducir la carga cognitiva frente a la alta densidad de datos de un CMDB y HelpDesk, la interfaz debe adoptar los principios del *Apple Human Interface Guidelines (HIG)*: jerarquía de capas, materiales translúcidos y microinteracciones elásticas.

### 2.1 Materiales y Superficies (Vibrancy & Squircles)
Se abandona el uso de fondos sólidos opacos en favor del *frosted glass*.

**Definición de Variables CSS:**
```css
:root {
  /* Modo Claro - Adaptable a esquemas corporativos (ej. detalles #9e052b) */
  --macos-window-bg: rgba(255, 255, 255, 0.75);
  --macos-sidebar-bg: rgba(245, 245, 247, 0.65);
  --macos-toolbar-bg: rgba(255, 255, 255, 0.5);
  --macos-border-subtle: rgba(0, 0, 0, 0.1);
  --macos-accent: #007aff;
  --macos-text-primary: #1d1d1f;
}

@media (prefers-color-scheme: dark) {
  :root {
    --macos-window-bg: rgba(30, 30, 30, 0.75);
    --macos-sidebar-bg: rgba(20, 20, 20, 0.65);
    --macos-toolbar-bg: rgba(40, 40, 40, 0.45);
    --macos-border-subtle: rgba(255, 255, 255, 0.12);
    --macos-accent: #0a84ff;
    --macos-text-primary: #f5f5f7;
  }
}


# Guía de Arquitectura Visual y Estratégica: Dashboard ITAM

**Proyecto:** Aplicativo de Inventario y Gestión de Activos Tecnológicos  
**Enfoque:** Monitoreo Estratégico (ITAM 2026) y Diseño de Interfaz (macOS Style)  
**Stack Tecnológico Compatible:** Laravel (Blade SSR), Alpine.js, Tailwind CSS

---

## Parte 1: Estrategia y Métricas del Dashboard ITAM (Estándares 2026)

La gestión de activos de TI (ITAM) requiere transformar datos operativos en inteligencia de negocio. El panel de control debe estructurarse bajo normativas globales, integrando ciberseguridad, finanzas y sostenibilidad.

### 1.1 Fundamentos Normativos (ISO 19770, ESG e ITIL 4)
*   **ISO/IEC 19770-1 y Sostenibilidad (ESG):** La arquitectura debe soportar auditorías financieras y, según las enmiendas de 2024-2025, medir el impacto ambiental. El dashboard debe incluir métricas de eficiencia energética, reducción de *e-waste* y trazabilidad en la disposición final de equipos.
*   **ITIL 4:** Orientación hacia las "corrientes de valor". Un hardware inoperativo no es solo un equipo dañado, es una interrupción en la productividad.

### 1.2 Indicadores Clave de Rendimiento (KPIs)
El panel principal (diseñado para perfiles administrativos y gerenciales) debe priorizar los siguientes indicadores:

| Métrica ITAM | Implicación Estratégica (Business Value) |
| :--- | :--- |
| **Precisión del Inventario Físico** | Identifica discrepancias entre CMDB y la realidad. Objetivo global: > 95%. |
| **Tasa de Descubrimiento / Zero Trust** | Cruce entre equipos registrados y equipos detectados en red. Clave para detectar *Shadow IT* y asegurar políticas de Confianza Cero (Zero Trust). |
| **Cumplimiento de Licencias** | Previene riesgos legales. Muestra la relación entre instalaciones detectadas y cupos comerciales adquiridos. |
| **Brecha de Inactividad (Ghost Inventory)** | Equipos marcados como "Asignados" pero sin actividad de red en 30-45 días. Disparador para recuperar hardware o cancelar suscripciones SaaS. |

### 1.3 FinOps y Costo Total de Propiedad (TCO)
La convergencia entre ITAM y FinOps (operaciones financieras) exige que el panel muestre la economía completa:
*   **Cálculo de TCO:** `Inversión Inicial + Mantenimiento + Operación - Valor Residual`.
*   **Depreciación Contable:** Módulos que automaticen la depreciación en línea recta para periféricos/portátiles y saldo decreciente para servidores.
*   **Renovaciones y MTBF:** Integración de IA predictiva basada en historiales de tickets para prever cuándo un hardware (MTBF) comenzará a fallar, alertando sobre renovaciones contractuales con 30/60/90 días de anticipación.

---

## Parte 2: Arquitectura de Diseño UX/UI (Estilo macOS)

Para reducir la carga cognitiva frente a la alta densidad de datos de un CMDB y HelpDesk, la interfaz debe adoptar los principios del *Apple Human Interface Guidelines (HIG)*: jerarquía de capas, materiales translúcidos y microinteracciones elásticas.

### 2.1 Materiales y Superficies (Vibrancy & Squircles)
Se abandona el uso de fondos sólidos opacos en favor del *frosted glass*.

**Definición de Variables CSS:**
```css
:root {
  /* Modo Claro - Adaptable a esquemas corporativos (ej. detalles #9e052b) */
  --macos-window-bg: rgba(255, 255, 255, 0.75);
  --macos-sidebar-bg: rgba(245, 245, 247, 0.65);
  --macos-toolbar-bg: rgba(255, 255, 255, 0.5);
  --macos-border-subtle: rgba(0, 0, 0, 0.1);
  --macos-accent: #007aff;
  --macos-text-primary: #1d1d1f;
}

@media (prefers-color-scheme: dark) {
  :root {
    --macos-window-bg: rgba(30, 30, 30, 0.75);
    --macos-sidebar-bg: rgba(20, 20, 20, 0.65);
    --macos-toolbar-bg: rgba(40, 40, 40, 0.45);
    --macos-border-subtle: rgba(255, 255, 255, 0.12);
    --macos-accent: #0a84ff;
    --macos-text-primary: #f5f5f7;
  }
}
Efecto de Cristal y Squircles:

CSS
.macos-window-frame {
  background-color: var(--macos-window-bg);
  backdrop-filter: blur(25px) saturate(190%);
  -webkit-backdrop-filter: blur(25px) saturate(190%);
  
  /* Elevación y bordes finos (Hairline borders) */
  box-shadow: 0 22px 70px 4px rgba(0, 0, 0, 0.28), 0 0 0 1px var(--macos-border-subtle);
  border-radius: 12px; /* Emulación de superelipse */
}
2.2 Anatomía de Ventana y "Traffic Lights"
Estructura clásica de 3 paneles (Split View) gobernada por una barra superior unificada.

HTML
<!-- Controles de ventana (Semáforo) -->
<div class="flex items-center gap-2 px-4 py-3 select-none">
  <button class="w-3 h-3 rounded-full bg-[#ff5f56] border border-black/10 hover:before:content-['×'] flex items-center justify-center text-[8px] text-black/60"></button>
  <button class="w-3 h-3 rounded-full bg-[#ffbd2e] border border-black/10"></button>
  <button class="w-3 h-3 rounded-full bg-[#27c93f] border border-black/10"></button>
</div>
2.3 Tipografía e Iconografía SF Pro
Para evitar licencias, se invoca la pila tipográfica nativa del sistema, lo que activa automáticamente la estética correcta en entornos Apple y caídas limpias en otros OS.

CSS
body {
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", system-ui, sans-serif;
  -webkit-font-smoothing: antialiased;
  letter-spacing: -0.011em;
}
Recomendación de iconos: Utilizar Lucide Icons o Phosphor Icons, configurando un trazo fino (1.5px a 2px) para lograr el Optical Alignment característico de SF Symbols.

2.4 Controles Segmentados e Inputs de Búsqueda
Reemplazar los selectores de pestañas tradicionales (Tabs) por cápsulas segmentadas corredizas.

CSS
/* Búsqueda Estilo Spotlight / Finder */
.macos-search-input {
  background-color: rgba(0, 0, 0, 0.05);
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 5px 10px 5px 28px;
  font-size: 13px;
  color: var(--macos-text-primary);
  transition: all 0.2s ease;
}

.macos-search-input:focus {
  outline: none;
  background-color: #ffffff;
  border-color: var(--macos-accent);
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.25);
}
2.5 Configuración para Tailwind CSS (Integración Directa)
Añade estas utilidades a tu archivo tailwind.config.js para usarlas directamente en tus plantillas .blade.php.

JavaScript
module.exports = {
  theme: {
    extend: {
      colors: {
        macos: {
          accent: 'var(--macos-accent)',
          close: '#ff5f56',
          minimize: '#ffbd2e',
          maximize: '#27c93f',
        }
      },
      borderRadius: {
        'macos-window': '12px',
        'macos-control': '6px',
      },
      boxShadow: {
        'macos-window': '0 22px 70px 4px rgba(0, 0, 0, 0.28), 0 0 0 1px rgba(0,0,0,0.1)',
        'macos-popover': '0 10px 30px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0,0,0,0.1)',
      }
    }
  }
}
2.6 Sistema de Semaforización en CMDB (Alerta Visual)
Evita la fatiga visual. En vistas operativas de técnicos o analistas, el diseño debe ser monocromático (gris/neutro) con inyecciones de color exclusivas para alertas:

Zona Nominal (Gris/Transparente): Equipos asignados operando correctamente.

Zona Ámbar (Precaución): Garantías a vencer en 30 días o stock de consumibles en límite de seguridad.

Zona Crítica (Rojo/Escarlata): Equipos "Zombies" detectados, quiebre de stock, o licencias sobrepasando el límite contractual.

2.7 Microinteracciones y Física
Las animaciones deben emular el rebote físico (Spring Physics) en lugar de curvas lineales (ease-in-out).

CSS
/* Animación para apertura de Modales (Checklists o Tickets) */
.macos-dropdown-enter {
  transform-origin: top left;
  animation: macos-scale-in 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes macos-scale-in {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}