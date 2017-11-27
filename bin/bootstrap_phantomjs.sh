#!/usr/bin/env bash

pkill phantomjs
./bin/phantomjs --config=./phantomjs.json >/dev/null 2>&1 < /dev/null &
