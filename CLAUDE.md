# grova — CLAUDE.md

## Qué es grova

SaaS modular horizontal (similar a Odoo) construido en Symfony 7.4. El objetivo es vender módulos a empresas y personas de distintas industrias. Sin un módulo activo el núcleo no tiene sentido para el usuario final.

## Arquitectura

### Multi-tenant
- Cada tenant tiene su propia base de datos
- La BD principal (`grova_core`) guarda tenants, planes, suscripciones y usuarios
- Cada tenant tiene su BD propia (`grova_{tenant_slug}`)

### Flujo de login y cambio de BD

```
Login con email
  → buscar en grova_core quién es el usuario y a qué tenant pertenece
    → obtener db_name del tenant
      → cambiar conexión Doctrine a la BD del tenant
        → JWT con tenant + módulos activos
          → cada request usa TenantContext para saber a qué BD conectarse
```

### Conexiones Doctrine

Dos conexiones configuradas en `doctrine.yaml`:
- `core` — siempre apunta a `grova_core`
- `tenant` — se sobreescribe en runtime con el `db_name` del tenant autenticado

### TenantContext (servicio central)

Servicio inyectable que cualquier parte del sistema puede usar:
```php
class TenantContext
{
    public function setTenant(Tenant $tenant): void
    public function getTenant(): Tenant
    public function getModulosActivos(): array
    public function getConnection(): Connection
}
```

### JWT payload

```json
{
  "email": "usuario@ejemplo.com",
  "tenant": "grova_jordi",
  "modulos": ["construccion", "facturacion"]
}
```

### Estructura del proyecto

```
src/
  Controller/       # pantallas del sistema base
  Entity/           # entidades del núcleo
  Service/          # servicios del núcleo
  Workspace/        # lógica del workspace
  Module/
    ModuleInterface.php
    ModuleRegistry.php
    Facturacion/
    Legal/
    Construccion/
    POS/
    Restaurante/
    Clinica/
    Financiera/
    Personal/
      Wallet/
      Work/
      Agenda/
      Habitos/
      Pesca/
```

### Niveles de acceso

- **Super Admin** — Christian (ve todos los tenants, gestiona planes, cobra)
- **Tenant Admin** — administrador de cada empresa/cliente
- **Usuario final** — usa los módulos que su admin activó

## Núcleo (infraestructura compartida)

Lo que va en el núcleo es lo que TODOS los módulos usan. Sin un módulo activo no tiene sentido para el usuario.

- Auth, usuarios, roles, permisos
- Multi-tenant + planes + suscripciones
- Contactos (clientes, proveedores, pacientes, empleados — todos son contactos)
- Documentos (adjuntos compartidos por todos los módulos)
- Notificaciones (internas, email, WhatsApp)

## Módulos

### Verticales (por industria)
- **Construcción** — materiales, compras, obras, proveedores
- **Legal** — casos, seguimientos, cobros, historial de clientes
- **POS** — ventas, caja, inventario, productos
- **Restaurante** — mesas, comandas, cocina, reservas
- **Clínica** — pacientes, citas, historial clínico, cobros
- **Financiera** — créditos, cuotas, mora, garantías, cobros

### Transversales (usados por varios verticales)
- **Facturación y cobros**
- **Inventario**
- **Recursos Humanos** — empleados, contratos, asistencia, nómina
- **Agenda y citas**

### Personales (tenant de Christian)
- **Wallet** — gastos, ingresos, suscripciones personales
- **Work** — noches trabajadas (12.5/noche), facturación automática a empresa, feriados, vacaciones
- **Agenda** — calendario personal, cumpleaños, eventos
- **Hábitos** — lectura, ejercicio, tareas, rendimiento personal
- **Pesca** — fincas con geolocalización, puntos de pesca, mareas, gastos compartidos del grupo

## Orden de desarrollo

1. Núcleo — multi-tenant, auth, contactos, documentos, notificaciones
2. Wallet + Work — uso personal diario, feedback inmediato
3. Pesca — uso personal con amigos
4. Legal + Construcción — clientes Jordi (construcción) y abogado
5. POS
6. Restaurante
7. Clínica
8. Financiera

