#!/bin/bash
###############################################################################
# SIMRS Database Backup Cron Script
# 
# Automated database backup script for cron job
# 
# Setup:
# 1. Make executable: chmod +x scripts/cron-backup.sh
# 2. Add to crontab: crontab -e
# 3. Add line: 0 2 * * * /var/www/simrs/scripts/cron-backup.sh
#
# This will run backup every day at 2 AM
###############################################################################

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
LOG_DIR="$PROJECT_DIR/storage/logs"
LOG_FILE="$LOG_DIR/cron-backup.log"

# Create log directory if not exists
mkdir -p "$LOG_DIR"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Start backup
log_message "======================================"
log_message "Starting scheduled database backup"
log_message "======================================"

# Run PHP backup script
/usr/bin/php "$SCRIPT_DIR/backup.php" >> "$LOG_FILE" 2>&1

# Check exit status
if [ $? -eq 0 ]; then
    log_message "Backup completed successfully"
    exit 0
else
    log_message "ERROR: Backup failed!"
    
    # Optional: Send email notification
    # mail -s "SIMRS Backup Failed" admin@example.com < "$LOG_FILE"
    
    exit 1
fi