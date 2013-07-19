PROJECT = isi-php-fw

dist :
	tar -czf $(PROJECT)_`date --rfc-3339='date'`.tar.gz --ignore-failed-read \
		makefile README.md COPYING \
		index.php \
		JSON.php \
		system/launcher.php \
		system/core/*.php \
		system/library/*.php \
		system/library/browscap.ini \
		application/config/*.php \
		application/controller/*.php \
		application/view/*.php \
		application/library/ \
		static/css/pure-min.css \
		static/js/mootools-core-1.4.5-full-nocompat-yc.js

mrproper :
	find . -name '*~' -print0 | xargs -0 -r rm