## Entidades del núcleo (grova_core)

### Tenant
- id, nombre, slug, db_name, estado (activo/suspendido/cancelado), created_at

### Plan
- id, nombre, modulos (json — módulos disponibles), precio_mensual, estado

### Suscripcion
- id, tenant_id, plan_id, fecha_inicio, fecha_vencimiento, estado (activa/vencida/cancelada)

### ModuloTenant
- id, tenant_id, modulo_key, activo (true/false)

## Activación de módulos por tenant (3 capas)

1. **Plan** — tú defines qué módulos están disponibles en cada plan
2. **Suscripcion** — el tenant contrata un plan y tiene acceso a esos módulos
3. **ModuloTenant** — el tenant admin decide cuáles de sus módulos están encendidos

Un tenant puede tener un módulo disponible en su plan pero apagado temporalmente — sin perder acceso.

## Tenants

### grova_grova (tenant especial — tu empresa)
Donde gestionas grova como negocio. No es solo el panel técnico, es el módulo de negocio de grova:
- Todos los tenants y su estado
- Clientes de cortesía — cuánto representan en pérdida mensual
- Clientes de pago — ingresos reales
- MRR (ingreso mensual recurrente)
- Churn (clientes que se van)
- Proyección de ingresos
- Costos reales (hosting, n8n, APIs de IA)
- Rentabilidad del negocio

### Tenants de cortesía (prueba)
- **grova_jordi** — Jordi, primer usuario del módulo Construcción
- **grova_alcides** — Alcides, primer usuario del módulo Legal
- **grova_pesca** — grupo de pesca, primer usuario del módulo Pesca
- **grova_christian** — tenant personal de Christian (Wallet, Work, Agenda, Hábitos)

## Stack técnico

- PHP 8.2
- Symfony 7.4
- Doctrine ORM 3.x
- JWT Auth (lexik)
- MariaDB (MySQL)
- n8n para automatizaciones e integraciones de IA (igual que en authemis)

## Reglas de desarrollo

- No aplicar cambios sin consultarlo primero — solo diseñar y proponer
- El núcleo no tiene pantallas de negocio, solo administración del sistema
- No existe ModuleInterface — los módulos se registran por convención en la BD (plan.modulos JSON + modulo_tenant)
- Cada módulo es independiente y puede activarse/desactivarse por tenant
- No mezclar lógica de módulos en el núcleo
- Pool de reglas de IA: este archivo + `contexto-proyecto.md` en grova-docs-privados
- Convención spanglish: variables/funciones/clases en español, palabras reservadas y métodos del framework en inglés

## Estado actual del proyecto (2026-05-17)

### Últimas acciones completadas
1. Seguridad: `.env.dev` eliminado del historial de git (filter-repo), APP_SECRET rotado
2. Repositorio GitHub: remote cambiado a SSH, deploy key agregada al servidor
3. Producción: código actualizado en grovaapp.com, deploy key configurada
4. Usuarios: `grova@grova.com` ahora login con username `grova`, Christian con username `csolorzano`
5. Contributors en GitHub: se removió Co-Authored-By de Claude, ahora solo Christian aparece

### Próximo feature: Sistema de Licencias y Suscripciones (en planificación)

Se está diseñando un sistema para:
1. **Super Admin perpetuo** — Christian (ROLE_SUPER_ADMIN) sin restricciones
2. **Validación automática de suscripciones** — detectar vencimientos y bloquear acceso
3. **Licencias on-premise** — para clientes que instalen grova en su propio servidor
4. **Panel de administración** — gestionar suscripciones, planes y licencias

### Pendientes
- [ ] Fase 1: SubscriptionGuardService + SubscriptionCheckListener + bypass Super Admin
- [ ] Fase 2: Sistema de licencias con sodium crypto (LicenseGeneratorService + LicenseValidatorService)
- [ ] Fase 3: Gestión de planes/suscripciones (admin CRUD)
- Ver plan detallado en: `/Users/christiansolorzano/.claude/plans/joyful-roaming-flamingo.md`
