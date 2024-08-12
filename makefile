RNT_PRODUCT := rnw
RNT_BASE    := ../../server/src
RNT_INCLUDE := 1

all: $(TARGET)

ifdef HOSTED
    MAKEPATH := $(RNT_BASE)/common/scripts/make
    include ./make.moddefs
    SCR_OBJDIR := compiled
    include $(MAKEPATH)/make.phpdefs
    include $(MAKEPATH)/make.phprules
		# Remove blank lines at the beginning of the file.
		# The script compile process inserts blank lines at the beginning of the file. (44,431 at last count)
		# Historically the process deleted all blank lines in the file to fix that.
		# That has undesirable effects on modern PHP features like heredocs and nowdocs.
    BLANK_LINE_REMOVER := sed -e '/./,$$!d'
	# Construct list of CX versions that are already compiled by grabbing the version dirs already in cp/compiled/versions
	COMPILED_VERSIONS := $(shell find ./compiled/versions -maxdepth 1 -name 'rnw-*' -type d -printf " %P")
	# Wrap each already compiled version in ! -path so it will be skipped during compilation
	VERSIONS_TO_SKIP_STR := $(foreach version,$(COMPILED_VERSIONS),! -path './versions/$(version)*')
	
	SCRIPTS := $(shell find $(VERSIONS_TO_SKIP_STR) ! -path '*/test/*' ! -path '*/tests/*' ! -path '*/docs/*' ! -path '*/compiled*' ! -path '*/generated/*' ! -path '*/customer/development/*' ! -path './mod_info.phph' ! -path '*/core/util/tarballDeploy.php' ! -path '*/core/util/mappingUpgrade.php' \( -name '*.php' -o -name '*.phph' \) | sort)

	TEMP_TARGET := $(subst ./, $(SCR_OBJDIR)/, $(SCRIPTS))
    TARGET += $(TEMP_TARGET)

# This is a common dependency
NEEDED_HEADERS := $(addprefix $(HEADERDIR)/, $(HEADERS))
$(filter-out $(NEEDED_HEADERS), $(TARGET)):	$(NEEDED_HEADERS)

else
    SCR_OBJDIR := .
endif

TARGET += $(SCR_OBJDIR)/mod_info.phph

include $(RNT_BASE)/common/include/make/defs

CX_BUILD_NUM ?= $(BUILD_NUM)

# always build mod_info.phph to get new build time
mod_info.phph: FORCE
	sed -e 's!VER_TOKEN!$(BUILD_VER)!' -e 's!VERCMN_TOKEN!$(CMN_BUILD_VER)!' \
		-e "s!CX_NUM_TOKEN!$(CX_BUILD_NUM)!" -e 's!NUM_TOKEN!$(BUILD_NUM)!' -e 's!VER_HTDIG_TOKEN!$(HTDIG_BUILD_VER)!' \
		-e "s!DATE_TOKEN!$(BUILD_DATE)!" -e "s!TIME_TOKEN!$(BUILD_TIME)!" \
		-e 's!SP_TOKEN!$(BUILD_SP)!' \
		mod_info.src > $@.tmp
	cmp $@.tmp $@ >/dev/null 2>&1 || \
	mv $@.tmp $@
	-rm -f $@.tmp

FORCE:

include $(RNT_BASE)/common/include/make/makerules

clean::
	$(RM) -r compiled
