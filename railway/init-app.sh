#!/bin/sh
set -e

if [ -z "${APP_URL:-}" ]; then
    if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
        export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
    elif [ -n "${RAILWAY_STATIC_URL:-}" ]; then
        export APP_URL="${RAILWAY_STATIC_URL%/}"
    fi
fi

if [ -n "${APP_URL:-}" ]; then
    export APP_URL="${APP_URL%/}"
    export VNPAY_RETURN_URL="${VNPAY_RETURN_URL:-${APP_URL}/vnpay/return}"
    export VNPAY_IPN_URL="${VNPAY_IPN_URL:-${APP_URL}/vnpay/ipn}"
fi

export VNPAY_TMN_CODE="${VNPAY_TMN_CODE:-TYIMV67T}"
export VNPAY_HASH_SECRET="${VNPAY_HASH_SECRET:-LNBQQ3N8MYP26ECD7DW47JM60474RKUD}"
export VNPAY_ENVIRONMENT="${VNPAY_ENVIRONMENT:-sandbox}"
export VNPAY_LOCALE="${VNPAY_LOCALE:-vn}"
export VNPAY_EXPIRE_TIME="${VNPAY_EXPIRE_TIME:-30}"

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache
