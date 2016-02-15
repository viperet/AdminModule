#!/bin/sh

#xgettext --omit-header -j -o locale/uk_UA.UTF-8/LC_MESSAGES/AdminModule.po   *.php types/*.php views/*.php
msgfmt -o locale/uk_UA.UTF-8/LC_MESSAGES/AdminModule.mo  locale/uk_UA.UTF-8/LC_MESSAGES/AdminModule.po

#xgettext --omit-header -j -o locale/ru_RU.UTF-8/LC_MESSAGES/AdminModule.po *.php types/*.php views/*.php
msgfmt -o locale/ru_RU.UTF-8/LC_MESSAGES/AdminModule.mo  locale/ru_RU.UTF-8/LC_MESSAGES/AdminModule.po

#xgettext --omit-header -j -o locale/en_US.UTF-8/LC_MESSAGES/AdminModule.po *.php types/*.php views/*.php
msgfmt -o locale/en_US.UTF-8/LC_MESSAGES/AdminModule.mo  locale/en_US.UTF-8/LC_MESSAGES/AdminModule.po
