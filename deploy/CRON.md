# Cron jobs — Grova (Producción)

Agregar al crontab del usuario `www-data` o el que ejecuta la app:

```bash
sudo crontab -u www-data -e
```

## Jobs

```cron
# ── Grova ── Mantenimiento y suscripciones ─────────────────

# 00:00 — Marcar suscripciones vencidas (diario)
0 0 * * * /usr/bin/php /var/www/grova/bin/console grova:suscripciones:vencer --no-interaction >> /var/www/grova/var/log/cron-suscripciones.log 2>&1

# 08:00 — Recordatorio suscripciones próximas a vencer (diario)
0 8 * * * /usr/bin/php /var/www/grova/bin/console grova:suscripciones:recordar --no-interaction >> /var/www/grova/var/log/cron-recordatorios.log 2>&1

# 03:00 — Limpieza general semanal: tokens revocados, notificaciones viejas, errores resueltos (domingo)
0 3 * * 0 /usr/bin/php /var/www/grova/bin/console grova:mantenimiento --no-interaction >> /var/www/grova/var/log/cron-mantenimiento.log 2>&1
```

## Logs

```bash
tail -f /var/www/grova/var/log/cron-suscripciones.log
tail -f /var/www/grova/var/log/cron-recordatorios.log
tail -f /var/www/grova/var/log/cron-mantenimiento.log
```

## Verificar que corren

```bash
# Ver últimos intentos de cron
grep -i grova /var/log/syslog

# Ejecutar manual para probar
sudo -u www-data php /var/www/grova/bin/console grova:suscripciones:vencer --no-interaction
sudo -u www-data php /var/www/grova/bin/console grova:suscripciones:recordar --no-interaction
sudo -u www-data php /var/www/grova/bin/console grova:mantenimiento --no-interaction
```

## Prueba de error crítico (bajo demanda)

```bash
sudo -u www-data php /var/www/grova/bin/console grova:probar-email-errores --no-interaction
```
