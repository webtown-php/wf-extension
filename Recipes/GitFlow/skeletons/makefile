GITFLOW_BINARY_DIRECTORY := {{ recipe_path }}/bin
# Ezt használjuk az INVERZ grep-ben: egrep -v "\-\-($(ENABLED_GITFLOW_PARAMETERS))"
#                                                   ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
ENABLED_GITFLOW_PARAMETERS := from-this|disable-db|reload-db

GITFLOW_DEVELOP := {{ develop }}
GITFLOW_FEATURE := {{ feature }}
GITFLOW_HOTFIX  := {{ hotfix }}

.PHONY: feature
feature: __feature

.PHONY: hotfix
hotfix: __hotfix

.PHONY: push
push: __push

.PHONY: publish
publish: __publish

.PHONY: gitlab-list-env
gitlab-list-env:
	$(GITFLOW_BINARY_DIRECTORY)/gitlab_list_environments.sh

.PHONY: gitlab-clear-env
gitlab-clear-env:
	$(GITFLOW_BINARY_DIRECTORY)/gitlab_remove_stopped_environments.sh

# GITFLOW COMMANDS

# Check is there any uncommitted changes?
__validate_git_uncommitted_changes:
	$(GITFLOW_BINARY_DIRECTORY)/git_uncommitted_changes.sh

# Check the arguments and the last word!
__validate_gitflow_args: __validate_args_no_empty
    # Are there any invalid parameters? Eg: wf feature --disable-db --invalid new-feature
	UNKNOWN_PARAMETERS="$$(echo "$(ARGS)" | egrep -oh "\-\-[^ ]+" | egrep -v "^\-\-($(ENABLED_GITFLOW_PARAMETERS))$$")"; \
	if [ ! -z "$${UNKNOWN_PARAMETERS}" ]; then \
		echo -e "\n\033[31m❌ There are some unknown parameter:\033[0m"; \
		echo -e "\033[33m$${UNKNOWN_PARAMETERS}\033[0m"; \
		exit 1; \
	fi
    # Is there an invalid name? Eg: wf hotfix --disable-db [missing name!]
	INVALID_NAME="$$(echo "$(lastword $(ARGS))" | egrep "^[^a-zA-Z0-9]")"; \
	if [ ! -z "$${INVALID_NAME}" ]; then \
		echo -e "\n\033[31m❌ Invalid branch name: \033[33m$${INVALID_NAME}\033[0m"; \
		echo -e "\033[94mYou have to finish the command with valid branch name!\033[0m"; \
		exit 1; \
	fi

# make -f .project.makefile __branch ORIGIN_BRANCH="develop" PREFIX="feature" ARGS="..."
__branch: __validate_git_uncommitted_changes __validate_gitflow_args
    # !--from-this --> git fetch -u origin develop/develop
    # Leszedjük a legfrissebb develop verziót
    ifeq (,$(findstring --from-this,$(ARGS)))
		git fetch -u origin $(ORIGIN_BRANCH):$(ORIGIN_BRANCH)
		$(eval FROM_BRANCH := origin/$(ORIGIN_BRANCH))
    else
        # git pull <current-branch> (if exists on origin)
		if [ $$(git ls-remote --heads origin | grep -c $$(git rev-parse --abbrev-ref HEAD)) != 0 ]; then git pull; fi;
		$(eval FROM_BRANCH := HEAD)
    endif

    # (!--disable-db && !--from-this) --> sf doctrine:migrations:execute [...] --down
    ifeq (,$(findstring --disable-db,$(ARGS))$(findstring --from-this,$(ARGS)))
        # Magyarázat:
        #  --name-only                  Csak a nevek kellenek nekünk
        #  --diff-filter=D              Csak azok a fájlok kellenek nekünk, amik törlődni fognak (a módosultakat direkt
        #                               hagyjuk ki)
        #  [...]app/DoctrineMigrations  Csak a migrációs fájlokra vagyunk kiváncsiak
		$(eval MIGRATION_VERSIONS := $(shell [[ -d $(BASE_DIRECTORY)/$(PROJECT_DIRECOTRY)/app/DoctrineMigrations ]] && \
			git diff --name-only --diff-filter=D HEAD..origin/$(ORIGIN_BRANCH) \
				$(BASE_DIRECTORY)/$(PROJECT_DIRECOTRY)/app/DoctrineMigrations \
			| sort -r \
			| egrep -o 'Version[0-9]{14}.php' \
			| egrep -o '[0-9]{14}' \
		))
		@$(foreach V,$(MIGRATION_VERSIONS),$(CMD_MAKE) sf ARGS="doctrine:migrations:execute $(V) -n --down";)
    endif

    # composer install
	$(eval COMPOSER_LOCK := $(shell \
		git diff --name-only HEAD..$(FROM_BRANCH) \
			$(BASE_DIRECTORY)/$(PROJECT_DIRECOTRY)/composer.lock \
	))
	$(foreach CL,$(COMPOSER_LOCK),$(CMD_MAKE) composer ARGS="install")

    # git checkout -B feature/[...] [origin/develop]
	git checkout -B $(PREFIX)/$(lastword $(ARGS)) --no-track $(FROM_BRANCH)
    # git pull <current-branch> (if exists on origin)
	if [ $$(git ls-remote --heads origin | grep -c $$(git rev-parse --abbrev-ref HEAD)) != 0 ]; then git pull; fi;

    # !--disable-db --> make dbreload FULL={--dbreload?}
    ifeq (,$(findstring --disable-db,$(ARGS)))
		$(CMD_MAKE) dbreload FULL="$(findstring --reload-db,$(ARGS))"
    endif

