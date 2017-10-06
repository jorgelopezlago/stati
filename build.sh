#!/bin/bash

composer --no-dev update && box build && cp ./build/stati.phar ./build/stati && cd ../stati-paginate-plugin/ && box build && cd ../stati/ && cd ../stati-related-plugin/ && box build && cd ../stati/ && cd ../stati-categories-plugin/ && box build && cd ../stati/ && cp ../stati-paginate-plugin/build/paginate.phar ./build/ && cp ../stati-related-plugin/build/related.phar ./build/ && cp ../stati-categories-plugin/build/categories.phar build/ && cd ./build/ && zip stati.zip *.phar stati && cd ../ && composer update