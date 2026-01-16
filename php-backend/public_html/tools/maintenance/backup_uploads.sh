#!/bin/sh
# compacta uploads e guarda com timestamp (usa no servidor via cron se disponível)
tar czf /tmp/uploads_backup_$(date +%Y%m%d%H%M%S).tgz /path/to/public_html/uploads
# após isto move para local seguro se houver espaço
