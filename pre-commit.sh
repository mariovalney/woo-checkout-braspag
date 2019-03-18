#!/bin/sh

#########################
#                       #
#     Initializing      #
#                       #
#########################

PHPCS_BIN=./vendor/bin/phpcs
PHPCBF_BIN=./vendor/bin/phpcbf

# Check for PHPCS / PHPCBF
if [ ! -x $PHPCS_BIN ]; then
    echo "[Vizir] PHP CodeSniffer is not installed locally."
    echo "[Vizir] Please run 'composer install' or check the path: $PHPCS_BIN"
    exit 1
fi

if [ ! -x $PHPCBF_BIN ]; then
    echo "[Vizir] PHP Code Beautifier and Fixer is not installed locally."
    echo "[Vizir] Please run 'composer install' or check the path: $PHPCBF_BIN"
    exit 1
fi

PHPMD_BIN=./vendor/bin/phpmd

# Check for PHPMD
if [ ! -x $PHPMD_BIN ]; then
    echo "[Vizir] PHP Mess Detect is not installed locally."
    echo "[Vizir] Please run 'composer install' or check the path: $PHPMD_BIN"
    exit 1
fi

#########################
#                       #
#       Starting        #
#                       #
#########################

PROJECT=$(git rev-parse --show-toplevel)

# All files in staging area (no deletions)

FILES=$(git diff --cached --name-only --diff-filter=ACMR HEAD | grep .php)

if [ "$FILES" != "" ]
then
    # Coding Standards

    echo "[Vizir] Checking PHPCS..."

    $PHPCS_BIN -n $FILES

    if [ $? != 0 ]
    then
        echo "[Vizir] Coding standards errors have been detected."
        echo "[Vizir] Running PHP Code Beautifier and Fixer..."

        $PHPCBF_BIN -n $FILES

        echo "[Vizir] Checking PHPCS again..."

        $PHPCS_BIN -n $FILES

        if [ $? != 0 ]
        then
            echo "[Vizir] PHP Code Beautifier and Fixer wasn't able to solve all problems."
            echo "[Vizir] Run 'composer lint' to check all errors and fix manually."
            exit 1
        fi

        echo "[Vizir] All errors are fixed automatically."

        git add $FILES
    else
        echo "[Vizir] No errors found."
    fi

    # Mess Detector

    echo "\n[Vizir] Checking PHPMD...\n"

    for FILE in $FILES
    do
        $PHPMD_BIN $PROJECT/$FILE text phpmd.xml

        if [ $? != 0 ]
        then
            echo "\n[Vizir] Fix errors before commit."
            exit 1
        fi
    done
fi

exit $?
