#!/bin/sh

xgettext --omit-header -j -o locale/uk_UA.UTF-8/LC_MESSAGES/messages.po   *.php types/*.php
msgfmt -o locale/uk_UA.UTF-8/LC_MESSAGES/messages.mo  locale/uk_UA.UTF-8/LC_MESSAGES/messages.po

xgettext --omit-header -j -o locale/ru_RU.UTF-8/LC_MESSAGES/messages.po *.php
msgfmt -o locale/ru_RU.UTF-8/LC_MESSAGES/messages.mo  locale/ru_RU.UTF-8/LC_MESSAGES/messages.po
