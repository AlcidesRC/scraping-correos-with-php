.DEFAULT_GOAL := help

###
# CONSTANTS
###

ifneq (,$(findstring xterm,$(TERM)))
	BLACK   := $(shell tput -Txterm setaf 0)
	RED     := $(shell tput -Txterm setaf 1)
	GREEN   := $(shell tput -Txterm setaf 2)
	YELLOW  := $(shell tput -Txterm setaf 3)
	BLUE    := $(shell tput -Txterm setaf 4)
	MAGENTA := $(shell tput -Txterm setaf 5)
	CYAN    := $(shell tput -Txterm setaf 6)
	WHITE   := $(shell tput -Txterm setaf 7)
	RESET   := $(shell tput -Txterm sgr0)
else
	BLACK   := ""
	RED     := ""
	GREEN   := ""
	YELLOW  := ""
	BLUE    := ""
	MAGENTA := ""
	CYAN    := ""
	WHITE   := ""
	RESET   := ""
endif

#---

SERVICE_APP   = app

#---

HOST_USER_ID    := $(shell id --user)
HOST_USER_NAME  := $(shell id --user --name)
HOST_GROUP_ID   := $(shell id --group)
HOST_GROUP_NAME := $(shell id --group --name)

#---

RANDOM_SEED := $(shell head -200 /dev/urandom | cksum | cut -f1 -d " ")

#---

DOCKER_COMPOSE_COMMAND = docker compose

DOCKER_RUN             = $(DOCKER_COMPOSE_COMMAND) run --rm $(SERVICE_APP)
DOCKER_RUN_AS_USER     = $(DOCKER_COMPOSE_COMMAND) run --rm --user $(HOST_USER_ID):$(HOST_GROUP_ID) $(SERVICE_APP)

DOCKER_EXEC            = $(DOCKER_COMPOSE_COMMAND) exec $(SERVICE_APP)
DOCKER_EXEC_AS_USER    = $(DOCKER_COMPOSE_COMMAND) exec --user $(HOST_USER_ID):$(HOST_GROUP_ID) $(SERVICE_APP)

DOCKER_BUILD_ARGUMENTS = --build-arg="HOST_USER_ID=$(HOST_USER_ID)" --build-arg="HOST_USER_NAME=$(HOST_USER_NAME)" --build-arg="HOST_GROUP_ID=$(HOST_GROUP_ID)" --build-arg="HOST_GROUP_NAME=$(HOST_GROUP_NAME)"

###
# FUNCTIONS
###

require-%:
	@if [ -z "$($(*))" ] ; then \
		echo "" ; \
		echo " ${RED}⨉${RESET} Parameter [ ${YELLOW}${*}${RESET} ] is required!" ; \
		echo "" ; \
		echo " ${YELLOW}ℹ${RESET} Usage [ ${YELLOW}make <command>${RESET} ${RED}${*}=${RESET}${YELLOW}xxxxxx${RESET} ]" ; \
		echo "" ; \
		exit 1 ; \
	fi;

define taskDone
	@echo ""
	@echo " ${GREEN}✓${RESET}  ${GREEN}Task done!${RESET}"
	@echo ""
endef

# $(1)=TEXT $(2)=EXTRA
define showInfo
	@echo ""
	@echo " ${YELLOW}ℹ${RESET}  $(1) $(2)"
	@echo ""
endef

# $(1)=TEXT $(2)=EXTRA
define showAlert
	@echo ""
	@echo " ${RED}!${RESET}  $(1) $(2)"
	@echo ""
endef

# $(1)=NUMBER $(2)=TEXT
define orderedList
	@echo ""
	@echo " ${CYAN}$(1).${RESET}  ${CYAN}$(2)${RESET}"
	@echo ""
endef

###
# HELP
###

.PHONY: help
help:
	@clear
	@echo "╔══════════════════════════════════════════════════════════════════════════════╗"
	@echo "║                                                                              ║"
	@echo "║                           ${YELLOW}.:${RESET} AVAILABLE COMMANDS ${YELLOW}:.${RESET}                           ║"
	@echo "║                                                                              ║"
	@echo "╚══════════════════════════════════════════════════════════════════════════════╝"
	@echo ""
	@grep -E '^[a-zA-Z_0-9%-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "· ${YELLOW}%-30s${RESET} %s\n", $$1, $$2}'
	@echo ""

###
# MISCELANEOUS
###

.PHONY: show-context
show-context: ## Setup: show context
	$(call showInfo,"Showing context")
	@echo "    · Host user  : (${YELLOW}${HOST_USER_ID}${RESET}) ${YELLOW}${HOST_USER_NAME}${RESET}"
	@echo "    · Host group : (${YELLOW}${HOST_GROUP_ID}${RESET}) ${YELLOW}${HOST_GROUP_NAME}${RESET}"
	@echo "    · Service(s) : ${YELLOW}${SERVICE_APP}${RESET}"
	$(call taskDone)

###
# DOCKER RELATED
###

.PHONY: build
build: ## Docker: builds the service
	$(call showInfo,"Building the image")
	@$(DOCKER_COMPOSE_COMMAND) build $(DOCKER_BUILD_ARGUMENTS)
	$(call taskDone)

.PHONY: up
up: ## Docker: starts the service
	$(call showInfo,"Starting the service")
	@$(DOCKER_COMPOSE_COMMAND) up --remove-orphans --detach
	$(call taskDone)

.PHONY: restart
restart: ## Docker: restarts the service
	$(call showInfo,"Restarting the service")
	@$(DOCKER_COMPOSE_COMMAND) restart
	$(call taskDone)

.PHONY: down
down: ## Docker: stops the service
	$(call showInfo,"Stopping the service")
	@$(DOCKER_COMPOSE_COMMAND) down --remove-orphans
	$(call taskDone)

.PHONY: logs
logs: ## Docker: exposes the service logs
	$(call showInfo,"Displaying logs from service")
	@$(DOCKER_COMPOSE_COMMAND) logs
	$(call taskDone)

.PHONY: bash
bash: ## Docker: establish a bash session into main container
	$(call showInfo,"Establishing a Bash session into main container...")
	$(DOCKER_RUN_AS_USER) bash
	$(call taskDone)

###
# APPLICATION
###

.PHONY: install
install:
	$(call showInfo,"Installing PHP dependecies...")
	$(DOCKER_RUN_AS_USER) composer install
	$(call taskDone)

.PHONY: generate-csv-files
generate-csv-files:
	$(call showInfo,"Generating CSV files...")
	@$(eval testsuite ?= 'Unit')
	@$(eval filter ?= '.')
	@$(DOCKER_RUN_AS_USER) php -d xdebug.mode=off vendor/bin/phpunit --configuration=phpunit.xml --coverage-text --coverage-html=/coverage --testdox --colors --order-by=random --random-order-seed=$(RANDOM_SEED) --testsuite=$(testsuite) --filter=$(filter)
	$(call taskDone)

.PHONY: init
init: down build install generate-csv-files ## Application: initializes the scrape process
