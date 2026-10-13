<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Módulo Residencial — seed de ítems de menú.
 * Idempotente: usa WHERE NOT EXISTS para no duplicar si ya existen.
 */
final class Version20260524100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Módulo Residencial: seed de ítems de menú (principal + submenús admin/guardia/residente)';
    }

    public function up(Schema $schema): void
    {
        // Menú principal
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial', NULL, 'Residencial', 'bi-house-door', 85, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial')
        ");

        // ── Admin ────────────────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-admin', 'residencial', 'Administración', 'bi-gear', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-admin')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-dashboard', 'residencial-admin', 'Dashboard', 'bi-speedometer2', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-dashboard')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-casas', 'residencial-admin', 'Casas', 'bi-houses', 20, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-casas')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-residentes', 'residencial-admin', 'Residentes', 'bi-people', 30, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-residentes')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-historial', 'residencial-admin', 'Historial', 'bi-clock-history', 40, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-historial')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-paquetes', 'residencial-admin', 'Paquetes', 'bi-box-seam', 50, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-paquetes')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-incidentes', 'residencial-admin', 'Incidentes', 'bi-exclamation-triangle', 60, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-incidentes')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-mapa', 'residencial-admin', 'Editor de mapa', 'bi-map', 70, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-mapa')
        ");

        // ── Guardia ───────────────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-guardia', 'residencial', 'Garita', 'bi-shield-check', 20, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-guardia')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-garita', 'residencial-guardia', 'Panel garita', 'bi-camera-video', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-garita')
        ");

        // ── Residente ─────────────────────────────────────────────────────────
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-residente', 'residencial', 'Mi acceso', 'bi-person-badge', 30, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-residente')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-mis-invitaciones', 'residencial-residente', 'Mis invitaciones', 'bi-qr-code', 10, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-mis-invitaciones')
        ");
        $this->addSql("
            INSERT INTO menu (menu_key, parent_key, label, icon, sort_order, show_in_sidebar, dev_only, required_role, status)
            SELECT 'residencial-historial-visitas', 'residencial-residente', 'Historial de visitas', 'bi-journal-text', 20, 1, 0, NULL, 'hecho'
            WHERE NOT EXISTS (SELECT 1 FROM menu WHERE menu_key = 'residencial-historial-visitas')
        ");
    }

    public function down(Schema $schema): void
    {
        $keys = [
            'residencial-historial-visitas',
            'residencial-mis-invitaciones',
            'residencial-residente',
            'residencial-garita',
            'residencial-guardia',
            'residencial-mapa',
            'residencial-incidentes',
            'residencial-paquetes',
            'residencial-historial',
            'residencial-residentes',
            'residencial-casas',
            'residencial-dashboard',
            'residencial-admin',
            'residencial',
        ];

        foreach ($keys as $key) {
            $this->addSql("DELETE FROM menu WHERE menu_key = '$key'");
        }
    }
}
