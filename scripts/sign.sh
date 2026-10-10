#!/bin/sh
# SPDX-FileCopyrightText: 2026 SUNET
# SPDX-License-Identifier: AGPL-3.0-or-later

set -eu

image="$1"
archive="$2"
cert_dir="$3"
app_name=stepupauth
package_name=$(basename "$archive")
container=''

test -r "$cert_dir/$app_name.crt"
test -r "$cert_dir/$app_name.key"

cleanup() {
	if [ -n "$container" ]; then
		docker rm --force --volumes "$container" >/dev/null
	fi
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

container=$(docker run --detach --network none \
	--mount "type=bind,source=$cert_dir/$app_name.crt,target=/certificates/$app_name.crt,readonly" \
	--mount "type=bind,source=$cert_dir/$app_name.key,target=/certificates/$app_name.key,readonly" "$image")

attempt=0
until docker exec --user www-data "$container" php /var/www/html/occ status >/dev/null 2>&1; do
	attempt=$((attempt + 1))
	if [ "$attempt" -ge 60 ]; then
		printf '%s\n' 'Signing container did not become ready within 60 seconds.' >&2
		docker logs "$container" >&2
		exit 1
	fi
	sleep 1
done

# Owner-only host keys remain private while occ runs as www-data.
docker exec "$container" install --directory --mode=0700 --owner=www-data --group=www-data \
	/tmp/signing-certificates
docker exec "$container" install --mode=0400 --owner=www-data --group=www-data \
	"/certificates/$app_name.crt" "/certificates/$app_name.key" /tmp/signing-certificates

docker cp "$archive" "$container:/var/www/html/custom_apps/$package_name"
docker exec --user www-data "$container" tar -xzf "/var/www/html/custom_apps/$package_name" \
	-C /var/www/html/custom_apps
docker exec --user www-data "$container" php /var/www/html/occ integrity:sign-app \
	--certificate "/tmp/signing-certificates/$app_name.crt" \
	--privateKey "/tmp/signing-certificates/$app_name.key" \
	--path "/var/www/html/custom_apps/$app_name"
docker exec --user www-data "$container" tar \
	--sort=name --mtime=@0 --owner=0 --group=0 --numeric-owner \
	-czf "/var/www/html/custom_apps/signed-$package_name" -C /var/www/html/custom_apps "$app_name"
docker cp "$container:/var/www/html/custom_apps/signed-$package_name" "$archive"

openssl dgst -sha512 -sign "$cert_dir/$app_name.key" -out "$archive.sig" "$archive"
openssl base64 -A -in "$archive.sig"
printf '\n'
