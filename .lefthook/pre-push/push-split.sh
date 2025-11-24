#!/usr/bin/env bash

set -e

CURRENT_BRANCH="main"

function remote()
{
    git remote add "$1" "$2" 2>/dev/null || true
}

function split()
{
        LOCAL_SHA=$(git subtree split --prefix="$1")
        REMOTE_SHA=$(git ls-remote "$2" "refs/heads/$CURRENT_BRANCH" | awk '{print $1}')
        if [ "$LOCAL_SHA" = "$REMOTE_SHA" ]; then
            echo ">>> No changes for $1 — skipping."
            return
        fi
        echo ">>> Changes detected for $1 — pushing..."
        git push --no-verify "$2" "$LOCAL_SHA:refs/heads/$CURRENT_BRANCH" -f
}


git pull origin $CURRENT_BRANCH

remote module-accounts-manager git@github.com:unigale/module-accounts-manager.git
remote module-administration git@github.com:unigale/module-administration.git
remote module-execution-platform git@github.com:unigale/module-execution-platform.git
remote module-hypercore git@github.com:unigale/module-hypercore.git
remote module-mailing-system git@github.com:unigale/module-mailing-system.git
remote foundation git@github.com:unigale/foundation.git

split 'modules/AccountsManager' module-accounts-manager &
split 'modules/Administration' module-administration &
split 'modules/ExecutionPlatform' module-execution-platform &
split 'modules/Hypercore' module-hypercore &
split 'modules/MailingSystem' module-mailing-system &
split 'packages/Foundation' foundation &

wait
