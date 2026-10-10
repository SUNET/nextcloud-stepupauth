app_name=stepupauth

version := $(shell sed -n 's/.*<version>\([^<]*\)<\/version>.*/\1/p' "$(CURDIR)/appinfo/info.xml")
cert_dir ?= $(HOME)/.nextcloud/certificates
signing_image ?= nextcloud:35.0.1
project_dir=$(CURDIR)
build_dir=$(CURDIR)/build/artifacts
archive=$(build_dir)/$(app_name)-$(version).tar.gz
package_files=appinfo lib img COPYING README.md CHANGELOG.md publiccode.yml

.PHONY: all release clean sign appstore package

all: appstore

release: appstore

clean:
	rm -rf -- "$(build_dir)"

sign: package
	sh "$(project_dir)/scripts/sign.sh" "$(signing_image)" "$(archive)" "$(cert_dir)"

appstore: sign

package:
	mkdir -p "$(build_dir)"
	tar --sort=name --mtime=@0 --owner=0 --group=0 --numeric-owner \
		--transform='flags=r;s,^,$(app_name)/,' \
		-czf "$(archive)" -C "$(project_dir)" $(package_files)
