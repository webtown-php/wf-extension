.PHONY: ezphp
ezphp: DOCKER_USER="www-data"
ezphp: DOCKER_CLI_NAME="{{ names.engine }}"
ezphp:
	$(CMD_DOCKER_RUN_CLI) php $(ARGS)

.PHONY: ezphp-exec
ezphp-exec: DOCKER_USER="www-data"
ezphp-exec: DOCKER_CLI_NAME="{{ names.engine }}"
ezphp-exec: up
	$(CMD_DOCKER_EXEC_CLI) php $(ARGS)

.PHONY: ezsf
ezsf: DOCKER_USER="www-data"
ezsf: DOCKER_CLI_NAME="{{ names.engine }}"
ezsf: up
	$(CMD_DOCKER_EXEC_CLI) php $(SF_CONSOLE_CMD) $(ARGS)

.PHONY: ezcomposer
ezcomposer: DOCKER_USER="www-data"
ezcomposer: DOCKER_CLI_NAME="{{ names.engine }}"
ezcomposer:
	$(CMD_DOCKER_RUN_CLI) composer $(ARGS)
