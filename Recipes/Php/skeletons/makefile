DOCKER_CLI_NAME		:= {{ names.engine }}
DOCKER_DOC_ROOT		:= {{ project_path }}

XDEBUG_IDE_SERVER_NAME := {{ environment.XDEBUG_IDE_SERVER_NAME | default(server.xdebug_ide_server_name) }}
PHP_IDE_CONFIG := "serverName=${XDEBUG_IDE_SERVER_NAME}"
WF_XDEBUG_ENABLED := {{ server.xdebug ? '1' : '0' }}

ORIGINAL_CMD_DOCKER_ENV := $(CMD_DOCKER_ENV)
define CMD_DOCKER_ENV
    $(ORIGINAL_CMD_DOCKER_ENV) \
    WF_XDEBUG_ENABLED=$(WF_XDEBUG_ENABLED) \
    PHP_IDE_CONFIG=$(PHP_IDE_CONFIG)
endef

.PHONY: php
php: DOCKER_USER="www-data"
php:
	$(CMD_DOCKER_RUN_CLI) php $(ARGS)

php-exec: DOCKER_USER="www-data"
php-exec: up
	$(CMD_DOCKER_EXEC_CLI) php $(ARGS)

.PHONY: composer
composer: DOCKER_USER="www-data"
composer:
	$(CMD_DOCKER_RUN_CLI) composer $(ARGS)