########################################################################################################################
#                                                                                                                      #
#  FEATURE                                                                                                             #
#                                                                                                                      #
#  ... feature [--from-this] [--disable-db] [--reload-db] <name>                                                       #
#                                                                                                                      #
#  --from-this      Ha meg van adva, akkor nem a `develop` branch-ből indít, hanem az aktuálisból.                     #
#  --disable-db     Ha meg van adva, akkor nem babrálja az adatbázist.                                                 #
#  --reload-db      Ha meg van adva, akkor újratölti a teljes adatbázist (drop -> create -> migrations -> fixtures)    #
#                                                                                                                      #
#  name             Kötelező, ez lesz az új feature branch neve: feature/<name> Ha már létezik a branch, akkor arra    #
#                   checkoutol ki.                                                                                     #
#                                                                                                                      #
#----------------------------------------------------------------------------------------------------------------------#
.PHONY: __feature
__feature:
	$(CMD_MAKE) __branch ARGS="$(ARGS)" ORIGIN_BRANCH="$(GITFLOW_DEVELOP)" PREFIX="$(GITFLOW_FEATURE)"

########################################################################################################################
#                                                                                                                      #
#  HOTFIX                                                                                                              #
#                                                                                                                      #
#  ... hotfix [--from-this] [--disable-db] [--reload-db] <name>                                                        #
#                                                                                                                      #
#  --from-this      Ha meg van adva, akkor nem a `develop` branch-ből indít, hanem az aktuálisból.                     #
#  --disable-db     Ha meg van adva, akkor nem babrálja az adatbázist.                                                 #
#  --reload-db      Ha meg van adva, akkor újratölti a teljes adatbázist (drop -> create -> migrations -> fixtures)    #
#                                                                                                                      #
#  name             Kötelező, ez lesz az új feature branch neve: feature/<name> Ha már létezik a branch, akkor arra    #
#                   checkoutol ki.                                                                                     #
#                                                                                                                      #
#----------------------------------------------------------------------------------------------------------------------#
.PHONY: __hotfix
__hotfix:
	$(CMD_MAKE) __branch ARGS="$(ARGS)" ORIGIN_BRANCH="master" PREFIX="$(GITFLOW_HOTFIX)"

.PHONY: __push
__push: __validate_git_uncommitted_changes
	git push -u origin HEAD

.PHONY: __publish
__publish: push
	DEVELOP_BRANCH_NAME=$(GITFLOW_DEVELOP) \
		HOTFIX_BRANCH_PREFIX=$(GITFLOW_HOTFIX) \
		$(GITFLOW_BINARY_DIRECTORY)/gitlab_merge_request.sh
