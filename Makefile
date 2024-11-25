app_name=stepupauth

get_version = $(shell  grep /version appinfo/info.xml | sed 's/.*\([0-9]\.[0-9]\.[0-9]\).*/\1/')
cert_dir=$(HOME)/.nextcloud/certificates
project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
version := $(call get_version)

all: appstore

release: appstore

clean:
	rm -rf $(build_dir)

sign: package
	docker run --rm --volume $(cert_dir):/certificates --detach --name nextcloud nextcloud:latest
	sleep 10
	docker cp $(build_dir)/$(app_name)-$(version).tar.gz nextcloud:/var/www/html/custom_apps
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar -xzf "$(app_name)-$(version).tar.gz" && rm "$(app_name)-$(version).tar.gz
	docker exec -u www-data nextcloud /bin/bash -c "php /var/www/html/occ integrity:sign-app --certificate /certificates/"$(app_name)".crt --privateKey /certificates/"$(app_name)".key --path /var/www/html/custom_apps/"$(app_name)
	docker exec -u www-data nextcloud /bin/bash -c "cd /var/www/html/custom_apps && tar pzcf "$(app_name)-$(version)".tar.gz "$(app_name)
	docker cp nextcloud:/var/www/html/custom_apps/$(app_name)-$(version).tar.gz $(build_dir)/$(app_name)-$(version).tar.gz
	docker kill nextcloud

appstore: sign

package: clean
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/translationfiles \
	--exclude=.tx \
	--exclude=/tests \
	--exclude=.git \
	--exclude=.github \
	--exclude=/l10n/l10n.pl \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=.gitattributes \
	--exclude=.gitignore \
	--exclude=.scrutinizer.yml \
	--exclude=.travis.yml \
	--exclude=/Makefile \
	--exclude=.drone.yml \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name)-$(version).tar.gz \
		-C $(sign_dir) $(app_name)
