#!/bin/bash
# Created by Sebastien Choblet (s.choblet@astellia.com)
#

# initialise default value
gen_default_ini_values() {
    # Default value for context
    verc=""
    ctx_name=""
}

# Delete "install" directory created during generator execution
clean_dirs() {
    cd ${out}
    cd ..
    [ "$KEEP_INSTALL" = "false" ] && rm -rf ${out}
}

# Delete directory created during generator execution and exit
clean_exit() {
	clean_dirs
	exit
}
