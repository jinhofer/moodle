#!/bin/sh

echo "Content-Type: text/plain"
echo ""
echo `date`
echo `hostname -s`
echo $REMOTE_ADDR

if [ -n "$HTTP_X_FORWARDED_FOR" ]; then
    echo "HTTP_X_FORWARDED_FOR: $HTTP_X_FORWARDED_FOR"
fi
