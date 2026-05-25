#!/usr/bin/env sh
set -eu

php_version="$(
	php -r '$composer = json_decode(file_get_contents("composer.json"), true); echo $composer["config"]["platform"]["php"] ?? "8.2";'
)"
image_name="featured-image-block-fallback-release-build:php-${php_version}"

docker build \
	--build-arg "PHP_VERSION=${php_version}" \
	-f Dockerfile.build \
	-t "${image_name}" \
	.

docker run --rm \
	-v "$PWD":/app \
	-w /app \
	"${image_name}" \
	composer run build-release
